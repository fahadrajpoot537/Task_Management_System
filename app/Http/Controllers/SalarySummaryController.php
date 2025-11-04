<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class SalarySummaryController extends Controller
{
    public function print(Request $request)
    {
        $userId = $request->query('user_id');
        $dateFrom = $request->query('from');
        $dateTo = $request->query('to');
        $noDeduction = $request->query('no_deduction', 0) == 1;
        $manualBonus = is_numeric($request->query('manual_bonus', 0)) ? max(0, (float)$request->query('manual_bonus', 0)) : 0;

        $user = User::findOrFail($userId);
        $attendanceRecords = AttendanceRecord::where('user_id', $userId)
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->orderBy('attendance_date', 'asc')
            ->get();

        // Expected working days (Mon-Fri)
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        $expectedWorkingDays = 0; $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) { if ($cursor->isWeekday()) { $expectedWorkingDays++; } $cursor->addDay(); }

        // Expected hours per day (fallback 9)
        $expectedHoursPerDay = ($user->check_in_time && $user->check_out_time)
            ? max(0, Carbon::parse($user->check_out_time)->diffInMinutes(Carbon::parse($user->check_in_time)) / 60 - 0.5)
            : 9;

        $monthlySalary = $user->monthly_salary ?? 0;
        $dailyWage = $expectedWorkingDays > 0 ? $monthlySalary / $expectedWorkingDays : 0;
        $hourlyWage = $expectedHoursPerDay > 0 ? $dailyWage / $expectedHoursPerDay : 0;

        $totalHoursWorked = 0; $totalWagesEarned = 0; $shortLateCount = 0;
        foreach ($attendanceRecords as $record) {
            if ($record->status === 'absent') { continue; }
            
            // Calculate hours worked
            $hoursWorked = $this->calculateHoursWorkedForRecord($record, $user, $expectedHoursPerDay);
            
            // If no deduction is enabled, pay for full expected hours even if worked less
            if ($noDeduction && $record->status !== 'absent' && $record->status !== 'holiday' && $record->status !== 'pending') {
                $hoursWorked = $expectedHoursPerDay; // Pay for full expected hours
            }
            
            // Count short lates for reporting
            $lateMinutes = $record->late_minutes ?? 0;
            if ($lateMinutes > 0 && $record->check_in_time) {
                $ci = Carbon::parse($record->check_in_time)->startOfMinute();
                $expectedCi = $user->check_in_time ? Carbon::parse($user->check_in_time)->startOfMinute() : ($user->shift_start ? Carbon::parse($user->shift_start)->startOfMinute() : Carbon::parse('09:00')->startOfMinute());
                if ($ci->eq($expectedCi)) { $lateMinutes = 0; }
            }
            if ($lateMinutes > 0 && $lateMinutes <= 30) { $shortLateCount++; }

            $totalHoursWorked += $hoursWorked;
            $totalWagesEarned += ($hoursWorked * $hourlyWage);
        }

        // Short late penalties
        $fullDayPenaltyCount = intdiv($shortLateCount, 3);
        $remainingShortLates = $shortLateCount % 3;
        $shortLatePenalty = ($fullDayPenaltyCount * $dailyWage) + ($remainingShortLates * 200);

        // Recorded absences
        $recordedAbsentDays = $attendanceRecords->where('status', 'absent')->count();
        $absentDeduction = $recordedAbsentDays * $dailyWage;

        $grossWages = $totalWagesEarned;
        
        // Apply deductions only if no deduction option is not checked
        $actualShortLatePenalty = $noDeduction ? 0 : $shortLatePenalty;
        $actualAbsentDeduction = $noDeduction ? 0 : $absentDeduction;
        $netWages = max(0, $totalWagesEarned - $actualShortLatePenalty - $actualAbsentDeduction);

        // Punctuality bonus (exclude probation)
        $hasAnyLate = $attendanceRecords->contains(fn($r) => $r->status === 'late');
        $hasAnyAbsent = $attendanceRecords->contains(fn($r) => $r->status === 'absent');
        // Missing working days
        $existingDates = $attendanceRecords->pluck('attendance_date')->map(fn($d)=>Carbon::parse($d)->format('Y-m-d'))->toArray();
        $missingDays = 0; $c=$startDate->copy();
        while ($c->lte($endDate)) { if ($c->isWeekday() && !in_array($c->format('Y-m-d'), $existingDates)) { $missingDays++; } $c->addDay(); }
        $isOnProbation = ($user->employment_status === 'probation') || ($user->probation_end_at && Carbon::parse($user->probation_end_at)->isFuture());
        $punctualBonus = (!$isOnProbation && !$hasAnyLate && !$hasAnyAbsent && $missingDays === 0) ? 2500 : 0;
        
        // Add manual bonus and punctual bonus
        $finalWages = $netWages + $punctualBonus + $manualBonus;

        $data = [
            'user_name' => $user->name,
            'user_email' => $user->email,
            'month' => Carbon::parse($dateFrom)->format('F Y'),
            'monthly_salary' => $monthlySalary,
            'expected_working_days' => $expectedWorkingDays,
            'expected_hours_per_day' => $expectedHoursPerDay,
            'daily_wage' => $dailyWage,
            'hourly_wage' => $hourlyWage,
            'short_late_count' => $shortLateCount,
            'short_late_penalty' => $shortLatePenalty,
            'actual_short_late_penalty' => $actualShortLatePenalty,
            'gross_wages' => $grossWages,
            'punctual_bonus' => $punctualBonus,
            'manual_bonus' => $manualBonus,
            'total_bonus' => $punctualBonus + $manualBonus,
            'final_wages' => $finalWages,
            // Absent info (informational)
            'absent_days' => $recordedAbsentDays,
            'absent_deduction' => $absentDeduction,
            'actual_absent_deduction' => $actualAbsentDeduction,
            'no_deduction' => $noDeduction,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        return view('livewire.attendance.summary-print', compact('data'));
    }
    
    /**
     * Calculate hours worked for a record (similar to AttendanceViewer logic)
     */
    private function calculateHoursWorkedForRecord($record, $user, $expectedHoursPerDay)
    {
        // If absent or holiday, return 0
        if (in_array($record->status ?? '', ['absent', 'holiday'])) {
            return 0;
        }
        
        // If WFH or Paid Leave, return the hours_worked value
        if (in_array($record->status ?? '', ['wfh', 'paid_leave'])) {
            return $record->hours_worked ?? $expectedHoursPerDay;
        }
        
        // Calculate based on late minutes
        $lateMinutes = $record->late_minutes ?? 0;
        if ($lateMinutes > 0 && $record->check_in_time) {
            $ci = Carbon::parse($record->check_in_time)->startOfMinute();
            $expectedCi = $user->check_in_time ? Carbon::parse($user->check_in_time)->startOfMinute() : ($user->shift_start ? Carbon::parse($user->shift_start)->startOfMinute() : Carbon::parse('09:00')->startOfMinute());
            if ($ci->eq($expectedCi)) { $lateMinutes = 0; }
        }
        
        // If late is 30 minutes or less (short late), give full working hours
        if ($lateMinutes > 0 && $lateMinutes <= 30) {
            return $expectedHoursPerDay;
        }
        
        // For lates more than 30 minutes, deduct late hours
        if ($lateMinutes > 30) {
            $lateHours = $lateMinutes / 60;
            return max(0, $expectedHoursPerDay - $lateHours);
        }
        
        // Default to expected hours if no late
        return $expectedHoursPerDay;
    }
}



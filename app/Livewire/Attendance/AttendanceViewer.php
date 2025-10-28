<?php

namespace App\Livewire\Attendance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;

class AttendanceViewer extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedUser = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $viewType = 'daily'; // daily, weekly, monthly
    public $perPage = 25;
    public $sortField = 'attendance_date';
    public $sortDirection = 'desc';

    // New properties for dynamic inputs
    public $selectedDate = '';
    public $selectedWeek = '';
    public $selectedMonth = '';
    public $selectedYear = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedUser' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'viewType' => ['except' => 'daily'],
        'perPage' => ['except' => 25],
        'selectedDate' => ['except' => ''],
        'selectedWeek' => ['except' => ''],
        'selectedMonth' => ['except' => ''],
        'selectedYear' => ['except' => ''],
    ];

    public function mount()
    {
        // Set default values based on current date
        $now = Carbon::now();
        $this->selectedDate = $now->format('Y-m-d');
        $this->selectedWeek = $now->format('Y-W');
        $this->selectedMonth = $now->format('n'); // 1-12
        $this->selectedYear = $now->format('Y');
        
        // Set default date range to current month
        $this->dateFrom = $now->startOfMonth()->format('Y-m-d');
        $this->dateTo = $now->endOfMonth()->format('Y-m-d');
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'selectedUser', 'dateFrom', 'dateTo', 'viewType', 'perPage', 'selectedDate', 'selectedWeek', 'selectedMonth', 'selectedYear'])) {
            $this->resetPage();
            $this->updateDateRange();
        }
    }
    
    public function updateDateRange()
    {
        switch ($this->viewType) {
            case 'daily':
                if ($this->selectedDate) {
                    $this->dateFrom = $this->selectedDate;
                    $this->dateTo = $this->selectedDate;
                }
                break;
            case 'weekly':
                if ($this->selectedWeek) {
                    $weekParts = explode('-', $this->selectedWeek);
                    $year = $weekParts[0];
                    $week = $weekParts[1];
                    $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
                    $endOfWeek = Carbon::now()->setISODate($year, $week)->endOfWeek();
                    $this->dateFrom = $startOfWeek->format('Y-m-d');
                    $this->dateTo = $endOfWeek->format('Y-m-d');
                }
                break;
            case 'monthly':
                if ($this->selectedMonth && $this->selectedYear) {
                    $startOfMonth = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
                    $endOfMonth = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();
                    $this->dateFrom = $startOfMonth->format('Y-m-d');
                    $this->dateTo = $endOfMonth->format('Y-m-d');
                }
                break;
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedUser = '';
        $this->viewType = 'daily';
        $now = Carbon::now();
        $this->selectedDate = $now->format('Y-m-d');
        $this->selectedWeek = $now->format('Y-W');
        $this->selectedMonth = $now->format('n');
        $this->selectedYear = $now->format('Y');
        $this->dateFrom = $now->startOfMonth()->format('Y-m-d');
        $this->dateTo = $now->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }

    public function getAttendanceRecordsProperty()
    {
        $query = AttendanceRecord::with('user');

        // Apply user filter
        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        }

        // Apply date range filter
        if ($this->dateFrom) {
            $query->where('attendance_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('attendance_date', '<=', $this->dateTo);
        }

        // Apply search filter
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Apply view type grouping
        if ($this->viewType === 'weekly') {
            if ($this->selectedUser) {
                // For single user weekly view, show individual daily records
                $query->orderBy('attendance_date', 'asc');
            } else {
                // For all users weekly view, show grouped data
            $query->selectRaw('
                WEEK(attendance_date) as week_number,
                YEAR(attendance_date) as year,
                user_id,
                COUNT(*) as total_days,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                SUM(late_minutes) as total_late_minutes,
                SUM(hours_worked) as total_hours_worked,
                AVG(hours_worked) as avg_hours_per_day
            ')
            ->groupBy('user_id', 'week_number', 'year')
            ->orderBy('year', 'desc')
            ->orderBy('week_number', 'desc');
            }
        } elseif ($this->viewType === 'monthly') {
            if ($this->selectedUser) {
                // For single user monthly view, show individual daily records
                $query->orderBy('attendance_date', 'asc');
            } else {
                // For all users monthly view, show grouped data
            $query->selectRaw('
                MONTH(attendance_date) as month_number,
                YEAR(attendance_date) as year,
                user_id,
                COUNT(*) as total_days,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                SUM(late_minutes) as total_late_minutes,
                SUM(hours_worked) as total_hours_worked,
                AVG(hours_worked) as avg_hours_per_day
            ')
            ->groupBy('user_id', 'month_number', 'year')
            ->orderBy('year', 'desc')
            ->orderBy('month_number', 'desc');
            }
        } else {
            // Daily view - default
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return $query->paginate($this->perPage);
    }

    public function getUsersProperty()
    {
        return User::orderBy('name')->get();
    }

    public function getSummaryStatsProperty()
    {
        $query = AttendanceRecord::query();

        // Apply same filters as main query
        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        }
        if ($this->dateFrom) {
            $query->where('attendance_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('attendance_date', '<=', $this->dateTo);
        }
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Get attendance records for calculation
        $attendanceRecords = $query->get();

        $baseStats = [
            'total_records' => $attendanceRecords->count(),
            'total_users' => $attendanceRecords->unique('user_id')->count(),
            'present_days' => $attendanceRecords->where('status', 'present')->count(),
            'late_days' => $attendanceRecords->where('status', 'late')->count(),
            'absent_days' => $attendanceRecords->where('status', 'absent')->count(),
            'total_hours' => $this->selectedUser ? $this->calculateTotalHoursWithGrace($attendanceRecords) : 0,
            'total_late_minutes' => $attendanceRecords->sum('late_minutes'),
            'avg_hours_per_day' => $attendanceRecords->avg('hours_worked'),
        ];

        // Add view-specific stats
        switch ($this->viewType) {
            case 'daily':
                $baseStats['on_leave'] = $attendanceRecords->where('status', 'weekly_off')->count();
                $baseStats['expected_hours'] = $this->getExpectedWorkingHours();
                break;
            case 'weekly':
                $baseStats['avg_attendance_percent'] = $this->getAverageAttendancePercentage();
                $baseStats['expected_weekly_hours'] = $this->getExpectedWeeklyHours();
                break;
            case 'monthly':
                $baseStats['avg_attendance_percent'] = $this->getAverageAttendancePercentage();
                $baseStats['expected_monthly_hours'] = $this->getExpectedMonthlyHours();
                $baseStats['short_late_count'] = $this->countShortLates($attendanceRecords);
                break;
        }

        return $baseStats;
    }
    
    private function getExpectedWorkingHours()
    {
        if ($this->selectedUser) {
            $user = User::find($this->selectedUser);
            return $user ? $this->calculateExpectedHours($user) : 9;
        }
        return 9; // Default 9 hours
    }
    
    private function getExpectedWeeklyHours()
    {
        if ($this->selectedUser) {
            $user = User::find($this->selectedUser);
            return $user ? $this->calculateExpectedHours($user) * 5 : 45; // 5 working days × 9 hours
        }
        return 0; // Don't show expected hours for all employees
    }
    
    private function getExpectedMonthlyHours()
    {
        if ($this->selectedUser) {
            $user = User::find($this->selectedUser);
            return $user ? $this->calculateExpectedHours($user) * 22 : 198; // ~22 working days per month × 9 hours
        }
        return 0; // Don't show expected hours for all employees
    }
    
    private function calculateExpectedHours($user)
    {
        // Always use 9 hours as standard working hours
        return 9;
    }
    
    private function getAverageAttendancePercentage()
    {
        if ($this->selectedUser) {
            // For single employee, calculate their attendance percentage
            $query = AttendanceRecord::where('user_id', $this->selectedUser);
            
            if ($this->dateFrom) {
                $query->where('attendance_date', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $query->where('attendance_date', '<=', $this->dateTo);
            }
            
            $totalDays = $query->count();
            $presentDays = $query->clone()->whereIn('status', ['present', 'late'])->count();
            
            return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
        }
        
        // For all employees
        $totalUsers = User::count();
        if ($totalUsers == 0) return 0;
        
        $presentDays = AttendanceRecord::where('attendance_date', '>=', $this->dateFrom)
            ->where('attendance_date', '<=', $this->dateTo)
            ->whereIn('status', ['present', 'late'])
            ->count();
            
        $totalDays = AttendanceRecord::where('attendance_date', '>=', $this->dateFrom)
            ->where('attendance_date', '<=', $this->dateTo)
            ->count();
            
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
    }
    
    /**
     * Calculate late minutes ignoring seconds
     * Uses the existing late_minutes from database but applies seconds-ignoring logic
     */
    public function calculateLateMinutesWithGrace($attendanceRecord)
    {
        // Use the existing late_minutes from database
        $lateMinutes = $attendanceRecord->late_minutes ?? 0;
        
        // If there are late minutes, we need to check if they should be ignored due to seconds
        if ($lateMinutes > 0 && $attendanceRecord->check_in_time) {
            $checkInTime = Carbon::parse($attendanceRecord->check_in_time);
            $expectedCheckInTime = $this->getExpectedCheckInTime($attendanceRecord);
            
            if ($expectedCheckInTime) {
                // Ignore seconds - set both times to start of minute
                $checkInTimeNoSeconds = $checkInTime->copy()->startOfMinute();
                $expectedTimeNoSeconds = $expectedCheckInTime->copy()->startOfMinute();
                
                // If check-in minute is same as expected minute (ignoring seconds), no late minutes
                if ($checkInTimeNoSeconds->eq($expectedTimeNoSeconds)) {
                    return 0;
                }
            }
        }
        
        return $lateMinutes;
    }
    
    /**
     * Get expected check-in time for an attendance record
     */
    private function getExpectedCheckInTime($attendanceRecord)
    {
        $user = $attendanceRecord->user;
        
        if ($user && $user->shift_start) {
            return Carbon::parse($user->shift_start);
        }
        
        // Default check-in time (9:00 AM)
        return Carbon::parse('09:00');
    }
    
    /**
     * Calculate hours worked ignoring seconds
     */
    public function calculateHoursWorkedWithGrace($attendanceRecord)
    {
        $totalHours = 9; // Standard working hours
        $lateMinutes = $this->calculateLateMinutesWithGrace($attendanceRecord);
        $lateHours = $lateMinutes / 60;
        
        return max(0, $totalHours - $lateHours);
    }
    
    /**
     * Calculate total hours for multiple attendance records with grace period
     */
    private function calculateTotalHoursWithGrace($attendanceRecords)
    {
        $totalHours = 0;
        
        foreach ($attendanceRecords as $record) {
            $totalHours += $this->calculateHoursWorkedWithGrace($record);
        }
        
        return $totalHours;
    }
    
    /**
     * Count short lates (≤30 minutes)
     */
    private function countShortLates($attendanceRecords)
    {
        $shortLateCount = 0;
        
        foreach ($attendanceRecords as $record) {
            if (!$record->check_in_time || !$record->user || $record->status === 'absent') {
                continue;
            }
            
            $lateMinutes = $this->calculateLateMinutesWithGrace($record);
            
            // Count only short lates (≤30 minutes)
            if ($lateMinutes > 0 && $lateMinutes <= 30) {
                $shortLateCount++;
            }
        }
        
        return $shortLateCount;
    }

    public function render()
    {
        return view('livewire.attendance.attendance-viewer', [
            'attendances' => $this->attendanceRecords,
            'users' => $this->users,
            'summaryStats' => $this->summaryStats,
            'years' => $this->getYears(),
            'months' => $this->getMonths(),
            'weeks' => $this->getWeeks(),
        ]);
    }
    
    private function getYears()
    {
        $currentYear = Carbon::now()->year;
        return range($currentYear - 4, $currentYear);
    }
    
    private function getMonths()
    {
        return [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
    }
    
    private function getWeeks()
    {
        $weeks = [];
        $currentYear = Carbon::now()->year;
        
        for ($week = 1; $week <= 52; $week++) {
            $startOfWeek = Carbon::now()->setISODate($currentYear, $week)->startOfWeek();
            $endOfWeek = Carbon::now()->setISODate($currentYear, $week)->endOfWeek();
            $weeks[$currentYear . '-' . $week] = "Week {$week} ({$startOfWeek->format('M d')} - {$endOfWeek->format('M d')})";
        }
        
        return $weeks;
    }
}
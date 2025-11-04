<?php

namespace App\Livewire\Zkteco;

use Livewire\Component;
use Jmrashed\Zkteco\Lib\ZKTeco;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceManager extends Component
{
    public $ip = '192.168.1.252';
    public $port = 4370;
    public $attendance = [];
    public $users = [];
    public $deviceInfo = [];
    public $isConnected = false;
    public $loading = false;
    public $error = '';
    public $workingHours = [];
    public $editingUser = null;
    public $editCheckIn = '';
    public $editCheckOut = '';
    public $attendanceCalculations = [];

    public function mount()
    {
        $this->ip = env('ZKTECO_IP', '192.168.1.252');
        $this->port = env('ZKTECO_PORT', 4370);
    }

    public function connect()
    {
        $this->loading = true;
        $this->error = '';
        $this->attendance = [];
        $this->users = [];
        $this->deviceInfo = [];

        try {
            // Try the alt driver first
            $zk = new ZKTeco($this->ip, $this->port);
            
            if ($zk->connect()) {
                $this->isConnected = true;
                
                // Get device info
                $this->deviceInfo = [
                    'version' => $zk->version(),
                    'platform' => $zk->platform(),
                    'os' => $zk->osVersion(),
                    'serial' => $zk->serialNumber(),
                ];

                // Get users
                $zk->disableDevice();
                $this->users = $zk->getUser();
                $zk->enableDevice();

                // Load working hours for all users
                $this->loadWorkingHours();

                session()->flash('success', 'Connected successfully! Found ' . count($this->users) . ' users.');
            } else {
                $this->error = 'Failed to connect to device. Check IP/port settings.';
            }
        } catch (\Exception $e) {
            $this->error = 'Connection error: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    public function fetchAttendance()
    {
        if (!$this->isConnected) {
            $this->error = 'Please connect to device first.';
            return;
        }

        $this->loading = true;
        $this->error = '';

        try {
            $zk = new ZKTeco($this->ip, $this->port);
            
            if ($zk->connect()) {
                // Fetch attendance with retries
                $attendance = [];
                for ($i = 1; $i <= 3; $i++) {
                    $zk->disableDevice();
                    $attendance = $zk->getAttendance();
                    $zk->enableDevice();
                    if (!empty($attendance)) {
                        break;
                    }
                    usleep(200000);
                }

                if (!empty($attendance)) {
                    // Fetch all attendance records (not just today's)
                    $this->attendance = $attendance;
                    session()->flash('success', 'Fetched ' . count($attendance) . ' attendance records from device.');
                } else {
                    $this->error = 'No attendance records found. Make sure there are punch records on the device.';
                }
            } else {
                $this->error = 'Failed to reconnect to device.';
            }
        } catch (\Exception $e) {
            $this->error = 'Error fetching attendance: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    public function saveAttendanceToDatabase()
    {
        if (empty($this->attendance)) {
            $this->error = 'No attendance data available. Please fetch attendance first.';
            return;
        }

        $this->loading = true;
        $this->error = '';
        $savedCount = 0;

        try {
            foreach ($this->attendance as $record) {
                $deviceUserId = $record['id'];
                
                // Find user by device_user_id
                $user = User::where('device_user_id', $deviceUserId)->first();
                
                if (!$user) {
                    // Try to find by zkteco_uid as fallback
                    $user = User::where('zkteco_uid', $deviceUserId)->first();
                }
                
                if (!$user) {
                    // Log unmatched records for debugging
                    Log::info("No user found for device_user_id: {$deviceUserId}");
                    continue; // Skip if user not found
                }

                // Parse the timestamp
                $timestamp = Carbon::parse($record['timestamp']);
                $recordDate = $timestamp->format('Y-m-d');
                $actualCheckInTime = $timestamp->format('H:i:s');
                $expectedCheckInTime = $user->check_in_time;
                
                // Calculate late minutes - check_in_time in users table is COMPANY SET TIME
                $lateMinutes = 0;
                $earlyMinutes = 0;
                $status = 'present';
                $isCheckOut = false;
                
                if ($expectedCheckInTime) {
                    $companySetCheckInTime = Carbon::parse($recordDate . ' ' . $expectedCheckInTime);
                    $actualTime = Carbon::parse($recordDate . ' ' . $actualCheckInTime);
                    
                    // Determine if this is likely a check-out based on time
                    $expectedCheckOut = $user->check_out_time ? Carbon::parse($recordDate . ' ' . $user->check_out_time) : null;
                    
                    if ($expectedCheckOut && $actualTime->greaterThan($expectedCheckOut->copy()->subHours(2))) {
                        // This is likely a check-out
                        $isCheckOut = true;
                        if ($actualTime->lessThan($expectedCheckOut)) {
                            $earlyMinutes = $expectedCheckOut->diffInMinutes($actualTime);
                            $status = $earlyMinutes > 15 ? 'early_departure' : 'present';
                        }
                    } else {
                        // This is likely a check-in - compare with COMPANY SET TIME
                        // Ignore seconds - compare only at minute level
                        $companySetCheckInTimeNoSeconds = $companySetCheckInTime->copy()->startOfMinute();
                        $actualTimeNoSeconds = $actualTime->copy()->startOfMinute();
                        
                        if ($actualTimeNoSeconds->greaterThan($companySetCheckInTimeNoSeconds)) {
                            // User checked in AFTER company set time (ignoring seconds) = LATE
                            $calculatedLateMinutes = $actualTimeNoSeconds->diffInMinutes($companySetCheckInTimeNoSeconds);
                            
                            // Apply late calculation rules:
                            // - If late < 30 minutes: store exact late minutes
                            // - If late >= 30 minutes and < 60 minutes: store 60 minutes (1 hour)
                            // - If late >= 60 minutes: store exact late minutes
                            if ($calculatedLateMinutes < 30) {
                                $lateMinutes = (int)$calculatedLateMinutes; // Store exact time
                            } elseif ($calculatedLateMinutes >= 30 && $calculatedLateMinutes < 60) {
                                $lateMinutes = 60; // Mark as 1 hour
                            } else {
                                $lateMinutes = (int)$calculatedLateMinutes; // Store exact time for 1 hour or more
                            }
                            
                            $status = 'late'; // Any late arrival is marked as late
                            
                            Log::info("Late calculation for user {$user->name}: Calculated={$calculatedLateMinutes} min, Stored={$lateMinutes} min, Check-in={$actualCheckInTime}, Expected={$expectedCheckInTime}");
                        } else {
                            // User checked in ON TIME or EARLY (same minute or earlier, ignoring seconds) = PRESENT
                            $status = 'present';
                        }
                    }
                }
                
                // Check if attendance record already exists for this date
                $existingRecord = AttendanceRecord::where('user_id', $user->id)
                    ->where('attendance_date', $recordDate)
                    ->first();
                
                if ($existingRecord) {
                    // Update existing record
                    $updateData = [
                        'device_uid' => $deviceUserId,
                    ];
                    
                    if ($isCheckOut) {
                        // Update check-out time
                        $updateData['check_out_time'] = $actualCheckInTime;
                        $updateData['early_minutes'] = $earlyMinutes;
                        $updateData['status'] = $status;
                        
                        // Calculate hours worked if both times exist
                        if ($existingRecord->check_in_time) {
                            $checkIn = Carbon::parse($recordDate . ' ' . $existingRecord->check_in_time);
                            $checkOut = Carbon::parse($recordDate . ' ' . $actualCheckInTime);
                            $hoursWorked = $checkOut->diffInMinutes($checkIn) / 60;
                            $updateData['hours_worked'] = round($hoursWorked, 2);
                        }
                    } else {
                        // Update check-in time if it's earlier or doesn't exist
                        if ($existingRecord->check_in_time === null || $actualCheckInTime < $existingRecord->check_in_time) {
                            $updateData['check_in_time'] = $actualCheckInTime;
                            $updateData['late_minutes'] = $lateMinutes;
                            $updateData['status'] = $status;
                        } else {
                            // Even if we don't update check-in time (because existing is earlier),
                            // we should recalculate late_minutes based on the EARLIEST check-in time
                            // Use the existing check-in time to recalculate late minutes
                            $existingCheckInTime = $existingRecord->check_in_time;
                            $existingCheckInTimeObj = Carbon::parse($recordDate . ' ' . $existingCheckInTime);
                            $companySetCheckInTime = Carbon::parse($recordDate . ' ' . $expectedCheckInTime);
                            
                            $companySetCheckInTimeNoSeconds = $companySetCheckInTime->copy()->startOfMinute();
                            $existingCheckInTimeNoSeconds = $existingCheckInTimeObj->copy()->startOfMinute();
                            
                            if ($existingCheckInTimeNoSeconds->greaterThan($companySetCheckInTimeNoSeconds)) {
                                $calculatedLateMinutes = $existingCheckInTimeNoSeconds->diffInMinutes($companySetCheckInTimeNoSeconds);
                                
                                // Apply late calculation rules:
                                // - If late < 30 minutes: store exact late minutes
                                // - If late >= 30 minutes and < 60 minutes: store 60 minutes (1 hour)
                                // - If late >= 60 minutes: store exact late minutes
                                if ($calculatedLateMinutes < 30) {
                                    $recalculatedLateMinutes = (int)$calculatedLateMinutes;
                                } elseif ($calculatedLateMinutes >= 30 && $calculatedLateMinutes < 60) {
                                    $recalculatedLateMinutes = 60;
                                } else {
                                    $recalculatedLateMinutes = (int)$calculatedLateMinutes;
                                }
                                
                                $updateData['late_minutes'] = $recalculatedLateMinutes;
                                $updateData['status'] = 'late';
                                
                                Log::info("Recalculated late for user {$user->name}: Calculated={$calculatedLateMinutes} min, Stored={$recalculatedLateMinutes} min, Check-in={$existingCheckInTime}, Expected={$expectedCheckInTime}");
                            } else {
                                $updateData['late_minutes'] = 0;
                                $updateData['status'] = 'present';
                            }
                        }
                    }
                    
                    $existingRecord->update($updateData);
                    $savedCount++;
                    Log::info("Updated attendance record for user: {$user->name} (ID: {$deviceUserId}) - " . 
                              ($isCheckOut ? "Check-out: {$actualCheckInTime}, Early: {$earlyMinutes} min" : "Check-in: {$actualCheckInTime}, Company Set Time: {$expectedCheckInTime}, Late: {$lateMinutes} min") . 
                              ", Status: {$status}");
                } else {
                    // Create new attendance record
                    $recordData = [
                        'user_id' => $user->id,
                        'attendance_date' => $recordDate,
                        'device_uid' => $deviceUserId,
                        'status' => $status,
                    ];
                    
                    if ($isCheckOut) {
                        $recordData['check_out_time'] = $actualCheckInTime;
                        $recordData['early_minutes'] = $earlyMinutes;
                    } else {
                        $recordData['check_in_time'] = $actualCheckInTime;
                        $recordData['late_minutes'] = $lateMinutes;
                    }
                    
                    AttendanceRecord::create($recordData);
                    $savedCount++;
                    Log::info("Created attendance record for user: {$user->name} (ID: {$deviceUserId}) - " . 
                              ($isCheckOut ? "Check-out: {$actualCheckInTime}, Early: {$earlyMinutes} min" : "Check-in: {$actualCheckInTime}, Company Set Time: {$expectedCheckInTime}, Late: {$lateMinutes} min") . 
                              ", Status: {$status}");
                }
            }
            
            session()->flash('success', "Saved {$savedCount} attendance records to database from all dates.");
            
        } catch (\Exception $e) {
            $this->error = 'Error saving attendance to database: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    public function clearAttendance()
    {
        if (!$this->isConnected) {
            $this->error = 'Please connect to device first.';
            return;
        }

        $this->loading = true;
        $this->error = '';

        try {
            $zk = new ZKTeco($this->ip, $this->port);
            
            if ($zk->connect()) {
                $zk->disableDevice();
                $result = $zk->clearAttendance();
                $zk->enableDevice();
                
                if ($result) {
                    $this->attendance = [];
                    session()->flash('success', 'Attendance records cleared successfully.');
                } else {
                    $this->error = 'Failed to clear attendance records.';
                }
            } else {
                $this->error = 'Failed to reconnect to device.';
            }
        } catch (\Exception $e) {
            $this->error = 'Error clearing attendance: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    public function exportCsv()
    {
        if (empty($this->attendance)) {
            $this->error = 'No attendance data to export.';
            return;
        }

        $filename = 'attendance_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, ['UID', 'Timestamp', 'State', 'Verify', 'Workcode', 'Reserved']);
            
            // Data rows
            foreach ($this->attendance as $row) {
                fputcsv($file, [
                    $row['uid'] ?? '',
                    $row['timestamp'] ?? '',
                    $row['state'] ?? 0,
                    $row['verify'] ?? 0,
                    $row['workcode'] ?? 0,
                    $row['reserved'] ?? 0,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function loadWorkingHours()
    {
        $this->workingHours = [];
        foreach ($this->users as $user) {
            // Try to find user by device_user_id first, then zkteco_uid, then by name
            $dbUser = User::where('device_user_id', $user['uid'])->first();
            if (!$dbUser) {
                $dbUser = User::where('zkteco_uid', $user['uid'])->first();
            }
            if (!$dbUser) {
                $dbUser = User::where('name', 'LIKE', '%' . $user['name'] . '%')->first();
            }
            
            $this->workingHours[$user['uid']] = [
                'check_in_time' => $dbUser ? $dbUser->check_in_time : '',
                'check_out_time' => $dbUser ? $dbUser->check_out_time : '',
                'is_active' => $dbUser ? true : false,
                'user_id' => $dbUser ? $dbUser->id : null,
                'device_user_id' => $dbUser ? $dbUser->device_user_id : null,
            ];
        }
    }

    public function editWorkingHours($uid)
    {
        $this->editingUser = $uid;
        $workingHourData = $this->workingHours[$uid] ?? null;
        $this->editCheckIn = $workingHourData['check_in_time'] ?? '';
        $this->editCheckOut = $workingHourData['check_out_time'] ?? '';
    }

    public function saveWorkingHours()
    {
        if (!$this->editingUser) {
            return;
        }

        $user = collect($this->users)->firstWhere('uid', $this->editingUser);
        if (!$user) {
            return;
        }

        // Find or create user in database
        $dbUser = User::where('device_user_id', $this->editingUser)->first();
        if (!$dbUser) {
            $dbUser = User::where('zkteco_uid', $this->editingUser)->first();
        }
        if (!$dbUser) {
            $dbUser = User::where('name', 'LIKE', '%' . $user['name'] . '%')->first();
        }

        if ($dbUser) {
            // Update existing user
            $dbUser->update([
                'check_in_time' => $this->editCheckIn,
                'check_out_time' => $this->editCheckOut,
                'device_user_id' => $this->editingUser,
                'zkteco_uid' => $this->editingUser, // Keep both for compatibility
            ]);
        } else {
            // Create new user record
            $dbUser = User::create([
                'name' => $user['name'],
                'email' => strtolower(str_replace(' ', '', $user['name'])) . '@company.com', // Generate email
                'password' => bcrypt('password123'), // Default password
                'role_id' => 4, // Default to employee role
                'check_in_time' => $this->editCheckIn,
                'check_out_time' => $this->editCheckOut,
                'device_user_id' => $this->editingUser,
                'zkteco_uid' => $this->editingUser, // Keep both for compatibility
            ]);
        }

        $this->loadWorkingHours();
        $this->editingUser = null;
        $this->editCheckIn = '';
        $this->editCheckOut = '';
        
        session()->flash('success', 'Working hours saved successfully for ' . $user['name']);
    }

    public function cancelEdit()
    {
        $this->editingUser = null;
        $this->editCheckIn = '';
        $this->editCheckOut = '';
    }

    public function calculateAttendance()
    {
        if (empty($this->attendance)) {
            $this->error = 'No attendance data available. Please fetch attendance first.';
            return;
        }

        $calculations = [];
        
        foreach ($this->users as $user) {
            $uid = $user['uid'];
            $dbUser = User::where('zkteco_uid', $uid)->first();
            if (!$dbUser) {
                $dbUser = User::where('name', 'LIKE', '%' . $user['name'] . '%')->first();
            }
            
            if (!$dbUser || !$dbUser->check_in_time || !$dbUser->check_out_time) {
                continue;
            }

            // Get attendance records for this user
            $userAttendance = collect($this->attendance)->where('uid', $uid)->sortBy('timestamp');
            
            if ($userAttendance->isEmpty()) {
                continue;
            }

            $totalHours = 0;
            $totalDays = 0;
            $expectedCheckIn = $dbUser->check_in_time;
            $expectedCheckOut = $dbUser->check_out_time;

            // Group by date
            $attendanceByDate = $userAttendance->groupBy(function ($record) {
                return date('Y-m-d', strtotime($record['timestamp']));
            });

            foreach ($attendanceByDate as $date => $records) {
                $totalDays++;
                
                // Find first check-in and last check-out for the day
                $firstRecord = $records->first();
                $lastRecord = $records->last();
                
                $actualCheckIn = date('H:i:s', strtotime($firstRecord['timestamp']));
                $actualCheckOut = date('H:i:s', strtotime($lastRecord['timestamp']));
                
                // Calculate working hours
                $checkInTime = strtotime($actualCheckIn);
                $checkOutTime = strtotime($actualCheckOut);
                $dayHours = ($checkOutTime - $checkInTime) / 3600;
                
                $totalHours += $dayHours;
                
                $calculations[$uid][] = [
                    'date' => $date,
                    'expected_check_in' => $expectedCheckIn,
                    'expected_check_out' => $expectedCheckOut,
                    'actual_check_in' => $actualCheckIn,
                    'actual_check_out' => $actualCheckOut,
                    'hours_worked' => round($dayHours, 2),
                    'late_arrival' => $actualCheckIn > $expectedCheckIn,
                    'early_departure' => $actualCheckOut < $expectedCheckOut,
                ];
            }

            $calculations[$uid]['summary'] = [
                'total_days' => $totalDays,
                'total_hours' => round($totalHours, 2),
                'average_hours_per_day' => $totalDays > 0 ? round($totalHours / $totalDays, 2) : 0,
            ];
        }

        $this->attendanceCalculations = $calculations;
        session()->flash('success', 'Attendance calculations completed for ' . count($calculations) . ' users.');
    }

    public function render()
    {
        return view('livewire.zkteco.attendance-manager');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Jmrashed\Zkteco\Lib\ZKTeco;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FetchDailyAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:fetch-daily {--date= : Specific date to fetch (Y-m-d format, defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch daily attendance from ZKTeco device and save to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Daily Attendance Fetch Command...');
        
        // Get ZKTeco device IP and port from environment or config
        $ip = env('ZKTECO_IP', '192.168.1.252');
        $port = env('ZKTECO_PORT', 4370);
        
        $this->info("Connecting to ZKTeco device at {$ip}:{$port}...");
        
        try {
            // Connect to ZKTeco device
            $zk = new ZKTeco($ip, $port);
            
            if (!$zk->connect()) {
                $this->error("Failed to connect to ZKTeco device at {$ip}:{$port}");
                return Command::FAILURE;
            }
            
            $this->info("✓ Connected successfully to ZKTeco device");
            
            // Get device info
            $deviceInfo = [
                'version' => $zk->version(),
                'platform' => $zk->platform(),
                'os' => $zk->osVersion(),
                'serial' => $zk->serialNumber(),
            ];
            
            $this->info("Device Info - Version: {$deviceInfo['version']}, Serial: {$deviceInfo['serial']}");
            
            // Get target date (today or specified date)
            $targetDate = $this->option('date') 
                ? Carbon::parse($this->option('date'))->format('Y-m-d')
                : Carbon::now()->format('Y-m-d');
            
            $this->info("Fetching attendance for date: {$targetDate}");
            
            // Fetch attendance with retries
            $attendance = [];
            $zk->disableDevice();
            for ($i = 1; $i <= 3; $i++) {
                $attendance = $zk->getAttendance();
                if (!empty($attendance)) {
                    break;
                }
                $this->warn("Attempt {$i}/3: No attendance records found, retrying...");
                usleep(200000); // 200ms delay
            }
            $zk->enableDevice();
            
            if (empty($attendance)) {
                $this->warn("No attendance records found on device.");
                return Command::SUCCESS;
            }
            
            $this->info("Found " . count($attendance) . " attendance records on device");
            
            // Filter attendance records for the target date
            $todayAttendance = array_filter($attendance, function($record) use ($targetDate) {
                try {
                    $recordDate = Carbon::parse($record['timestamp'])->format('Y-m-d');
                    return $recordDate === $targetDate;
                } catch (\Exception $e) {
                    return false;
                }
            });
            
            $this->info("Filtered " . count($todayAttendance) . " attendance records for {$targetDate}");
            
            if (empty($todayAttendance)) {
                $this->warn("No attendance records found for date: {$targetDate}");
                return Command::SUCCESS;
            }
            
            // Save attendance to database
            $savedCount = $this->saveAttendanceToDatabase($todayAttendance);
            
            $this->info("✓ Successfully saved {$savedCount} attendance records to database for {$targetDate}");
            
            // Disconnect
            $zk->disconnect();
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("FetchDailyAttendance Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
    
    /**
     * Save attendance records to database
     */
    private function saveAttendanceToDatabase(array $attendance): int
    {
        $savedCount = 0;
        
        foreach ($attendance as $record) {
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
                        } else {
                            $updateData['late_minutes'] = 0;
                            $updateData['status'] = 'present';
                        }
                    }
                }
                
                $existingRecord->update($updateData);
                $savedCount++;
                $this->line("  Updated: {$user->name} - {$actualCheckInTime} (" . ($isCheckOut ? 'Check-out' : 'Check-in') . ")");
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
                $this->line("  Created: {$user->name} - {$actualCheckInTime} (" . ($isCheckOut ? 'Check-out' : 'Check-in') . ")");
            }
        }
        
        return $savedCount;
    }
}


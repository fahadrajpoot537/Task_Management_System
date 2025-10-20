<?php

namespace App\Livewire\Zkteco;

use Livewire\Component;
use Jmrashed\Zkteco\Lib\ZKTeco;

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
                    $this->attendance = $attendance;
                    // dd($this->attendance);
                    session()->flash('success', 'Fetched ' . count($attendance) . ' attendance records.');
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

    public function render()
    {
        return view('livewire.zkteco.attendance-manager');
    }
}

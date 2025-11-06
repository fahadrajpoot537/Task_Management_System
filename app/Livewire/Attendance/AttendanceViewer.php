<?php

namespace App\Livewire\Attendance;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\MonthlySalarySummary;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AttendanceViewer extends Component
{
    use WithPagination, WithFileUploads;

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
    
    // WFH Modal properties
    public $showWfhModal = false;
    public $wfhHours = 0;
    public $selectedDateForWFH = '';
    public $maxWfhHours = 9;
    
    // Breakdown Modal properties
    public $showBreakdownModal = false;
    public $breakdownData = [];
    public $manualBonus = 0;
    public $noDeduction = false;
    
    // Holiday Modal properties
    public $showHolidayModal = false;
    public $holidayStartDate = '';
    public $holidayEndDate = '';
    
    // Import Modal properties
    public $showImportModal = false;
    public $importFile = null;
    public $importError = '';
    public $importSuccess = '';
    
    // Edit Attendance modal state
    public $showEditModal = false;
    public $editRecordId = null;
    public $editUserId = null;
    public $editDate = '';
    public $editStatus = '';
    public $editCheckIn = '';
    public $editCheckOut = '';
    public $editHoursWorked = 0;
    public $editLateMinutes = 0;
    public $editLateHoursReadable = '0.0h';

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

    public $canManageAttendance = false;
    public $canOnlyViewOwn = false;

    public function mount()
    {
        // Check if user has permission to view attendance
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('view_attendance') && !$user->hasPermission('manage_attendance')) {
            abort(403, 'You do not have permission to view attendance records.');
        }
        
        // Check if user can manage attendance (has manage_attendance permission or is super admin)
        $this->canManageAttendance = $user->isSuperAdmin() || $user->hasPermission('manage_attendance');
        
        // Check if user can only view own attendance (has view_attendance but not manage_attendance)
        $this->canOnlyViewOwn = !$user->isSuperAdmin() && $user->hasPermission('view_attendance') && !$user->hasPermission('manage_attendance');
        
        // If user can only view own attendance, force select themselves
        if ($this->canOnlyViewOwn) {
            $this->selectedUser = $user->id;
        }
        
        // Set default values based on current date
        $now = Carbon::now();
        $this->selectedDate = $now->format('Y-m-d');
        $this->selectedWeek = $now->format('Y-W');
        $this->selectedMonth = $now->format('n'); // 1-12
        $this->selectedYear = $now->format('Y');
        
        // Set default date range based on view type (daily view defaults to today)
        $this->updateDateRange();
    }

    public function updated($property)
    {
        // If user can only view own attendance, prevent changing selectedUser
        if ($property === 'selectedUser' && $this->canOnlyViewOwn) {
            $this->selectedUser = auth()->id();
            session()->flash('error', 'You can only view your own attendance records.');
            return;
        }
        
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
                } else {
                    // If no date selected, default to today
                    $today = Carbon::now()->format('Y-m-d');
                    $this->selectedDate = $today;
                    $this->dateFrom = $today;
                    $this->dateTo = $today;
                }
                break;
            case 'weekly':
                if ($this->selectedWeek) {
                    $weekParts = explode('-', $this->selectedWeek);
                    if (count($weekParts) === 2 && is_numeric($weekParts[0]) && is_numeric($weekParts[1])) {
                        $year = (int) $weekParts[0];
                        $week = (int) $weekParts[1];
                        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
                        $endOfWeek = Carbon::now()->setISODate($year, $week)->endOfWeek();
                        $this->dateFrom = $startOfWeek->format('Y-m-d');
                        $this->dateTo = $endOfWeek->format('Y-m-d');
                    } else {
                        // Fallback to current week if malformed
                        $startOfWeek = Carbon::now()->startOfWeek();
                        $endOfWeek = Carbon::now()->endOfWeek();
                        $this->dateFrom = $startOfWeek->format('Y-m-d');
                        $this->dateTo = $endOfWeek->format('Y-m-d');
                    }
                } else {
                    // Derive from selectedDate if available, else current week
                    $base = $this->selectedDate ? Carbon::parse($this->selectedDate) : Carbon::now();
                    $startOfWeek = $base->copy()->startOfWeek();
                    $endOfWeek = $base->copy()->endOfWeek();
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
        $this->updateDateRange(); // Update date range based on view type
        $this->resetPage();
    }

    public function getAttendanceRecordsProperty()
    {
        $query = AttendanceRecord::with('user');
        
        $user = auth()->user();
        
        // If user can only view own attendance, restrict to their records only
        if ($this->canOnlyViewOwn) {
            $query->where('user_id', $user->id);
        } elseif ($this->selectedUser) {
            // Apply user filter only if user has manage_attendance permission
            $query->where('user_id', $this->selectedUser);
        }

        // Apply date range filter
        if ($this->dateFrom) {
            $query->where('attendance_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('attendance_date', '<=', $this->dateTo);
        }

        // Apply search filter (only if user can manage attendance)
        if ($this->search && $this->canManageAttendance) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        } elseif ($this->search && $this->canOnlyViewOwn) {
            // For view-only users, search is not applicable (they only see their own)
            // But we can still apply it to their own records if needed
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
                SUM(late_minutes) as total_late_minutes
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
                SUM(late_minutes) as total_late_minutes
            ')
            ->groupBy('user_id', 'month_number', 'year')
            ->orderBy('year', 'desc')
            ->orderBy('month_number', 'desc');
            }
        } else {
            // Daily view - default
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $paginatedResults = $query->paginate($this->perPage);
        
        // For monthly view with selected user, add missing working days as pending
        if ($this->viewType === 'monthly' && $this->selectedUser && $this->dateFrom && $this->dateTo) {
            $paginatedResults = $this->addMissingDaysToMonthlyView($paginatedResults);
        }
        
        // For daily view with single employee, add missing attendance as pending
        if ($this->viewType === 'daily' && $this->selectedUser && $this->dateFrom && $this->dateTo) {
            $paginatedResults = $this->addMissingAttendanceForSingleEmployee($paginatedResults);
        }
        
        // Note: Pending records are NOT shown for "All Employees" view - only for single employee view
        
        // Recalculate totals using grace period logic if grouped view with all users (only if can manage)
        if (!$this->selectedUser && $this->canManageAttendance && !empty($paginatedResults)) {
            if ($this->viewType === 'monthly') {
                $this->recalculateMonthlyHours($paginatedResults);
            } elseif ($this->viewType === 'weekly') {
                $this->recalculateWeeklyHours($paginatedResults);
            }
        }
        
        return $paginatedResults;
    }
    
    /**
     * Recalculate monthly total hours using grace period logic
     */
    private function recalculateMonthlyHours($paginatedResults)
    {
        foreach ($paginatedResults as $record) {
            // Get all attendance records for this user/month/year combination
            $monthYearQuery = AttendanceRecord::with('user')
                ->where('user_id', $record->user_id)
                ->whereYear('attendance_date', $record->year)
                ->whereMonth('attendance_date', $record->month_number);
            
            // Apply date range filters if set
            if ($this->dateFrom) {
                $monthYearQuery->where('attendance_date', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $monthYearQuery->where('attendance_date', '<=', $this->dateTo);
            }
            
            $monthYearRecords = $monthYearQuery->get();
            
            // Recalculate total and average hours using grace period logic
            $totalHours = 0;
            $totalLateHours = 0;
            $presentCount = 0;
            $workingDays = 0;
            $totalLateMinutes = 0;
            
            foreach ($monthYearRecords as $attendanceRecord) {
                // Exclude absent records, include all others (present, late, wfh, paid_leave)
                if ($attendanceRecord->status !== 'absent') {
                    $hoursWorked = $this->calculateHoursWorkedWithGrace($attendanceRecord);
                    $totalHours += $hoursWorked;
                    $presentCount++;
                    // Count working days (present + late + wfh + paid_leave)
                    if (in_array($attendanceRecord->status, ['present', 'late', 'wfh', 'paid_leave'])) {
                        $workingDays++;
                    }
                    // Sum late minutes directly from database (stored late_minutes field)
                    if ($attendanceRecord->late_minutes) {
                        $totalLateMinutes += $attendanceRecord->late_minutes;
                    }
                }
            }
            
            // Calculate expected working days for this month
            $expectedDays = $this->getWorkingDaysInMonth();
            
            // Get recorded absent days only (no missing records calculation)
            $recordedAbsent = $monthYearRecords->where('status', 'absent')->count();
            
            // Convert late minutes to hours
            $totalLateHours = $totalLateMinutes / 60;
            
            // Update the record with recalculated values
            $record->total_hours_worked = $totalHours;
            $record->total_late_hours = $totalLateHours;
            $record->avg_hours_per_day = $presentCount > 0 ? ($totalHours / $presentCount) : 0;
            $record->total_working_days = $workingDays;
            $record->expected_working_days = $expectedDays;
            $record->absent_days = $recordedAbsent; // Only recorded absent, no missing records
        }
    }
    
    /**
     * Add missing working days as pending records for monthly view
     */
    private function addMissingDaysToMonthlyView($paginatedResults)
    {
        $startDate = Carbon::parse($this->dateFrom);
        $endDate = Carbon::parse($this->dateTo);
        $user = User::find($this->selectedUser);
        
        if (!$user) {
            return $paginatedResults;
        }
        
        // Get all existing attendance dates for this user in the date range (not just current page)
        $existingDates = AttendanceRecord::where('user_id', $this->selectedUser)
            ->whereBetween('attendance_date', [$this->dateFrom, $this->dateTo])
            ->pluck('attendance_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Get all working days (Monday to Friday) in the date range
        $allWorkingDays = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekday()) {
                $dateStr = $currentDate->format('Y-m-d');
                if (!in_array($dateStr, $existingDates)) {
                    // Create a pending record object
                    $pendingRecord = new \stdClass();
                    $pendingRecord->id = null;
                    $pendingRecord->user_id = $user->id;
                    $pendingRecord->attendance_date = $dateStr;
                    $pendingRecord->check_in_time = null;
                    $pendingRecord->check_out_time = null;
                    $pendingRecord->late_minutes = 0;
                    $pendingRecord->hours_worked = 0;
                    $pendingRecord->status = 'pending';
                    $pendingRecord->user = $user;
                    $pendingRecord->is_pending = true;
                    
                    $allWorkingDays[] = $pendingRecord;
                }
            }
            $currentDate->addDay();
        }
        
        // Merge existing records with pending records
        $allRecords = collect($paginatedResults->items())->merge($allWorkingDays);
        
        // Sort by date
        $allRecords = $allRecords->sortBy(function ($record) {
            return Carbon::parse($record->attendance_date)->format('Y-m-d');
        })->values();
        
        // Recreate paginator with merged items
        $paginatedResults->setCollection($allRecords);
        
        return $paginatedResults;
    }
    
    /**
     * Add missing attendance records as pending for single employee in daily view
     */
    private function addMissingAttendanceForSingleEmployee($paginatedResults)
    {
        if (!$this->dateFrom || !$this->dateTo || !$this->selectedUser) {
            return $paginatedResults;
        }
        
        $startDate = Carbon::parse($this->dateFrom);
        $endDate = Carbon::parse($this->dateTo);
        $user = User::find($this->selectedUser);
        
        if (!$user) {
            return $paginatedResults;
        }
        
        // Get all existing attendance dates for this user in the date range
        $existingDates = AttendanceRecord::where('user_id', $this->selectedUser)
            ->whereBetween('attendance_date', [$this->dateFrom, $this->dateTo])
            ->pluck('attendance_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Get all pending records for this single user
        $pendingRecords = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Only show weekdays as pending
            if ($currentDate->isWeekday()) {
                $dateStr = $currentDate->format('Y-m-d');
                if (!in_array($dateStr, $existingDates)) {
                    // Create a pending record object
                    $pendingRecord = new \stdClass();
                    $pendingRecord->id = null;
                    $pendingRecord->user_id = $user->id;
                    $pendingRecord->attendance_date = $dateStr;
                    $pendingRecord->check_in_time = null;
                    $pendingRecord->check_out_time = null;
                    $pendingRecord->late_minutes = 0;
                    $pendingRecord->hours_worked = 0;
                    $pendingRecord->status = 'pending';
                    $pendingRecord->user = $user;
                    $pendingRecord->is_pending = true;
                    
                    $pendingRecords[] = $pendingRecord;
                }
            }
            $currentDate->addDay();
        }
        
        // Merge existing records with pending records
        $allRecords = collect($paginatedResults->items())->merge($pendingRecords);
        
        // Sort by the same field and direction as the query
        $sortField = $this->sortField;
        $sortDirection = $this->sortDirection;
        
        $allRecords = $allRecords->sortBy(function ($record) use ($sortField) {
            if ($sortField === 'attendance_date') {
                return Carbon::parse($record->attendance_date)->format('Y-m-d');
            } elseif ($sortField === 'user.name' || $sortField === 'name') {
                return $record->user->name ?? '';
            }
            return $record->$sortField ?? '';
        }, SORT_REGULAR, $sortDirection === 'desc')->values();
        
        // Recreate paginator with merged items
        $paginatedResults->setCollection($allRecords);
        
        return $paginatedResults;
    }
    
    /**
     * Add missing attendance records as pending for all employees in daily view
     */
    private function addMissingAttendanceForAllEmployees($paginatedResults)
    {
        if (!$this->dateFrom || !$this->dateTo) {
            return $paginatedResults;
        }
        
        $startDate = Carbon::parse($this->dateFrom);
        $endDate = Carbon::parse($this->dateTo);
        $isSingleDay = $startDate->format('Y-m-d') === $endDate->format('Y-m-d');
        
        // Get all employees
        $allUsers = User::query();
        if ($this->search) {
            $allUsers->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        $allUsers = $allUsers->get();
        
        // Get all pending records
        $pendingRecords = [];
        
        if ($isSingleDay) {
            // For single day selection, only show pending records for that specific day
            $dateStr = $startDate->format('Y-m-d');
            
            // Get existing attendance records only for this specific date
            $existingRecords = AttendanceRecord::where('attendance_date', $dateStr)
                ->pluck('user_id')
                ->toArray();
            
            // Create pending records for employees who don't have attendance on this date
            foreach ($allUsers as $user) {
                if (!in_array($user->id, $existingRecords)) {
                    // Create a pending record object
                    $pendingRecord = new \stdClass();
                    $pendingRecord->id = null;
                    $pendingRecord->user_id = $user->id;
                    $pendingRecord->attendance_date = $dateStr;
                    $pendingRecord->check_in_time = null;
                    $pendingRecord->check_out_time = null;
                    $pendingRecord->late_minutes = 0;
                    $pendingRecord->hours_worked = 0;
                    $pendingRecord->status = 'pending';
                    $pendingRecord->user = $user;
                    $pendingRecord->is_pending = true;
                    
                    $pendingRecords[] = $pendingRecord;
                }
            }
        } else {
            // For date range, only show weekdays and process all dates in range
            $existingRecords = AttendanceRecord::whereBetween('attendance_date', [$this->dateFrom, $this->dateTo])
                ->get()
                ->map(function ($record) {
                    return $record->user_id . '_' . Carbon::parse($record->attendance_date)->format('Y-m-d');
                })
                ->toArray();
            
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                if ($currentDate->isWeekday()) {
                    $dateStr = $currentDate->format('Y-m-d');
                    foreach ($allUsers as $user) {
                        $key = $user->id . '_' . $dateStr;
                        if (!in_array($key, $existingRecords)) {
                            // Create a pending record object
                            $pendingRecord = new \stdClass();
                            $pendingRecord->id = null;
                            $pendingRecord->user_id = $user->id;
                            $pendingRecord->attendance_date = $dateStr;
                            $pendingRecord->check_in_time = null;
                            $pendingRecord->check_out_time = null;
                            $pendingRecord->late_minutes = 0;
                            $pendingRecord->hours_worked = 0;
                            $pendingRecord->status = 'pending';
                            $pendingRecord->user = $user;
                            $pendingRecord->is_pending = true;
                            
                            $pendingRecords[] = $pendingRecord;
                        }
                    }
                }
                $currentDate->addDay();
            }
        }
        
        // Merge existing records with pending records
        $allRecords = collect($paginatedResults->items())->merge($pendingRecords);
        
        // Sort by the same field and direction as the query
        $sortField = $this->sortField;
        $sortDirection = $this->sortDirection;
        
        $allRecords = $allRecords->sortBy(function ($record) use ($sortField) {
            if ($sortField === 'attendance_date') {
                return Carbon::parse($record->attendance_date)->format('Y-m-d');
            } elseif ($sortField === 'user.name' || $sortField === 'name') {
                return $record->user->name ?? '';
            }
            return $record->$sortField ?? '';
        }, SORT_REGULAR, $sortDirection === 'desc')->values();
        
        // Recreate paginator with merged items
        $paginatedResults->setCollection($allRecords);
        
        return $paginatedResults;
    }
    
    /**
     * Open WFH modal to enter working hours
     */
    public function openWfhModal($date)
    {
        $this->selectedDateForWFH = $date;
        $user = User::find($this->selectedUser);
        $expectedHours = $user ? $this->calculateExpectedHours($user) : 9;
        $this->maxWfhHours = $expectedHours;
        $this->wfhHours = $expectedHours;
        $this->showWfhModal = true;
    }
    
    /**
     * Close WFH modal
     */
    public function closeWfhModal()
    {
        $this->showWfhModal = false;
        $this->wfhHours = 0;
        $this->selectedDateForWFH = '';
        $this->maxWfhHours = 9;
        $this->resetErrorBag();
    }
    
    /**
     * Save WFH with custom hours
     */
    public function saveWfh()
    {
        // Check if user has permission to manage attendance
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('manage_attendance')) {
            session()->flash('error', 'You do not have permission to manage attendance records.');
            return;
        }
        
        // Validate hours
        $this->validate([
            'wfhHours' => ['required', 'numeric', 'min:0', 'max:' . $this->maxWfhHours],
        ], [
            'wfhHours.required' => 'Please enter working hours.',
            'wfhHours.numeric' => 'Working hours must be a number.',
            'wfhHours.min' => 'Working hours cannot be negative.',
            'wfhHours.max' => 'Working hours cannot exceed ' . number_format($this->maxWfhHours, 1) . ' hours.',
        ]);
        
        $attendanceDate = Carbon::parse($this->selectedDateForWFH)->format('Y-m-d');
        
        // Find existing record or create new one
        $record = AttendanceRecord::firstOrNew([
            'user_id' => $this->selectedUser,
            'attendance_date' => $attendanceDate,
        ]);
        
        $record->status = 'wfh';
        $record->hours_worked = $this->wfhHours;
        $record->check_in_time = null;
        $record->check_out_time = null;
        $record->late_minutes = 0;
        $record->save();
        
        $this->closeWfhModal();
        session()->flash('message', 'WFH attendance saved successfully.');
        $this->resetPage();
    }
    
    /**
     * Update attendance status for pending records (Absent and Paid Leave)
     */
    public function updateAttendanceStatus($date, $status)
    {
        // Check if user has permission to manage attendance
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('manage_attendance')) {
            session()->flash('error', 'You do not have permission to manage attendance records.');
            return;
        }
        
        $attendanceDate = Carbon::parse($date)->format('Y-m-d');
        
        // Find existing record or create new one
        $record = AttendanceRecord::firstOrNew([
            'user_id' => $this->selectedUser,
            'attendance_date' => $attendanceDate,
        ]);
        
        // Update status based on selection
        $record->status = $status;
        
        // Set hours worked based on status
        if ($status === 'absent') {
            $record->hours_worked = 0;
            $record->check_in_time = null;
            $record->check_out_time = null;
            $record->late_minutes = 0;
        } elseif ($status === 'paid_leave') {
            // Paid Leave - full expected working hours
            $user = User::find($this->selectedUser);
            $record->hours_worked = $user ? $this->calculateExpectedHours($user) : 9;
            $record->check_in_time = null;
            $record->check_out_time = null;
            $record->late_minutes = 0;
        }
        
        $record->save();
        
        session()->flash('message', 'Attendance status updated successfully.');
        
        // Reset pagination to refresh the view
        $this->resetPage();
    }
    
    /**
     * Open breakdown modal and calculate wages breakdown
     */
    public function openBreakdownModal()
    {
        $currentUser = auth()->user();
        
        // Check if user has permission to view attendance
        if (!$currentUser->isSuperAdmin() && !$currentUser->hasPermission('view_attendance') && !$currentUser->hasPermission('manage_attendance')) {
            session()->flash('error', 'You do not have permission to view breakdown.');
            return;
        }
        
        if (!$this->selectedUser || $this->viewType !== 'monthly') {
            session()->flash('error', 'Please select an employee and ensure you are in monthly view.');
            return;
        }
        
        // If user can only view own attendance, ensure they can only see their own breakdown
        if ($this->canOnlyViewOwn && $this->selectedUser != $currentUser->id) {
            session()->flash('error', 'You can only view your own attendance breakdown.');
            // Force select their own ID
            $this->selectedUser = $currentUser->id;
            return;
        }
        
        $user = User::find($this->selectedUser);
        if (!$user || !$user->monthly_salary) {
            session()->flash('error', 'Employee salary is not set. Please set the monthly salary first.');
            return;
        }
        
        // Reset adjustments for view-only users (they can't adjust)
        if ($this->canOnlyViewOwn) {
            $this->manualBonus = 0;
            $this->noDeduction = false;
        }
        
        $this->calculateBreakdown();
        $this->showBreakdownModal = true;
    }
    
    /**
     * Close breakdown modal
     */
    public function closeBreakdownModal()
    {
        $this->showBreakdownModal = false;
        $this->breakdownData = [];
        $this->manualBonus = 0;
        $this->noDeduction = false;
        $this->resetErrorBag();
    }

    public function openEditModal($recordId)
    {
        $record = AttendanceRecord::with('user')->find($recordId);
        if (!$record) {
            session()->flash('error', 'Attendance record not found.');
            return;
        }
        $this->editRecordId = $record->id;
        $this->editDate = Carbon::parse($record->attendance_date)->format('Y-m-d');
        $this->editStatus = $record->status;
        $this->editCheckIn = $record->check_in_time ? Carbon::parse($record->check_in_time)->format('H:i') : '';
        $this->editCheckOut = $record->check_out_time ? Carbon::parse($record->check_out_time)->format('H:i') : '';
        $this->editHoursWorked = $record->hours_worked ?? 0;
        $this->editLateMinutes = $record->late_minutes ?? 0;
        $this->editLateHoursReadable = number_format(($this->editLateMinutes / 60), 1) . 'h';
        $this->showEditModal = true;
    }
    
    public function openEditModalByDate($date, $userId)
    {
        // Store userId for saving
        $this->editUserId = $userId;
        
        // Try to find existing record first
        $record = AttendanceRecord::with('user')
            ->where('attendance_date', $date)
            ->where('user_id', $userId)
            ->first();
        
        if ($record) {
            // If record exists, use existing openEditModal logic
            $this->editRecordId = $record->id;
            $this->editDate = Carbon::parse($record->attendance_date)->format('Y-m-d');
            $this->editStatus = $record->status;
            $this->editCheckIn = $record->check_in_time ? Carbon::parse($record->check_in_time)->format('H:i') : '';
            $this->editCheckOut = $record->check_out_time ? Carbon::parse($record->check_out_time)->format('H:i') : '';
            $this->editHoursWorked = $record->hours_worked ?? 0;
            $this->editLateMinutes = $record->late_minutes ?? 0;
            $this->editLateHoursReadable = number_format(($this->editLateMinutes / 60), 1) . 'h';
        } else {
            // For pending records, initialize with empty values
            $this->editRecordId = null;
            $this->editDate = Carbon::parse($date)->format('Y-m-d');
            $this->editStatus = 'pending';
            $this->editCheckIn = '';
            $this->editCheckOut = '';
            $this->editHoursWorked = 0;
            $this->editLateMinutes = 0;
            $this->editLateHoursReadable = '0.0h';
        }
        
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editRecordId = null;
        $this->editUserId = null;
        $this->editDate = '';
        $this->editStatus = '';
        $this->editCheckIn = '';
        $this->editCheckOut = '';
        $this->editHoursWorked = 0;
        $this->editLateMinutes = 0;
        $this->editLateHoursReadable = '0.0h';
        $this->resetErrorBag();
    }

    public function saveAttendanceEdit()
    {
        // Check if user has permission to manage attendance
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('manage_attendance')) {
            session()->flash('error', 'You do not have permission to manage attendance records.');
            return;
        }
        
        $this->validate([
            'editDate' => ['required', 'date'],
            'editStatus' => ['required', 'in:present,late,absent,wfh,paid_leave,holiday,pending'],
            'editHoursWorked' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'editLateMinutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ]);

        // Get user_id from the record being edited if selectedUser is not set
        $userId = $this->selectedUser;
        if (!$userId && $this->editRecordId) {
            $existingRecord = AttendanceRecord::find($this->editRecordId);
            if ($existingRecord) {
                $userId = $existingRecord->user_id;
            }
        }
        
        // For pending records opened via openEditModalByDate, use editUserId
        if (!$userId && $this->editUserId) {
            $userId = $this->editUserId;
        }
        
        if (!$userId) {
            session()->flash('error', 'Unable to determine user. Please select an employee first or ensure the record exists.');
            return;
        }

        // Always upsert by (user_id, attendance_date) to ensure pending placeholders are replaced
        $targetDate = Carbon::parse($this->editDate)->format('Y-m-d');
        $record = AttendanceRecord::updateOrCreate(
            [
                'user_id' => $userId,
                'attendance_date' => $targetDate,
            ],
            []
        );
        // Reload with relation for expected hours calculation
        $record->load('user');

        $record->attendance_date = $targetDate;
        $record->status = $this->editStatus;
        $record->check_in_time = $this->editCheckIn ? Carbon::parse($this->editCheckIn)->format('H:i:s') : null;
        $record->check_out_time = $this->editCheckOut ? Carbon::parse($this->editCheckOut)->format('H:i:s') : null;

        // Normalize values based on status
        if ($this->editStatus === 'absent') {
            $record->hours_worked = 0;
            $record->check_in_time = null;
            $record->check_out_time = null;
            $record->late_minutes = 0;
        } elseif ($this->editStatus === 'holiday') {
            // Holiday - 0 hours worked, no times, no late
            $record->hours_worked = 0;
            $record->check_in_time = null;
            $record->check_out_time = null;
            $record->late_minutes = 0;
        } elseif ($this->editStatus === 'paid_leave') {
            // Paid Leave -> full expected hours, no times, no late
            $user = $record->user ?: User::find($record->user_id);
            $expected = $user ? $this->calculateExpectedHours($user) : 9;
            $record->hours_worked = $expected;
            $record->check_in_time = null;
            $record->check_out_time = null;
            $record->late_minutes = 0;
        } else {
            // Present/Late/WFH: compute hours if both times provided
            if ($record->check_in_time && $record->check_out_time) {
                $in = Carbon::createFromFormat('H:i:s', $record->check_in_time);
                $out = Carbon::createFromFormat('H:i:s', $record->check_out_time);
                $minutes = max(0, $out->diffInMinutes($in));
                // Subtract 30 minutes lunch if shift spans typical workday
                $hours = max(0, ($minutes / 60) - 0.5);
                $record->hours_worked = round($hours, 2);
            } else {
                // Fallback to provided hours
                $record->hours_worked = $this->editHoursWorked ?? 0;
            }
            // Always use editLateMinutes if it's been set (either manually or auto-calculated)
            // The user can see and edit this value in the form, so use what they see
            if ($this->editLateMinutes !== null && $this->editLateMinutes !== '') {
                $record->late_minutes = (int) $this->editLateMinutes;
            } elseif ($record->check_in_time) {
                // Auto compute late minutes from check-in vs user's expected check_in_time
                $user = $record->user ?: User::find($record->user_id);
                // Use check_in_time first (from users table), then shift_start, then default to 09:00
                if ($user && $user->check_in_time) {
                    $expectedStart = Carbon::parse($user->check_in_time);
                } elseif ($user && $user->shift_start) {
                    $expectedStart = Carbon::parse($user->shift_start);
                } else {
                    $expectedStart = Carbon::parse('09:00');
                }
                
                $ci = Carbon::createFromFormat('H:i:s', $record->check_in_time);
                // Ignore seconds when comparing - use start of minute
                $ciMinute = $ci->copy()->startOfMinute();
                $expectedMinute = $expectedStart->copy()->startOfMinute();
                
                // If same minute (ignoring seconds), no late minutes
                if ($ciMinute->eq($expectedMinute)) {
                    $record->late_minutes = 0;
                } else {
                    $record->late_minutes = $ciMinute->greaterThan($expectedMinute) ? $ciMinute->diffInMinutes($expectedMinute) : 0;
                }
            } else {
                $record->late_minutes = $this->editLateMinutes ?? 0;
            }
        }
        $record->save();

        $this->closeEditModal();
        session()->flash('message', 'Attendance updated successfully.');
        $this->resetPage();
    }

    public function updatedEditCheckIn($value)
    {
        // Auto-calculate late minutes based on expected check-in (user check_in_time or shift_start or 09:00)
        $user = null;
        
        if ($this->editRecordId) {
            $record = AttendanceRecord::with('user')->find($this->editRecordId);
            $user = $record ? $record->user : null;
        } elseif ($this->editUserId) {
            $user = User::find($this->editUserId);
        }
        
        if (!$user || empty($value)) {
            $this->editLateMinutes = 0;
            $this->editLateHoursReadable = '0.0h';
            return;
        }
        
        // Use check_in_time first (from users table), then shift_start, then default to 09:00
        if ($user->check_in_time) {
            $expected = Carbon::parse($user->check_in_time);
        } elseif ($user->shift_start) {
            $expected = Carbon::parse($user->shift_start);
        } else {
            $expected = Carbon::parse('09:00');
        }
        
        $checkIn = Carbon::parse($value);
        // Ignore seconds - use start of minute for comparison
        $checkInMinute = $checkIn->copy()->startOfMinute();
        $expectedMinute = $expected->copy()->startOfMinute();
        
        // If same minute (ignoring seconds), zero late
        if ($checkInMinute->eq($expectedMinute)) {
            $this->editLateMinutes = 0;
            $this->editLateHoursReadable = '0.0h';
            return;
        }
        $diff = $checkInMinute->greaterThan($expectedMinute) ? $checkInMinute->diffInMinutes($expectedMinute) : 0;
        $this->editLateMinutes = $diff;
        $this->editLateHoursReadable = number_format(($diff / 60), 1) . 'h';
    }

    public function sendBreakdownEmail()
    {
        $currentUser = auth()->user();
        
        // Check if user has permission to view attendance
        if (!$currentUser->isSuperAdmin() && !$currentUser->hasPermission('view_attendance') && !$currentUser->hasPermission('manage_attendance')) {
            session()->flash('error', 'You do not have permission to send breakdown email.');
            return;
        }
        
        // If user can only view own attendance, ensure they can only send email for their own breakdown
        if ($this->canOnlyViewOwn && $this->selectedUser != $currentUser->id) {
            session()->flash('error', 'You can only send breakdown email for your own attendance.');
            return;
        }
        
        if (empty($this->breakdownData) || !$this->selectedUser) {
            session()->flash('error', 'Please open the breakdown first.');
            return;
        }
        $user = User::find($this->selectedUser);
        if (!$user || !$user->email) {
            session()->flash('error', 'User email not found.');
            return;
        }

        $data = $this->breakdownData;
        $data['user_email'] = $user->email;
        Mail::to($user->email)->send(new MonthlySalarySummary($data));
        session()->flash('message', 'Salary summary email sent to ' . $user->email . '.');
    }
    
    /**
     * Open holiday modal to mark a date range as holiday for all employees
     */
    public function openHolidayModal()
    {
        // Set default dates to currently selected date or today
        $defaultDate = $this->selectedDate ?: Carbon::now()->format('Y-m-d');
        $this->holidayStartDate = $defaultDate;
        $this->holidayEndDate = $defaultDate;
        $this->showHolidayModal = true;
    }
    
    /**
     * Close holiday modal
     */
    public function closeHolidayModal()
    {
        $this->showHolidayModal = false;
        $this->holidayStartDate = '';
        $this->holidayEndDate = '';
        $this->resetErrorBag();
    }
    
    /**
     * Mark a date range as holiday for all employees
     */
    public function markHolidayForAll()
    {
        // Check if user has permission to manage attendance
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('manage_attendance')) {
            session()->flash('error', 'You do not have permission to manage attendance records.');
            return;
        }
        
        $this->validate([
            'holidayStartDate' => ['required', 'date'],
            'holidayEndDate' => ['required', 'date', 'after_or_equal:holidayStartDate'],
        ], [
            'holidayEndDate.after_or_equal' => 'The end date must be after or equal to the start date.',
        ]);
        
        $startDate = Carbon::parse($this->holidayStartDate);
        $endDate = Carbon::parse($this->holidayEndDate);
        
        // Get all employees
        $allUsers = User::query();
        if ($this->search) {
            $allUsers->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        $allUsers = $allUsers->get();
        
        $totalRecords = 0;
        $currentDate = $startDate->copy();
        
        // Loop through each date in the range
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            
            foreach ($allUsers as $user) {
                // Update or create attendance record with holiday status
                $record = AttendanceRecord::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'attendance_date' => $dateStr,
                    ],
                    [
                        'status' => 'holiday',
                        'hours_worked' => 0,
                        'check_in_time' => null,
                        'check_out_time' => null,
                        'late_minutes' => 0,
                    ]
                );
                $totalRecords++;
            }
            
            $currentDate->addDay();
        }
        
        $dateRange = $startDate->format('M d, Y');
        if ($startDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
            $dateRange .= ' to ' . $endDate->format('M d, Y');
        }
        
        $daysCount = $startDate->diffInDays($endDate) + 1;
        $employeeCount = $allUsers->count();
        
        session()->flash('message', "Holiday marked for {$employeeCount} employee(s) for {$daysCount} day(s) ({$dateRange}). Total records: {$totalRecords}");
        
        $this->closeHolidayModal();
        $this->resetPage();
    }
    
    /**
     * Open import modal
     */
    public function openImportModal()
    {
        // Check if user has permission to manage attendance
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('manage_attendance')) {
            session()->flash('error', 'You do not have permission to import attendance records.');
            return;
        }
        
        $this->showImportModal = true;
        $this->importFile = null;
        $this->importError = '';
        $this->importSuccess = '';
        $this->resetErrorBag();
    }
    
    /**
     * Close import modal
     */
    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importError = '';
        $this->importSuccess = '';
        $this->resetErrorBag();
    }
    
    /**
     * Import attendance from CSV/XLS file
     */
    public function importAttendance()
    {
        // Check if user has permission to manage attendance
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('manage_attendance')) {
            $this->importError = 'You do not have permission to import attendance records.';
            return;
        }
        
        // Validate file
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:10240'], // Max 10MB
        ], [
            'importFile.required' => 'Please select a file to import.',
            'importFile.file' => 'The uploaded file is invalid.',
            'importFile.mimes' => 'The file must be a CSV or Excel file (CSV, XLS, XLSX).',
            'importFile.max' => 'The file size must not exceed 10MB.',
        ]);
        
        if (!$this->importFile) {
            $this->importError = 'No file selected.';
            return;
        }
        
        try {
            // Read file based on extension
            $extension = strtolower($this->importFile->getClientOriginalExtension());
            $records = [];
            
            // Get the file path - Livewire stores files temporarily
            $filePath = $this->importFile->getRealPath();
            if (!$filePath || !file_exists($filePath)) {
                // Try alternative path for Livewire temporary files
                $filePath = $this->importFile->path();
            }
            
            if (!$filePath || !file_exists($filePath)) {
                $this->importError = 'Unable to access uploaded file. Please try again.';
                Log::error("Import Attendance: File path not accessible", [
                    'real_path' => $this->importFile->getRealPath(),
                    'path' => $this->importFile->path(),
                    'extension' => $extension
                ]);
                return;
            }
            
            Log::info("Import Attendance: Reading file", [
                'file_path' => $filePath,
                'extension' => $extension,
                'size' => filesize($filePath)
            ]);
            
            if ($extension === 'csv') {
                $records = $this->readCsvFile($filePath);
            } elseif (in_array($extension, ['xls', 'xlsx'])) {
                $records = $this->readExcelFile($filePath, $extension);
            } else {
                $this->importError = 'Unsupported file format. Please upload CSV or Excel files.';
                return;
            }
            
            Log::info("Import Attendance: Records read from file", [
                'count' => count($records)
            ]);
            
            if (empty($records)) {
                $this->importError = 'No valid records found in the file. Please check the file format.';
                return;
            }
            
            // Process and save attendance records
            $savedCount = $this->saveAttendanceToDatabase($records);
            
            Log::info("Import Attendance: Records saved", [
                'saved_count' => $savedCount,
                'total_records' => count($records)
            ]);
            
            if ($savedCount > 0) {
                $this->importSuccess = "Successfully imported {$savedCount} attendance record(s).";
                session()->flash('message', "Successfully imported {$savedCount} attendance record(s).");
            } else {
                $this->importError = 'No records were saved. Please check if users exist with matching device_user_id.';
            }
            $this->importFile = null;
            
            // Reset page to refresh the view
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->importError = 'Error importing file: ' . $e->getMessage();
            Log::error("Import Attendance Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
    
    /**
     * Read CSV file and return array of records
     */
    private function readCsvFile($filePath)
    {
        $records = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Unable to open CSV file.');
        }
        
        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new \Exception('CSV file is empty or invalid.');
        }
        
        // Normalize header (lowercase, trim)
        $header = array_map(function($h) {
            return strtolower(trim($h));
        }, $header);
        
        // Find required columns - check for "No." first (most common in export format)
        $deviceUserIdIndex = array_search('no.', $header);
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('no', $header);
        }
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('device_user_id', $header);
        }
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('id', $header);
        }
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('user_id', $header);
        }
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('device_uid', $header);
        }
        
        // Find timestamp column - check for "Date/Time" first (most common in export format)
        $timestampIndex = array_search('date/time', $header);
        if ($timestampIndex === false) {
            $timestampIndex = array_search('date_time', $header);
        }
        if ($timestampIndex === false) {
            $timestampIndex = array_search('datetime', $header);
        }
        if ($timestampIndex === false) {
            $timestampIndex = array_search('timestamp', $header);
        }
        if ($timestampIndex === false) {
            $timestampIndex = array_search('date', $header);
        }
        
        if ($deviceUserIdIndex === false || $timestampIndex === false) {
            fclose($handle);
            $missingColumns = [];
            if ($deviceUserIdIndex === false) {
                $missingColumns[] = '"No." or "device_user_id"';
            }
            if ($timestampIndex === false) {
                $missingColumns[] = '"Date/Time" or "timestamp"';
            }
            throw new \Exception('CSV file must contain ' . implode(' and ', $missingColumns) . ' column(s).');
        }
        
        // Read data rows
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            if (count($row) < max($deviceUserIdIndex, $timestampIndex) + 1) {
                continue; // Skip incomplete rows
            }
            
            $deviceUserId = trim($row[$deviceUserIdIndex]);
            $timestamp = trim($row[$timestampIndex]);
            
            if (empty($deviceUserId) || empty($timestamp)) {
                continue; // Skip empty rows
            }
            
            // Parse timestamp - try multiple formats including MM/DD/YYYY HH:MM
            try {
                // Try MM/DD/YYYY HH:MM format first (common in export files)
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/', $timestamp, $matches)) {
                    $month = $matches[1];
                    $day = $matches[2];
                    $year = $matches[3];
                    $hour = $matches[4];
                    $minute = $matches[5];
                    $parsedDate = Carbon::create($year, $month, $day, $hour, $minute, 0);
                } else {
                    // Try standard Carbon parsing for other formats
                    $parsedDate = Carbon::parse($timestamp);
                }
                
                $records[] = [
                    'id' => $deviceUserId,
                    'timestamp' => $parsedDate->format('Y-m-d H:i:s'),
                ];
            } catch (\Exception $e) {
                Log::warning("Skipping invalid timestamp in CSV row {$rowNumber}: {$timestamp} - " . $e->getMessage());
                continue;
            }
        }
        
        fclose($handle);
        return $records;
    }
    
    /**
     * Read Excel file and return array of records
     */
    private function readExcelFile($filePath, $extension)
    {
        // Check if PhpSpreadsheet is available
        if (!class_exists(IOFactory::class)) {
            throw new \Exception('Excel files require PhpSpreadsheet. Please install it using: composer require phpoffice/phpspreadsheet');
        }
        
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $records = [];
        
        // Get header row (first row)
        $headerRow = $worksheet->getRowIterator(1, 1)->current();
        $header = [];
        foreach ($headerRow->getCellIterator() as $cell) {
            $header[] = strtolower(trim($cell->getValue()));
        }
        
        // Find required columns - check for "No." first (most common in export format)
        $deviceUserIdIndex = array_search('no.', $header);
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('no', $header);
        }
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('device_user_id', $header);
        }
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('id', $header);
        }
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('user_id', $header);
        }
        if ($deviceUserIdIndex === false) {
            $deviceUserIdIndex = array_search('device_uid', $header);
        }
        
        // Find timestamp column - check for "Date/Time" first (most common in export format)
        $timestampIndex = array_search('date/time', $header);
        if ($timestampIndex === false) {
            $timestampIndex = array_search('date_time', $header);
        }
        if ($timestampIndex === false) {
            $timestampIndex = array_search('datetime', $header);
        }
        if ($timestampIndex === false) {
            $timestampIndex = array_search('timestamp', $header);
        }
        if ($timestampIndex === false) {
            $timestampIndex = array_search('date', $header);
        }
        
        if ($deviceUserIdIndex === false || $timestampIndex === false) {
            $missingColumns = [];
            if ($deviceUserIdIndex === false) {
                $missingColumns[] = '"No." or "device_user_id"';
            }
            if ($timestampIndex === false) {
                $missingColumns[] = '"Date/Time" or "timestamp"';
            }
            throw new \Exception('Excel file must contain ' . implode(' and ', $missingColumns) . ' column(s).');
        }
        
        // Read data rows (starting from row 2)
        $rowIterator = $worksheet->getRowIterator(2);
        $rowNumber = 1;
        
        foreach ($rowIterator as $row) {
            $rowNumber++;
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            
            if (count($rowData) < max($deviceUserIdIndex, $timestampIndex) + 1) {
                continue; // Skip incomplete rows
            }
            
            $deviceUserId = trim($rowData[$deviceUserIdIndex] ?? '');
            $timestamp = trim($rowData[$timestampIndex] ?? '');
            
            if (empty($deviceUserId) || empty($timestamp)) {
                continue; // Skip empty rows
            }
            
            // Parse timestamp - try multiple formats including MM/DD/YYYY HH:MM
            try {
                $timestampStr = is_object($timestamp) ? $timestamp->getFormattedValue() : (string)$timestamp;
                
                // Try MM/DD/YYYY HH:MM format first (common in export files)
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/', $timestampStr, $matches)) {
                    $month = $matches[1];
                    $day = $matches[2];
                    $year = $matches[3];
                    $hour = $matches[4];
                    $minute = $matches[5];
                    $parsedDate = Carbon::create($year, $month, $day, $hour, $minute, 0);
                } else {
                    // Try standard Carbon parsing for other formats
                    $parsedDate = Carbon::parse($timestampStr);
                }
                
                $records[] = [
                    'id' => $deviceUserId,
                    'timestamp' => $parsedDate->format('Y-m-d H:i:s'),
                ];
            } catch (\Exception $e) {
                Log::warning("Skipping invalid timestamp in Excel row {$rowNumber}: {$timestampStr} - " . $e->getMessage());
                continue;
            }
        }
        
        return $records;
    }
    
    /**
     * Save attendance records to database (same logic as FetchDailyAttendance)
     */
    private function saveAttendanceToDatabase(array $attendance): int
    {
        $savedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        foreach ($attendance as $index => $record) {
            try {
                Log::info("Import Attendance: Record: " . json_encode($record));
                $deviceUserId = $record['id'];
                
                // Find user by device_user_id
                $user = User::where('device_user_id', $deviceUserId)->first();
                
                if (!$user) {
                    // Try to find by zkteco_uid as fallback
                    $user = User::where('zkteco_uid', $deviceUserId)->first();
                }
                
                if (!$user) {
                    // Log unmatched records for debugging
                    Log::warning("Import Attendance: No user found for device_user_id: {$deviceUserId}");
                    $skippedCount++;
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
                        // For check-in, always update with the latest/earliest time
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
                    
                    // Always update the record (even if just device_uid changes)
                    $result = $existingRecord->update($updateData);
                    if ($result) {
                        $savedCount++;
                        // Refresh the model to get updated values
                        $existingRecord->refresh();
                        Log::info("Import Attendance: Successfully updated record for user {$user->id} on {$recordDate}", [
                            'update_data' => $updateData,
                            'record_id' => $existingRecord->id,
                            'check_in_time' => $existingRecord->check_in_time,
                            'check_out_time' => $existingRecord->check_out_time,
                            'status' => $existingRecord->status
                        ]);
                    } else {
                        Log::error("Import Attendance: Failed to update record for user {$user->id} on {$recordDate}", [
                            'update_data' => $updateData,
                            'record_id' => $existingRecord->id
                        ]);
                    }
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
                
                $newRecord = AttendanceRecord::create($recordData);
                $savedCount++;
                Log::debug("Import Attendance: Created record for user {$user->id} on {$recordDate}", [
                    'record_id' => $newRecord->id,
                    'record_data' => $recordData
                ]);
            }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Import Attendance: Error saving record #{$index}", [
                    'device_user_id' => $deviceUserId ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }
        
        if ($skippedCount > 0) {
            Log::warning("Import Attendance: Skipped {$skippedCount} record(s) due to missing users");
        }
        if ($errorCount > 0) {
            Log::error("Import Attendance: Failed to save {$errorCount} record(s) due to errors");
        }
        
        return $savedCount;
    }
    
    /**
     * Calculate wages breakdown
     */
    private function calculateBreakdown()
    {
        $user = User::find($this->selectedUser);
        if (!$user || !$user->monthly_salary) {
            return;
        }
        
        // Get attendance records for the selected month
        $attendanceRecords = AttendanceRecord::where('user_id', $this->selectedUser)
            ->whereBetween('attendance_date', [$this->dateFrom, $this->dateTo])
            ->orderBy('attendance_date', 'asc')
            ->get();
        
        // Calculate expected values
        $expectedWorkingDays = $this->getWorkingDaysInMonth();
        $expectedHoursPerDay = $user ? $this->calculateExpectedHours($user) : 9;
        $monthlySalary = $user->monthly_salary;
        $dailyWage = $expectedWorkingDays > 0 ? $monthlySalary / $expectedWorkingDays : 0;
        $hourlyWage = $expectedHoursPerDay > 0 ? $dailyWage / $expectedHoursPerDay : 0;
        
        // Build breakdown data
        $breakdown = [];
        $totalHoursWorked = 0;
        $totalWagesEarned = 0;
        // Recorded absences (explicitly marked as 'absent')
        $recordedAbsentDays = $attendanceRecords->where('status', 'absent')->count();
        $absentDeduction = $recordedAbsentDays * $dailyWage;
        
        foreach ($attendanceRecords as $record) {
            $actualHoursWorked = $this->calculateHoursWorkedWithGrace($record);
            $hoursWorked = $actualHoursWorked;
            
            // If no deduction is enabled, pay for full expected hours even if worked less
            if ($this->noDeduction && $record->status !== 'absent' && $record->status !== 'holiday' && $record->status !== 'pending') {
                // Pay for full expected hours even if they worked less
                $hoursWorked = $expectedHoursPerDay;
            }
            
            $wagesEarned = $hoursWorked * $hourlyWage;
            
            $breakdown[] = [
                'date' => Carbon::parse($record->attendance_date)->format('Y-m-d'),
                'day' => Carbon::parse($record->attendance_date)->format('l'),
                'status' => $record->status,
                'hours_worked' => $actualHoursWorked, // Show actual hours worked
                'wages_earned' => $wagesEarned, // But pay based on no deduction logic
            ];
            
            $totalHoursWorked += $actualHoursWorked; // Track actual hours
            $totalWagesEarned += $wagesEarned; // But wages are calculated with no deduction logic
        }
        
        // Add missing days as absent (0 wages)
        $startDate = Carbon::parse($this->dateFrom);
        $endDate = Carbon::parse($this->dateTo);
        $existingDates = $attendanceRecords->pluck('attendance_date')->map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })->toArray();
        
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekday()) {
                $dateStr = $currentDate->format('Y-m-d');
                if (!in_array($dateStr, $existingDates)) {
                    $breakdown[] = [
                        'date' => $dateStr,
                        'day' => $currentDate->format('l'),
                        'status' => 'pending',
                        'hours_worked' => 0,
                        'wages_earned' => 0,
                    ];
                }
            }
            $currentDate->addDay();
        }
        
        // Sort by date
        usort($breakdown, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        // Calculate short late penalties
        $shortLateCount = $this->countShortLates($attendanceRecords);
        $fullDayPenaltyCount = intdiv($shortLateCount, 3); // every 3 short lates -> 1 day wage
        $remainingShortLates = $shortLateCount % 3;        // remaining short lates -> 200 each
        $shortLatePenalty = ($fullDayPenaltyCount * $dailyWage) + ($remainingShortLates * 200);

        // Preserve gross wages before penalty then apply penalty
        $grossWages = $totalWagesEarned;
        
        // Apply deductions only if no deduction option is not checked
        $actualShortLatePenalty = $this->noDeduction ? 0 : $shortLatePenalty;
        $actualAbsentDeduction = $this->noDeduction ? 0 : $absentDeduction;
        $totalWagesEarned = max(0, $totalWagesEarned - $actualShortLatePenalty - $actualAbsentDeduction);

        // Punctuality bonus: no late, no absent, and no missing working days in month
        $hasAnyLate = $attendanceRecords->contains(function ($r) {
            return isset($r->status) && $r->status === 'late';
        });
        $hasAnyAbsent = $attendanceRecords->contains(function ($r) {
            return isset($r->status) && $r->status === 'absent';
        });
        $missingWorkingDays = $this->countMissingWorkingDays();
        // Exclude users on probation from bonus
        $isOnProbation = ($user->employment_status === 'probation') || ($user->probation_end_at && Carbon::parse($user->probation_end_at)->isFuture());
        $punctualBonus = (!$isOnProbation && !$hasAnyLate && !$hasAnyAbsent && $missingWorkingDays === 0) ? 2500 : 0;
        
        // Add manual bonus and punctual bonus (ensure manualBonus is numeric)
        // Handle empty string, null, or non-numeric values
        $manualBonusAmount = 0;
        if (!empty($this->manualBonus)) {
            $manualBonusAmount = is_numeric($this->manualBonus) ? max(0, (float)$this->manualBonus) : 0;
        }
        $totalWagesEarned = $totalWagesEarned + $punctualBonus + $manualBonusAmount;

        $this->breakdownData = [
            'user_name' => $user->name,
            'month' => Carbon::parse($this->dateFrom)->format('F Y'),
            'monthly_salary' => $monthlySalary,
            'expected_working_days' => $expectedWorkingDays,
            'expected_hours_per_day' => $expectedHoursPerDay,
            'daily_wage' => $dailyWage,
            'hourly_wage' => $hourlyWage,
            'breakdown' => $breakdown,
            'total_hours_worked' => $totalHoursWorked,
            'total_wages_earned' => $totalWagesEarned,
            'expected_hours' => $expectedWorkingDays * $expectedHoursPerDay,
            'expected_wages' => $monthlySalary,
            // Short late penalty details
            'short_late_count' => $shortLateCount,
            'full_day_penalty_days' => $fullDayPenaltyCount,
            'short_late_penalty' => $shortLatePenalty,
            'actual_short_late_penalty' => $actualShortLatePenalty,
            'gross_wages' => $grossWages,
            // Punctuality bonus details
            'punctual_bonus' => $punctualBonus,
            'manual_bonus' => $manualBonusAmount,
            'total_bonus' => $punctualBonus + $manualBonusAmount,
            'final_wages' => $totalWagesEarned,
            // Absent details (informational)
            'absent_days' => $recordedAbsentDays,
            'absent_deduction' => $absentDeduction,
            'actual_absent_deduction' => $actualAbsentDeduction,
            'no_deduction' => $this->noDeduction,
        ];
    }
    
    /**
     * Recalculate breakdown when bonus or deduction option changes
     */
    public function updatedManualBonus($value)
    {
        // Ensure manualBonus is numeric, handle empty string
        if ($value === '' || $value === null) {
            $this->manualBonus = 0;
        } else {
            $this->manualBonus = is_numeric($value) ? (float)$value : 0;
        }
        
        // Only recalculate if modal is open and has data
        if ($this->showBreakdownModal && !empty($this->breakdownData)) {
            $this->calculateBreakdown();
        }
    }
    
    public function updatedNoDeduction()
    {
        if ($this->showBreakdownModal && !empty($this->breakdownData)) {
            $this->calculateBreakdown();
        }
    }
    
    /**
     * Validate manual bonus on blur (when user finishes editing)
     */
    public function validateManualBonus()
    {
        if ($this->manualBonus === '' || $this->manualBonus === null) {
            $this->manualBonus = 0;
        } elseif (!is_numeric($this->manualBonus)) {
            $this->manualBonus = 0;
            session()->flash('error', 'Bonus must be a valid number.');
        } elseif ($this->manualBonus < 0) {
            $this->manualBonus = 0;
            session()->flash('error', 'Bonus cannot be negative.');
        }
        
        if ($this->showBreakdownModal && !empty($this->breakdownData)) {
            $this->calculateBreakdown();
        }
    }
    
    /**
     * Recalculate weekly total hours using grace period logic
     */
    private function recalculateWeeklyHours($paginatedResults)
    {
        foreach ($paginatedResults as $record) {
            // Get all attendance records for this user/week/year combination
            $weekYearQuery = AttendanceRecord::with('user')
                ->where('user_id', $record->user_id)
                ->whereRaw('YEAR(attendance_date) = ?', [$record->year])
                ->whereRaw('WEEK(attendance_date) = ?', [$record->week_number]);
            
            // Apply date range filters if set
            if ($this->dateFrom) {
                $weekYearQuery->where('attendance_date', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $weekYearQuery->where('attendance_date', '<=', $this->dateTo);
            }
            
            $weekYearRecords = $weekYearQuery->get();
            
            // Recalculate total and average hours using grace period logic
            $totalHours = 0;
            $totalLateMinutes = 0;
            $presentCount = 0;
            
            foreach ($weekYearRecords as $attendanceRecord) {
                if ($attendanceRecord->status !== 'absent' && $attendanceRecord->check_in_time) {
                    $hoursWorked = $this->calculateHoursWorkedWithGrace($attendanceRecord);
                    $totalHours += $hoursWorked;
                    $presentCount++;
                }
                
                // Sum late minutes from database for this week (use stored late_minutes)
                if ($attendanceRecord->late_minutes) {
                    $totalLateMinutes += $attendanceRecord->late_minutes;
                }
            }
            
            // Convert late minutes to hours (or use total_late_minutes from query if available)
            if (isset($record->total_late_minutes)) {
                $totalLateHours = ($record->total_late_minutes ?? 0) / 60;
            } else {
                $totalLateHours = $totalLateMinutes / 60;
            }
            
            // Update the record with recalculated values
            $record->total_hours_worked = $totalHours;
            $record->total_late_hours = $totalLateHours;
            $record->avg_hours_per_day = $presentCount > 0 ? ($totalHours / $presentCount) : 0;
        }
    }

    public function getUsersProperty()
    {
        // If user can only view own attendance, return only themselves
        if ($this->canOnlyViewOwn) {
            return collect([auth()->user()]);
        }
        
        // If user can manage attendance, return all users
        return User::orderBy('name')->get();
    }

    public function getSummaryStatsProperty()
    {
        $query = AttendanceRecord::query();
        
        $user = auth()->user();

        // If user can only view own attendance, restrict to their records only
        if ($this->canOnlyViewOwn) {
            $query->where('user_id', $user->id);
        } elseif ($this->selectedUser) {
            // Apply same filters as main query
            $query->where('user_id', $this->selectedUser);
        }
        
        if ($this->dateFrom) {
            $query->where('attendance_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('attendance_date', '<=', $this->dateTo);
        }
        
        // Apply search filter only if user can manage attendance
        if ($this->search && $this->canManageAttendance) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Get attendance records for calculation
        $attendanceRecords = $query->get();

        // Calculate late hours from late_minutes stored in database
        $totalLateMinutes = 0;
        foreach ($attendanceRecords as $record) {
            // Use late_minutes directly from database (already calculated and stored correctly)
            if ($record->late_minutes) {
                $totalLateMinutes += $record->late_minutes;
            }
        }
        $totalLateHours = $totalLateMinutes / 60;

        // Calculate absent count - include missing days marked as absent
        $recordedAbsentCount = $attendanceRecords->where('status', 'absent')->count();
        
        // For monthly view with selected user, also count missing working days as absent (pending days)
        if ($this->viewType === 'monthly' && $this->selectedUser && $this->dateFrom && $this->dateTo) {
            $missingDays = $this->countMissingWorkingDays();
            // Missing days (not yet marked) are considered absent
            $recordedAbsentCount += $missingDays;
        }

        // Calculate total users - all employees if not filtered, otherwise unique users with records
        if ($this->selectedUser) {
            $totalUsers = 1;
        } else {
            $userQuery = User::query();
            if ($this->search) {
                $userQuery->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            }
            $totalUsers = $userQuery->count();
        }
        
        // Note: Missing days are NOT counted for "All Employees" view - only for single employee view
        // Pending records are only shown when a single employee is selected, not for all employees
        
        // Calculate on_leave (wfh + paid_leave)
        $onLeave = $attendanceRecords->where('status', 'wfh')->count() + 
                   $attendanceRecords->where('status', 'paid_leave')->count();
        
        $baseStats = [
            'total_records' => $attendanceRecords->count(),
            'total_users' => $totalUsers,
            'present_days' => $attendanceRecords->where('status', 'present')->count(),
            'late_days' => $attendanceRecords->where('status', 'late')->count(),
            'absent_days' => $recordedAbsentCount,
            'wfh_days' => $attendanceRecords->where('status', 'wfh')->count(),
            'paid_leave_days' => $attendanceRecords->where('status', 'paid_leave')->count(),
            'on_leave' => $onLeave,
            'total_hours' => $this->selectedUser ? $this->calculateTotalHoursWithGrace($attendanceRecords) : 0,
            'total_late_minutes' => $totalLateMinutes,
            'total_late_hours' => $totalLateHours,
            'avg_hours_per_day' => $attendanceRecords->avg('hours_worked'),
        ];

        // Add view-specific stats (on_leave already set in baseStats above)
        switch ($this->viewType) {
            case 'daily':
                // Expected hours: average expected hours per day if all employees, or user's expected hours if single employee
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
                // Add total working days (days with attendance - present, late, wfh, paid_leave)
                $baseStats['total_working_days'] = $attendanceRecords->whereIn('status', ['present', 'late', 'wfh', 'paid_leave'])->count();
                // Add expected working days (weekdays in period)
                $baseStats['expected_working_days'] = $this->getWorkingDaysInMonth();
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
        // For all employees, calculate average expected hours
        $userQuery = User::query();
        if ($this->search) {
            $userQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        $users = $userQuery->get();
        if ($users->isEmpty()) {
            return 9; // Default 9 hours
        }
        $totalExpected = 0;
        foreach ($users as $user) {
            $totalExpected += $this->calculateExpectedHours($user);
        }
        return round($totalExpected / $users->count(), 1);
    }
    
    private function getExpectedWeeklyHours()
    {
        if ($this->selectedUser) {
            $user = User::find($this->selectedUser);
            return $user ? $this->calculateExpectedHours($user) * 5 : 45; // 5 working days × 9 hours
        }
        
        // For all employees, calculate average expected weekly hours
        $users = User::query();
        if ($this->search) {
            $users->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        $users = $users->get();
        
        if ($users->isEmpty()) {
            return 45; // Default 5 days × 9 hours
        }
        
        $totalExpected = 0;
        foreach ($users as $user) {
            $totalExpected += $this->calculateExpectedHours($user) * 5; // 5 working days
        }
        return round($totalExpected / $users->count(), 1);
    }
    
    private function getExpectedMonthlyHours()
    {
        if ($this->selectedUser) {
            $user = User::find($this->selectedUser);
            $hoursPerDay = $user ? $this->calculateExpectedHours($user) : 9;
            
            // Calculate actual working days in the selected month
            $workingDays = $this->getWorkingDaysInMonth();
            
            return $hoursPerDay * $workingDays;
        }
        return 0; // Don't show expected hours for all employees
    }
    
    /**
     * Get the number of working days (Monday-Friday) in the selected month or date range
     */
    private function getWorkingDaysInMonth()
    {
        // Use dateFrom and dateTo if they're set, otherwise use selected month/year
        if ($this->dateFrom && $this->dateTo) {
            $startDate = Carbon::parse($this->dateFrom);
            $endDate = Carbon::parse($this->dateTo);
        } elseif ($this->selectedMonth && $this->selectedYear) {
            $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
            $endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();
        } else {
            // Default to current month if not set (shouldn't happen in normal flow)
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }
        
        $workingDays = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Count Monday to Friday as working days
            if ($currentDate->isWeekday()) {
                $workingDays++;
            }
            $currentDate->addDay();
        }
        
        return $workingDays;
    }
    
    private function calculateExpectedHours($user)
    {
        // Calculate expected hours based on the shift start and shift end
        // Subtract 0.5 hours (30 minutes) for lunch break
        if (!$user->check_in_time || !$user->check_out_time) {
            return 9; // Default 9 hours if shift times not set
        }
        
        $shiftStart = Carbon::parse($user->check_in_time);
        $shiftEnd = Carbon::parse($user->check_out_time);
        // Use diffInMinutes for more accurate calculation, then convert to hours
        $totalMinutes = $shiftEnd->diffInMinutes($shiftStart);
        $totalHours = $totalMinutes / 60;
        $expectedHours = max(0, $totalHours - 0.5); // Subtract 30 minutes for lunch break
        
        Log::info('User: ' . $user->name . ' Check in time: ' . $user->check_in_time . ' Check out time: ' . $user->check_out_time . ' Total hours: ' . $totalHours . ' Expected hours (after 0.5h break): ' . $expectedHours);
        return $expectedHours;
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
        
        // Use check_in_time first (from users table), then shift_start, then default to 09:00
        if ($user && $user->check_in_time) {
            return Carbon::parse($user->check_in_time);
        }
        
        if ($user && $user->shift_start) {
            return Carbon::parse($user->shift_start);
        }
        
        // Default check-in time (9:00 AM)
        return Carbon::parse('09:00');
    }
    
    /**
     * Calculate hours worked ignoring seconds and using expected hours per day
     * Short lates (≤30 minutes) get full working hours
     */
    public function calculateHoursWorkedWithGrace($attendanceRecord)
    {
        // If absent, return 0 hours
        if (isset($attendanceRecord->status) && $attendanceRecord->status === 'absent') {
            return 0;
        }
        
        // If Holiday, return 0 hours (holiday doesn't count as working)
        if (isset($attendanceRecord->status) && $attendanceRecord->status === 'holiday') {
            return 0;
        }
        
        // If WFH or Paid Leave, return the hours_worked value directly
        if (isset($attendanceRecord->status) && in_array($attendanceRecord->status, ['wfh', 'paid_leave'])) {
            return $attendanceRecord->hours_worked ?? 0;
        }
        
        $user = $attendanceRecord->user;
        
        // Get expected hours per day for this user (after lunch break)
        $expectedHours = $user ? $this->calculateExpectedHours($user) : 9;
        
        // Use late_minutes directly from database (already calculated with proper rules)
        $lateMinutes = $attendanceRecord->late_minutes ?? 0;
        
        // If late is 30 minutes or less (short late), give full working hours
        if ($lateMinutes > 0 && $lateMinutes <= 30) {
            return $expectedHours; // Full hours for short lates
        }
        
        // For lates more than 30 minutes, deduct late hours
        $lateHours = $lateMinutes / 60;
        
        return max(0, $expectedHours - $lateHours);
    }
    
    /**
     * Calculate deficit hours for a record: expected hours per day minus hours worked (>= 0)
     */
    public function calculateDeficitHours($attendanceRecord)
    {
        $user = $attendanceRecord->user ?? ($this->selectedUser ? User::find($this->selectedUser) : null);
        $expectedHours = $user ? $this->calculateExpectedHours($user) : 9;
        $worked = $this->calculateHoursWorkedWithGrace($attendanceRecord);
        $deficit = max(0, $expectedHours - $worked);
        return $deficit;
    }

    /**
     * Calculate late hours from late_minutes stored in database
     * This uses the stored late_minutes value which was calculated with the proper rules
     */
    public function calculateLateHoursFromDatabase($attendanceRecord)
    {
        // Use late_minutes directly from database (already calculated with rules: <30min exact, 30-60min = 60min, >=60min exact)
        $lateMinutes = $attendanceRecord->late_minutes ?? 0;
        return $lateMinutes / 60;
    }
    
    /**
     * Calculate late hours from the user's expected check_in_time (user table), not shift_start
     * @deprecated Use calculateLateHoursFromDatabase instead - it uses stored late_minutes from database
     */
    public function calculateLateHoursFromUserCheckIn($attendanceRecord)
    {
        // Use stored late_minutes from database instead of recalculating
        return $this->calculateLateHoursFromDatabase($attendanceRecord);
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
     * Count missing working days in the date range for all employees
     */
    private function countMissingDaysForAllEmployees()
    {
        if (!$this->dateFrom || !$this->dateTo) {
            return 0;
        }
        
        $startDate = Carbon::parse($this->dateFrom);
        $endDate = Carbon::parse($this->dateTo);
        $isSingleDay = $startDate->format('Y-m-d') === $endDate->format('Y-m-d');
        
        // Get all employees
        $allUsers = User::query();
        if ($this->search) {
            $allUsers->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        $allUsers = $allUsers->get();
        
        // Get all existing attendance records for all users in the date range
        $existingRecords = AttendanceRecord::whereBetween('attendance_date', [$this->dateFrom, $this->dateTo])
            ->get()
            ->map(function ($record) {
                return $record->user_id . '_' . Carbon::parse($record->attendance_date)->format('Y-m-d');
            })
            ->toArray();
        
        $missingDays = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // For single day selection, count all dates (including weekends)
            // For date range, only count weekdays
            if ($isSingleDay || $currentDate->isWeekday()) {
                $dateStr = $currentDate->format('Y-m-d');
                foreach ($allUsers as $user) {
                    $key = $user->id . '_' . $dateStr;
                    if (!in_array($key, $existingRecords)) {
                        $missingDays++;
                    }
                }
            }
            $currentDate->addDay();
        }
        
        return $missingDays;
    }
    
    /**
     * Count missing working days in the date range for single user
     */
    private function countMissingWorkingDays()
    {
        if (!$this->selectedUser || !$this->dateFrom || !$this->dateTo) {
            return 0;
        }
        
        $startDate = Carbon::parse($this->dateFrom);
        $endDate = Carbon::parse($this->dateTo);
        
        // Get all existing attendance dates for this user in the date range
        $existingDates = AttendanceRecord::where('user_id', $this->selectedUser)
            ->whereBetween('attendance_date', [$this->dateFrom, $this->dateTo])
            ->pluck('attendance_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Count working days (Monday to Friday) that don't have attendance records
        $missingDays = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekday()) {
                $dateStr = $currentDate->format('Y-m-d');
                if (!in_array($dateStr, $existingDates)) {
                    $missingDays++;
                }
            }
            $currentDate->addDay();
        }
        
        return $missingDays;
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
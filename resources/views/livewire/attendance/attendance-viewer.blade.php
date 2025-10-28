<div>
    <div class="container-fluid px-4 py-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-0 text-dark fw-bold">Attendance Viewer</h2>
                <p class="text-muted mb-0">Super Admin - View all employee attendance records</p>
            </div>
            <button class="btn btn-success">
                <i class="bi bi-download me-2"></i>Export Report
            </button>
        </div>

        <!-- Dynamic Summary Cards -->
        <div class="row mb-4">
            @if($viewType === 'daily')
                <!-- Daily View Summary Cards -->
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Employees</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['total_users'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['present_days'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Late Arrivals</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['late_days'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['absent_days'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">On Leave</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['on_leave'] ?? 0 }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-calendar-x text-info" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Expected Hours</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['expected_hours'] ?? 8 }}h</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-clock-history text-secondary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($viewType === 'weekly')
                <!-- Weekly View Summary Cards -->
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Employees</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['total_users'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Avg Attendance %</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['avg_attendance_percent'] ?? 0 }}%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-graph-up text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($selectedUser)
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Weekly Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summaryStats['total_hours'], 1) }}h</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Expected Weekly Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['expected_weekly_hours'] ?? 45 }}h</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-check text-info" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <!-- Monthly View Summary Cards -->
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Employees</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['total_users'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Avg Attendance %</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['avg_attendance_percent'] ?? 0 }}%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-graph-up text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($selectedUser)
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Monthly Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summaryStats['total_hours'], 1) }}h</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Short Late Count</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['short_late_count'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-hourglass-split text-danger" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Expected Monthly Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['expected_monthly_hours'] ?? 198 }}h</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-check text-info" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <!-- Filters Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filter Options</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Employee Dropdown -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label text-dark fw-bold">Employee</label>
                        <select wire:model.live="selectedUser" class="form-select">
                            <option value="">All Employees</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- View Type Dropdown -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label text-dark fw-bold">View Type</label>
                        <select wire:model.live="viewType" class="form-select">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    
                    <!-- Dynamic Inputs based on View Type -->
                    @if($viewType === 'daily')
                        <!-- Daily Date Picker -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-dark fw-bold">Date</label>
                            <input type="date" wire:model.live="selectedDate" class="form-control">
                        </div>
                    @elseif($viewType === 'weekly')
                        <!-- Weekly Selector -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-dark fw-bold">Week</label>
                            <select wire:model.live="selectedWeek" class="form-select">
                                @foreach($weeks as $weekKey => $weekLabel)
                                    <option value="{{ $weekKey }}">{{ $weekLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($viewType === 'monthly')
                        <!-- Monthly Month and Year Dropdowns -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label text-dark fw-bold">Month</label>
                            <select wire:model.live="selectedMonth" class="form-select">
                                @foreach($months as $monthNum => $monthName)
                                    <option value="{{ $monthNum }}">{{ $monthName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label text-dark fw-bold">Year</label>
                            <select wire:model.live="selectedYear" class="form-select">
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    
                    <!-- Clear Filters Button -->
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button wire:click="clearFilters" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-clockwise me-2"></i>Clear Filters
                        </button>
                    </div>
                </div>
                
                <!-- Loading Indicator -->
                <div wire:loading class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Updating attendance data...</p>
                </div>
            </div>
        </div>

        <!-- Attendance Table Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    @if($viewType === 'daily')
                        Daily Attendance Records
                    @elseif($viewType === 'weekly')
                        Weekly Attendance Summary
                    @else
                        Monthly Attendance Summary
                    @endif
                </h6>
                <div class="d-flex align-items-center">
                    <label class="form-label me-2 mb-0">Show</label>
                    <select wire:model.live="perPage" class="form-select form-select-sm me-3" style="width: auto;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <label class="form-label mb-0">entries</label>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="table-dark">
                            <tr>
                                @if($viewType === 'daily')
                                    @if($selectedUser)
                                        <!-- Single Employee Daily View -->
                                        <th class="text-center">Date</th>
                                        <th class="text-center">Day</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Check In</th>
                                        <th class="text-center">Late Hour</th>
                                        <th class="text-center">Hours Worked</th>
                                    @else
                                        <!-- All Employees Daily View -->
                                        <th class="text-center" style="cursor: pointer;" wire:click="sortBy('attendance_date')">
                                            Date
                                            @if($sortField === 'attendance_date')
                                                @if($sortDirection === 'asc') <i class="bi bi-arrow-up"></i> @else <i class="bi bi-arrow-down"></i> @endif
                                            @endif
                                        </th>
                                        <th class="text-center" style="cursor: pointer;" wire:click="sortBy('user_id')">
                                            Employee Name
                                            @if($sortField === 'user_id')
                                                @if($sortDirection === 'asc') <i class="bi bi-arrow-up"></i> @else <i class="bi bi-arrow-down"></i> @endif
                                            @endif
                                        </th>
                                        <th class="text-center">Employee ID</th>
                                        <th class="text-center">Check In</th>
                                        <th class="text-center">Check Out</th>
                                        <th class="text-center">Hours Worked</th>
                                        <th class="text-center">Late (min)</th>
                                        <th class="text-center">Status</th>
                                    @endif
                                @elseif($viewType === 'weekly')
                                    @if($selectedUser)
                                        <!-- Single Employee Weekly View -->
                                        <th class="text-center">Date</th>
                                        <th class="text-center">Day</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Check In</th>
                                        <th class="text-center">Late Hour</th>
                                        <th class="text-center">Hours Worked</th>
                                    @else
                                        <!-- All Employees Weekly View -->
                                        <th class="text-center">Employee Name</th>
                                        <th class="text-center">Total Days</th>
                                        <th class="text-center">Present</th>
                                        <th class="text-center">Late</th>
                                        <th class="text-center">Absent</th>
                                        <th class="text-center">Attendance %</th>
                                        <th class="text-center">Total Hours</th>
                                        <th class="text-center">Avg Hours/Day</th>
                                    @endif
                                @else
                                    @if($selectedUser)
                                        <!-- Single Employee Monthly View -->
                                        <th class="text-center">Date</th>
                                        <th class="text-center">Day</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Check In</th>
                                        <th class="text-center">Late Hour</th>
                                        <th class="text-center">Hours Worked</th>
                                    @else
                                        <!-- All Employees Monthly View -->
                                        <th class="text-center">Employee Name</th>
                                        <th class="text-center">Total Present</th>
                                        <th class="text-center">Total Absent</th>
                                        <th class="text-center">Total Leave</th>
                                        <th class="text-center">Avg Working Hours</th>
                                        <th class="text-center">Total Hours</th>
                                        <th class="text-center">Attendance %</th>
                                    @endif
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attendances as $attendance)
                                <tr class="{{ $loop->even ? 'table-light' : '' }}">
                                    @if($viewType === 'daily')
                                        @if($selectedUser)
                                            <!-- Single Employee Daily View -->
                                            <td class="text-center">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d, Y') }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l') }}</td>
                                            <td class="text-center">
                                                @php
                                                    $statusClass = match($attendance->status) {
                                                        'present' => 'success',
                                                        'late' => 'warning',
                                                        'absent' => 'danger',
                                                        'weekly_off' => 'info',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($attendance->check_in_time)
                                                    {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }}
                                                @else
                                                    <span class="text-muted">--:--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $lateMinutesWithGrace = $this->calculateLateMinutesWithGrace($attendance);
                                                @endphp
                                                @if($lateMinutesWithGrace > 0)
                                                    {{ number_format($lateMinutesWithGrace / 60, 1) }}h
                                                @else
                                                    <span class="text-muted">0.0h</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($this->calculateHoursWorkedWithGrace($attendance), 1) }}h
                                            </td>
                                        @else
                                            <!-- All Employees Daily View -->
                                            <td class="text-center">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d, Y') }}</td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <div class="me-2">
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 12px;">
                                                            {{ substr($attendance->user->name, 0, 1) }}
                                                        </div>
                                                    </div>
                                                    <span class="fw-bold">{{ $attendance->user->name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">{{ $attendance->user->device_user_id ?: 'N/A' }}</td>
                                            <td class="text-center">
                                                @if($attendance->check_in_time)
                                                    {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }}
                                                @else
                                                    <span class="text-muted">--:--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($attendance->check_out_time)
                                                    {{ \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') }}
                                                @else
                                                    <span class="text-muted">--:--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ number_format($attendance->hours_worked, 1) }}h</td>
                                            <td class="text-center">{{ $attendance->late_minutes }} min</td>
                                            <td class="text-center">
                                                @php
                                                    $statusClass = match($attendance->status) {
                                                        'present' => 'success',
                                                        'late' => 'warning',
                                                        'absent' => 'danger',
                                                        'weekly_off' => 'info',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                                            </td>
                                        @endif
                                    @elseif($viewType === 'weekly')
                                        @if($selectedUser)
                                            <!-- Single Employee Weekly View - Individual Daily Records -->
                                            <td class="text-center">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d') }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l') }}</td>
                                            <td class="text-center">
                                                @php
                                                    $statusClass = match($attendance->status) {
                                                        'present' => 'success',
                                                        'late' => 'warning',
                                                        'absent' => 'danger',
                                                        'weekly_off' => 'info',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($attendance->check_in_time)
                                                    {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }}
                                                @else
                                                    <span class="text-muted">--:--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $lateMinutesWithGrace = $this->calculateLateMinutesWithGrace($attendance);
                                                @endphp
                                                @if($lateMinutesWithGrace > 0)
                                                    {{ number_format($lateMinutesWithGrace / 60, 1) }}h
                                                @else
                                                    <span class="text-muted">0.0h</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($this->calculateHoursWorkedWithGrace($attendance), 1) }}h
                                            </td>
                                        @else
                                            <!-- All Employees Weekly View - Grouped Data -->
                                            <td class="text-center">{{ $attendance->user->name }}</td>
                                            <td class="text-center">{{ $attendance->total_days }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-success">{{ $attendance->present_days }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning">{{ $attendance->late_days }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-danger">{{ $attendance->absent_days }}</span>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $attendancePercent = $attendance->total_days > 0 ? round((($attendance->present_days + $attendance->late_days) / $attendance->total_days) * 100, 1) : 0;
                                                @endphp
                                                <span class="badge bg-{{ $attendancePercent >= 80 ? 'success' : ($attendancePercent >= 60 ? 'warning' : 'danger') }}">
                                                    {{ $attendancePercent }}%
                                                </span>
                                            </td>
                                            <td class="text-center">{{ number_format($attendance->total_hours_worked, 1) }}h</td>
                                            <td class="text-center">{{ number_format($attendance->avg_hours_per_day, 1) }}h</td>
                                        @endif
                                    @else
                                        @if($selectedUser)
                                            <!-- Single Employee Monthly View - Individual Daily Records -->
                                            <td class="text-center">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d') }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l') }}</td>
                                            <td class="text-center">
                                                @php
                                                    $statusClass = match($attendance->status) {
                                                        'present' => 'success',
                                                        'late' => 'warning',
                                                        'absent' => 'danger',
                                                        'weekly_off' => 'info',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($attendance->check_in_time)
                                                    {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }}
                                                @else
                                                    <span class="text-muted">--:--</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $lateMinutesWithGrace = $this->calculateLateMinutesWithGrace($attendance);
                                                @endphp
                                                @if($lateMinutesWithGrace > 0)
                                                    {{ number_format($lateMinutesWithGrace / 60, 1) }}h
                                                @else
                                                    <span class="text-muted">0.0h</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($this->calculateHoursWorkedWithGrace($attendance), 1) }}h
                                            </td>
                                        @else
                                            <!-- All Employees Monthly View - Grouped Data -->
                                            <td class="text-center">{{ $attendance->user->name }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-success">{{ $attendance->present_days }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-danger">{{ $attendance->absent_days }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $attendance->total_days - $attendance->present_days - $attendance->absent_days }}</span>
                                            </td>
                                            <td class="text-center">{{ number_format($attendance->avg_hours_per_day, 1) }}h</td>
                                            <td class="text-center">{{ number_format($attendance->total_hours_worked, 1) }}h</td>
                                            <td class="text-center">
                                                @php
                                                    $attendancePercent = $attendance->total_days > 0 ? round((($attendance->present_days + $attendance->late_days) / $attendance->total_days) * 100, 1) : 0;
                                                @endphp
                                                <span class="badge bg-{{ $attendancePercent >= 80 ? 'success' : ($attendancePercent >= 60 ? 'warning' : 'danger') }}">
                                                    {{ $attendancePercent }}%
                                                </span>
                                            </td>
                                        @endif
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-2">No attendance records found for the selected criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Showing {{ $attendances->firstItem() ?? 0 }} to {{ $attendances->lastItem() ?? 0 }} of {{ $attendances->total() }} entries
                    </div>
                    <div>
                        {{ $attendances->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="text-center">
            <button class="btn btn-success btn-lg px-5">
                <i class="bi bi-check-circle me-2"></i>Submit
            </button>
        </div>
    </div>

    <style>
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
    .border-left-danger {
        border-left: 0.25rem solid #e74a3b !important;
    }
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    .border-left-secondary {
        border-left: 0.25rem solid #858796 !important;
    }
    .text-primary {
        color: #4e73df !important;
    }
    .text-success {
        color: #1cc88a !important;
    }
    .text-warning {
        color: #f6c23e !important;
    }
    .text-danger {
        color: #e74a3b !important;
    }
    .text-info {
        color: #36b9cc !important;
    }
    .text-secondary {
        color: #858796 !important;
    }
    .table-dark th {
        background-color: #5a5c69 !important;
        border-color: #5a5c69 !important;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,.075);
    }
    .badge {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
    }
    .card {
        transition: transform 0.2s ease-in-out;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .spinner-border {
        width: 2rem;
        height: 2rem;
    }
    .form-select:focus, .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    </style>
</div>
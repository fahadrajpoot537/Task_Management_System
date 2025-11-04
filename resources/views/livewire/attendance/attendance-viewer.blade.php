<div>
    <div class="container-fluid px-4 py-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-0 text-dark fw-bold">Attendance Viewer</h2>
                <p class="text-muted mb-0">
                    @if($canManageAttendance)
                        View and manage all employee attendance records
                    @else
                        View your attendance records
                    @endif
                </p>
            </div>
            @if($canManageAttendance || ($canOnlyViewOwn && $selectedUser && $viewType === 'monthly'))
                <button class="btn btn-success" wire:click="openBreakdownModal">
                    <i class="bi bi-calculator me-2"></i>Make Breakdown
                </button>
            @endif
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Dynamic Summary Cards -->
        <style>
            /* Compact summary boxes */
            .mini-stat .card-body {
                padding: .5rem .6rem;
            }

            .mini-stat .text-xs {
                font-size: .72rem;
                opacity: .9;
            }

            .mini-stat .h5 {
                font-size: 1rem;
                margin: 0;
            }

            .mini-stat .col-auto i {
                font-size: 1.25rem !important;
            }

            .mini-stat .card {
                border-radius: .5rem;
                box-shadow: none !important;
            }

            /* Table readability */
            .sticky-header-table thead th {
                position: sticky;
                top: 0;
                z-index: 2;
            }

            .sticky-header-table td,
            .sticky-header-table th {
                vertical-align: middle;
            }

            /* Dark mode: ensure contrast */
            [data-bs-theme="dark"] .mini-stat .text-xs {
                color: rgba(255, 255, 255, .75);
            }

            [data-bs-theme="dark"] .mini-stat .h5 {
                color: rgba(255, 255, 255, .95);
            }

            [data-bs-theme="dark"] .text-gray-800 {
                color: #e5e7eb !important;
            }

            [data-bs-theme="dark"] .text-dark {
                color: #e5e7eb !important;
            }

            [data-bs-theme="dark"] .card-header {
                background-color: #0f172a;
                color: #e5e7eb;
            }

            [data-bs-theme="dark"] .table {
                color: rgba(255, 255, 255, .9);
            }

            [data-bs-theme="dark"] .table thead th {
                background-color: #1f2937 !important;
                color: #e5e7eb !important;
            }

            [data-bs-theme="dark"] .table-bordered {
                border-color: rgba(255, 255, 255, .12);
            }

            [data-bs-theme="dark"] .table-bordered> :not(caption)>* {
                border-color: rgba(255, 255, 255, .12);
            }

            [data-bs-theme="dark"] .table-striped>tbody>tr:nth-of-type(odd)>* {
                --bs-table-accent-bg: rgba(255, 255, 255, .05);
                color: inherit;
            }

            [data-bs-theme="dark"] .text-muted {
                color: rgba(255, 255, 255, .6) !important;
            }

            [data-bs-theme="dark"] .badge.bg-warning {
                color: #111 !important;
            }

            [data-bs-theme="dark"] .badge.bg-info {
                color: #0b1b2b !important;
            }

            [data-bs-theme="dark"] .badge.bg-secondary {
                color: #fff !important;
            }

            [data-bs-theme="dark"] .modal-content {
                background-color: #0b1220;
                color: #e5e7eb;
            }

            [data-bs-theme="dark"] .modal-header {
                background-color: #0f172a;
                color: #e5e7eb;
            }

            /* Fallbacks when data-bs-theme is not set but dark mode is active */
            @media (prefers-color-scheme: dark) {
                :root:not([data-bs-theme]) .mini-stat .text-xs {
                    color: rgba(255, 255, 255, .75);
                }

                :root:not([data-bs-theme]) .mini-stat .h5 {
                    color: rgba(255, 255, 255, .95);
                }

                :root:not([data-bs-theme]) .text-gray-800 {
                    color: #e5e7eb !important;
                }

                :root:not([data-bs-theme]) .text-dark {
                    color: #e5e7eb !important;
                }

                :root:not([data-bs-theme]) .card-header {
                    background-color: #0f172a;
                    color: #e5e7eb;
                }

                :root:not([data-bs-theme]) .table {
                    color: rgba(255, 255, 255, .9);
                }

                :root:not([data-bs-theme]) .table thead th {
                    background-color: #1f2937 !important;
                    color: #e5e7eb !important;
                }

                :root:not([data-bs-theme]) .table-bordered {
                    border-color: rgba(255, 255, 255, .12);
                }

                :root:not([data-bs-theme]) .table-bordered> :not(caption)>* {
                    border-color: rgba(255, 255, 255, .12);
                }

                :root:not([data-bs-theme]) .table-striped>tbody>tr:nth-of-type(odd)>* {
                    --bs-table-accent-bg: rgba(255, 255, 255, .05);
                    color: inherit;
                }

                :root:not([data-bs-theme]) .text-muted {
                    color: rgba(255, 255, 255, .6) !important;
                }

                :root:not([data-bs-theme]) .badge.bg-warning {
                    color: #111 !important;
                }

                :root:not([data-bs-theme]) .badge.bg-info {
                    color: #0b1b2b !important;
                }

                :root:not([data-bs-theme]) .badge.bg-secondary {
                    color: #fff !important;
                }

                :root:not([data-bs-theme]) .modal-content {
                    background-color: #0b1220;
                    color: #e5e7eb;
                }

                :root:not([data-bs-theme]) .modal-header {
                    background-color: #0f172a;
                    color: #e5e7eb;
                }
            }

            /* Support common dark body classes (e.g., body.bg-dark) */
            body.bg-dark .text-gray-800,
            body.bg-dark .text-dark {
                color: #e5e7eb !important;
            }

            body.bg-dark .card-header {
                background-color: #0f172a;
                color: #e5e7eb;
            }

            body.bg-dark .table {
                color: rgba(255, 255, 255, .9);
            }

            body.bg-dark .table thead th {
                background-color: #1f2937 !important;
                color: #e5e7eb !important;
            }

            body.bg-dark .text-muted {
                color: rgba(255, 255, 255, .6) !important;
            }
        </style>
        <div class="row mb-3 mini-stat">
            @if ($viewType === 'daily')
                <!-- Daily View Summary Cards -->
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total
                                        Employees</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summaryStats['total_users'] }}</div>
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
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summaryStats['present_days'] }}</div>
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
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Late Arrivals
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['late_days'] }}
                                    </div>
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
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summaryStats['absent_days'] }}</div>
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
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summaryStats['on_leave'] ?? 0 }}</div>
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
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Expected
                                        Hours</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summaryStats['expected_hours'] ?? 8 }}h</div>
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
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total
                                        Employees</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summaryStats['total_users'] }}</div>
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
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Avg
                                        Attendance %</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summaryStats['avg_attendance_percent'] ?? 0 }}%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-graph-up text-success" style="font-size: 2rem;"></i>
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
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Expected Weekly
                                        Hours</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summaryStats['expected_weekly_hours'] ?? 45 }}h</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-calendar-check text-info" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if ($selectedUser)
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total
                                            Weekly Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ number_format($summaryStats['total_hours'], 1) }}h</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <!-- Monthly View Summary Cards -->
                @if (!$selectedUser)
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total
                                            Employees</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $summaryStats['total_users'] }}</div>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Avg
                                            Attendance %</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $summaryStats['avg_attendance_percent'] ?? 0 }}%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-graph-up text-success" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($selectedUser)
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total
                                            Monthly Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ number_format($summaryStats['total_hours'], 1) }}h</div>
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
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total
                                            Late Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ number_format($summaryStats['total_late_hours'] ?? 0, 1) }}h</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clock-history text-danger" style="font-size: 2rem;"></i>
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
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent
                                            Count</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $summaryStats['absent_days'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">WFH
                                            Count</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $summaryStats['wfh_days'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-house-door text-primary" style="font-size: 2rem;"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Paid
                                            Leave Count</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $summaryStats['paid_leave_days'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-check text-success" style="font-size: 2rem;"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Expected
                                            Monthly Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $summaryStats['expected_monthly_hours'] ?? 0 }}h</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-check text-info" style="font-size: 2rem;"></i>
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
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Short
                                            Late Count</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $summaryStats['short_late_count'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
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
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total
                                            Working Days</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $summaryStats['total_working_days'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar3 text-secondary" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Expected
                                            Days</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $summaryStats['expected_working_days'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-week text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Mark Holiday Modal -->
            @if ($showHolidayModal)
                <div class="modal-backdrop fade show" wire:click="closeHolidayModal"
                    style="opacity: 0.5; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1054;">
                </div>
                <div class="modal fade show" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel"
                    aria-hidden="false" style="display: block; z-index: 1055;" wire:ignore.self>
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="holidayModalLabel">
                                    <i class="bi bi-calendar-event me-2"></i>Mark Holiday for All Employees
                                </h5>
                                <button type="button" class="btn-close btn-close-white"
                                    wire:click="closeHolidayModal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    This will mark the selected date as a holiday for all employees. All employees will
                                    have their attendance status set to "Holiday" for this date.
                                </div>
                                <div class="mb-3">
                                    <label for="holidayDate" class="form-label">Select Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date"
                                        class="form-control @error('holidayDate') is-invalid @enderror"
                                        id="holidayDate" wire:model="holidayDate" required>
                                    @error('holidayDate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    wire:click="closeHolidayModal">Cancel</button>
                                <button type="button" class="btn btn-success" wire:click="markHolidayForAll">
                                    <span wire:loading.remove wire:target="markHolidayForAll">
                                        <i class="bi bi-check-circle me-2"></i>Mark as Holiday
                                    </span>
                                    <span wire:loading wire:target="markHolidayForAll">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"
                                            aria-hidden="true"></span>
                                        Processing...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Mark Holiday Modal -->
            @if ($showHolidayModal)
                <div class="modal-backdrop fade show" wire:click="closeHolidayModal"
                    style="opacity: 0.5; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1054;">
                </div>
                <div class="modal fade show" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel"
                    aria-hidden="false" style="display: block; z-index: 1055;" wire:ignore.self>
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="holidayModalLabel">
                                    <i class="bi bi-calendar-event me-2"></i>Mark Holiday for All Employees
                                </h5>
                                <button type="button" class="btn-close btn-close-white"
                                    wire:click="closeHolidayModal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    This will mark the selected date range as a holiday for all employees. All employees
                                    will have their attendance status set to "Holiday" for the selected dates.
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="holidayStartDate" class="form-label">Start Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date"
                                            class="form-control @error('holidayStartDate') is-invalid @enderror"
                                            id="holidayStartDate" wire:model="holidayStartDate" required>
                                        @error('holidayStartDate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="holidayEndDate" class="form-label">End Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date"
                                            class="form-control @error('holidayEndDate') is-invalid @enderror"
                                            id="holidayEndDate" wire:model="holidayEndDate" required>
                                        @error('holidayEndDate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    wire:click="closeHolidayModal">Cancel</button>
                                <button type="button" class="btn btn-success" wire:click="markHolidayForAll">
                                    <span wire:loading.remove wire:target="markHolidayForAll">
                                        <i class="bi bi-check-circle me-2"></i>Mark as Holiday
                                    </span>
                                    <span wire:loading wire:target="markHolidayForAll">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"
                                            aria-hidden="true"></span>
                                        Processing...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Filters Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Options</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Employee Dropdown - Only show if can manage attendance -->
                        @if($canManageAttendance)
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-dark fw-bold">Employee</label>
                                <select wire:model.live="selectedUser" class="form-select" @if($canOnlyViewOwn) disabled @endif>
                                    <option value="">All Employees</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <!-- Hidden input to maintain selectedUser for view-only users -->
                            <input type="hidden" wire:model="selectedUser">
                        @endif

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
                        @if ($viewType === 'daily')
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
                                    @foreach ($weeks as $weekKey => $weekLabel)
                                        <option value="{{ $weekKey }}">{{ $weekLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($viewType === 'monthly')
                            <!-- Monthly Month and Year Dropdowns -->
                            <div class="col-md-2 mb-3">
                                <label class="form-label text-dark fw-bold">Month</label>
                                <select wire:model.live="selectedMonth" class="form-select">
                                    @foreach ($months as $monthNum => $monthName)
                                        <option value="{{ $monthNum }}">{{ $monthName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label text-dark fw-bold">Year</label>
                                <select wire:model.live="selectedYear" class="form-select">
                                    @foreach ($years as $year)
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
                        @if ($viewType === 'daily')
                            Daily Attendance Records
                        @elseif($viewType === 'weekly')
                            Weekly Attendance Summary
                        @else
                            Monthly Attendance Summary
                        @endif
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        @if ($canManageAttendance && $viewType === 'daily' && !$selectedUser)
                            <button type="button" class="btn btn-sm btn-success" wire:click="openHolidayModal">
                                <i class="bi bi-calendar-event me-1"></i>Mark Holiday
                            </button>
                        @endif
                        <label class="form-label me-2 mb-0">Show</label>
                        <select wire:model.live="perPage" class="form-select form-select-sm me-3"
                            style="width: auto;">
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
                        <table class="table table-bordered table-striped table-sm sticky-header-table" id="dataTable"
                            width="100%" cellspacing="0">
                            <thead class="table-dark">
                                <tr>
                                    @if ($viewType === 'daily')
                                        @if ($selectedUser)
                                            <!-- Single Employee Daily View -->
                                            <th class="text-center">Date</th>
                                            <th class="text-center">Day</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Check In</th>
                                            <th class="text-center">Late Hour</th>
                                            <th class="text-center">Hours Worked</th>
                                            <th class="text-center">Edit</th>
                                        @else
                                            <!-- All Employees Daily View -->
                                            <th class="text-center" style="cursor: pointer;"
                                                wire:click="sortBy('attendance_date')">
                                                Date
                                                @if ($sortField === 'attendance_date')
                                                    @if ($sortDirection === 'asc')
                                                        <i class="bi bi-arrow-up"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down"></i>
                                                    @endif
                                                @endif
                                            </th>
                                            <th class="text-center" style="cursor: pointer;"
                                                wire:click="sortBy('user_id')">
                                                Employee Name
                                                @if ($sortField === 'user_id')
                                                    @if ($sortDirection === 'asc')
                                                        <i class="bi bi-arrow-up"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down"></i>
                                                    @endif
                                                @endif
                                            </th>
                                            <th class="text-center">Employee ID</th>
                                            <th class="text-center">Check In</th>
                                            <th class="text-center">Check Out</th>
                                            <th class="text-center">Hours Worked</th>
                                            <th class="text-center">Late (min)</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Edit</th>
                                        @endif
                                    @elseif($viewType === 'weekly')
                                        @if ($selectedUser)
                                            <!-- Single Employee Weekly View -->
                                            <th class="text-center">Date</th>
                                            <th class="text-center">Day</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Check In</th>
                                            <th class="text-center">Late Hour</th>
                                            <th class="text-center">Hours Worked</th>
                                            <th class="text-center">Edit</th>
                                        @else
                                            <!-- All Employees Weekly View -->
                                            <th class="text-center">Employee Name</th>
                                            <th class="text-center">Total Days</th>
                                            <th class="text-center">Present</th>
                                            <th class="text-center">Late</th>
                                            <th class="text-center">Absent</th>
                                            <th class="text-center">Attendance %</th>
                                            <th class="text-center">Total Late Hours</th>
                                            <th class="text-center">Total Hours</th>
                                            <th class="text-center">Avg Hours/Day</th>
                                        @endif
                                    @else
                                        @if ($selectedUser)
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
                                            <th class="text-center">Working Days</th>
                                            <th class="text-center">Expected Days</th>
                                            <th class="text-center">Total Late Hours</th>
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
                                        @if ($viewType === 'daily')
                                            @if ($selectedUser)
                                                <!-- Single Employee Daily View -->
                                                <td class="text-center">
                                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d, Y') }}
                                                </td>
                                                <td class="text-center">
                                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l') }}
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $statusClass = match ($attendance->status) {
                                                            'present' => 'success',
                                                            'late' => 'warning',
                                                            'absent' => 'danger',
                                                            'weekly_off' => 'info',
                                                            default => 'secondary',
                                                        };
                                                    @endphp
                                                    <span
                                                        class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($attendance->check_in_time)
                                                        {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }}
                                                    @else
                                                        <span class="text-muted">--:--</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @php $lateH = $this->calculateLateHoursFromUserCheckIn($attendance); @endphp
                                                    @if ($lateH > 0)
                                                        {{ number_format($lateH, 1) }}h
                                                    @else
                                                        <span class="text-muted">0.0h</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    {{ number_format($this->calculateHoursWorkedWithGrace($attendance), 1) }}h
                                                </td>
                                                <td class="text-center">
                                                    @if($canManageAttendance)
                                                        @php $isPending = isset($attendance->is_pending) && $attendance->is_pending; @endphp
                                                        @if ($isPending && ($attendance->attendance_date ?? null))
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                wire:click="openEditModalByDate('{{ $attendance->attendance_date }}', {{ $attendance->user_id }})">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        @elseif($attendance->id)
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                wire:click="openEditModal({{ $attendance->id }})">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">View Only</span>
                                                    @endif
                                                </td>
                                            @else
                                                <!-- All Employees Daily View -->
                                                <td class="text-center">
                                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d, Y') }}
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <div class="me-2">
                                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                                                style="width: 30px; height: 30px; font-size: 12px;">
                                                                {{ substr($attendance->user->name, 0, 1) }}
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold">{{ $attendance->user->name }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    {{ $attendance->user->device_user_id ?: 'N/A' }}</td>
                                                <td class="text-center">
                                                    @if ($attendance->check_in_time)
                                                        {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }}
                                                    @else
                                                        <span class="text-muted">--:--</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($attendance->check_out_time)
                                                        {{ \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') }}
                                                    @else
                                                        <span class="text-muted">--:--</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    {{ number_format($attendance->hours_worked, 1) }}h</td>
                                                <td class="text-center">{{ $attendance->late_minutes }} min</td>
                                                <td class="text-center">
                                                    @php
                                                        $statusClass = match ($attendance->status) {
                                                            'present' => 'success',
                                                            'late' => 'warning',
                                                            'absent' => 'danger',
                                                            'weekly_off' => 'info',
                                                            default => 'secondary',
                                                        };
                                                    @endphp
                                                    <span
                                                        class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if($canManageAttendance)
                                                        @php $isPending = isset($attendance->is_pending) && $attendance->is_pending; @endphp
                                                        @if ($isPending && ($attendance->attendance_date ?? null) && ($attendance->user_id ?? null))
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                wire:click="openEditModalByDate('{{ $attendance->attendance_date }}', {{ $attendance->user_id }})">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        @elseif($attendance->id)
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                wire:click="openEditModal({{ $attendance->id }})">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">View Only</span>
                                                    @endif
                                                </td>
                                            @endif
                                        @elseif($viewType === 'weekly')
                                            @if ($selectedUser)
                                                <!-- Single Employee Weekly View - Individual Daily Records -->
                                                <td class="text-center">
                                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d') }}
                                                </td>
                                                <td class="text-center">
                                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l') }}
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $statusClass = match ($attendance->status) {
                                                            'present' => 'success',
                                                            'late' => 'warning',
                                                            'absent' => 'danger',
                                                            'weekly_off' => 'info',
                                                            default => 'secondary',
                                                        };
                                                    @endphp
                                                    <span
                                                        class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if ($attendance->check_in_time)
                                                        {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }}
                                                    @else
                                                        <span class="text-muted">--:--</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @php $lateH = $this->calculateLateHoursFromUserCheckIn($attendance); @endphp
                                                    @if ($lateH > 0)
                                                        {{ number_format($lateH, 1) }}h
                                                    @else
                                                        <span class="text-muted">0.0h</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    {{ number_format($this->calculateHoursWorkedWithGrace($attendance), 1) }}h
                                                </td>
                                                <td class="text-center">
                                                    @if($canManageAttendance)
                                                        @php $isPending = isset($attendance->is_pending) && $attendance->is_pending; @endphp
                                                        @if ($isPending && ($attendance->attendance_date ?? null))
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                wire:click="openEditModalByDate('{{ $attendance->attendance_date }}', {{ $attendance->user_id }})">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        @elseif($attendance->id)
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                wire:click="openEditModal({{ $attendance->id }})">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">View Only</span>
                                                    @endif
                                                </td>
                                            @else
                                                <!-- All Employees Weekly View - Grouped Data -->
                                                <td class="text-center">{{ $attendance->user->name }}</td>
                                                <td class="text-center">{{ $attendance->total_days }}</td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-success">{{ $attendance->present_days }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning">{{ $attendance->late_days }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-danger">{{ $attendance->absent_days }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $attendancePercent =
                                                            $attendance->total_days > 0
                                                                ? round(
                                                                    (($attendance->present_days +
                                                                        $attendance->late_days) /
                                                                        $attendance->total_days) *
                                                                        100,
                                                                    1,
                                                                )
                                                                : 0;
                                                    @endphp
                                                    <span
                                                        class="badge bg-{{ $attendancePercent >= 80 ? 'success' : ($attendancePercent >= 60 ? 'warning' : 'danger') }}">
                                                        {{ $attendancePercent }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-warning">{{ number_format($attendance->total_late_hours ?? 0, 1) }}h</span>
                                                </td>
                                                <td class="text-center">
                                                    {{ number_format($attendance->total_hours_worked, 1) }}h</td>
                                                <td class="text-center">
                                                    {{ number_format($attendance->avg_hours_per_day, 1) }}h</td>
                                            @endif
                                        @else
                                            @if ($selectedUser)
                                                <!-- Single Employee Monthly View - Individual Daily Records -->
                                                <td class="text-center">
                                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('M d') }}
                                                </td>
                                                <td class="text-center">
                                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l') }}
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $isPending =
                                                            isset($attendance->is_pending) && $attendance->is_pending;
                                                        $statusClass = match ($attendance->status ?? 'pending') {
                                                            'present' => 'success',
                                                            'late' => 'warning',
                                                            'absent' => 'danger',
                                                            'weekly_off' => 'info',
                                                            'wfh' => 'primary',
                                                            'paid_leave' => 'info',
                                                            'pending' => 'secondary',
                                                            default => 'secondary',
                                                        };
                                                    @endphp
                                                    @if ($isPending || ($attendance->status ?? '') === 'pending')
                                                        <span class="badge bg-secondary mb-2 d-block">Pending</span>
                                                    @else
                                                        <span
                                                            class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($attendance->check_in_time ?? null)
                                                        {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }}
                                                    @else
                                                        <span class="text-muted">--:--</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (isset($attendance->is_pending) && $attendance->is_pending)
                                                        <span class="text-muted">--</span>
                                                    @else
                                                        @php $lateH = $this->calculateLateHoursFromUserCheckIn($attendance); @endphp
                                                        @if ($lateH > 0)
                                                            {{ number_format($lateH, 1) }}h
                                                        @else
                                                            <span class="text-muted">0.0h</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (isset($attendance->is_pending) && $attendance->is_pending)
                                                        <span class="text-muted">--</span>
                                                    @else
                                                        {{ number_format($this->calculateHoursWorkedWithGrace($attendance), 1) }}h
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($canManageAttendance)
                                                        @php $isPending = isset($attendance->is_pending) && $attendance->is_pending; @endphp
                                                        @if ($isPending && ($attendance->attendance_date ?? null))
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                wire:click="openEditModalByDate('{{ $attendance->attendance_date }}', {{ $attendance->user_id }})">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        @elseif($attendance->id)
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                wire:click="openEditModal({{ $attendance->id }})">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">View Only</span>
                                                    @endif
                                                </td>
                                            @else
                                                <!-- All Employees Monthly View - Grouped Data -->
                                                <td class="text-center">{{ $attendance->user->name }}</td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-success">{{ $attendance->present_days }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-danger">{{ $attendance->absent_days ?? 0 }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-info">{{ $attendance->total_days - $attendance->present_days - ($attendance->absent_days ?? 0) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-success">{{ $attendance->total_working_days ?? $attendance->present_days + $attendance->late_days }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-primary">{{ $attendance->expected_working_days ?? 0 }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-warning">{{ number_format($attendance->total_late_hours ?? 0, 1) }}h</span>
                                                </td>
                                                <td class="text-center">
                                                    {{ number_format($attendance->avg_hours_per_day, 1) }}h</td>
                                                <td class="text-center">
                                                    {{ number_format($attendance->total_hours_worked, 1) }}h</td>
                                                <td class="text-center">
                                                    @php
                                                        $attendancePercent =
                                                            $attendance->total_days > 0
                                                                ? round(
                                                                    (($attendance->present_days +
                                                                        $attendance->late_days) /
                                                                        $attendance->total_days) *
                                                                        100,
                                                                    1,
                                                                )
                                                                : 0;
                                                    @endphp
                                                    <span
                                                        class="badge bg-{{ $attendancePercent >= 80 ? 'success' : ($attendancePercent >= 60 ? 'warning' : 'danger') }}">
                                                        {{ $attendancePercent }}%
                                                    </span>
                                                </td>
                                            @endif
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                                <p class="mt-2">No attendance records found for the selected
                                                    criteria.</p>
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
                            Showing {{ $attendances->firstItem() ?? 0 }} to {{ $attendances->lastItem() ?? 0 }} of
                            {{ $attendances->total() }} entries
                        </div>
                        <div>
                            {{ $attendances->links() }}
                        </div>
                    </div>
                </div>
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
                background-color: rgba(0, 0, 0, .075);
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

            .form-select:focus,
            .form-control:focus {
                border-color: #4e73df;
                box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            }

            .modal {
                z-index: 1055;
            }

            .modal-backdrop {
                z-index: 1054;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }
        </style>

        <!-- WFH Hours Modal -->
        @if ($showWfhModal)
            <div class="modal-backdrop fade show" wire:click="closeWfhModal"
                style="opacity: 0.5; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1054;">
            </div>
            <div class="modal fade show" id="wfhModal" tabindex="-1" aria-labelledby="wfhModalLabel"
                aria-hidden="false" style="display: block; z-index: 1055;" wire:ignore.self>
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="wfhModalLabel">Work From Home - Enter Working Hours</h5>
                            <button type="button" class="btn-close" wire:click="closeWfhModal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="wfhHours" class="form-label">
                                    Working Hours
                                    <small class="text-muted">(Max: {{ number_format($maxWfhHours, 1) }}
                                        hours)</small>
                                </label>
                                <input type="number" step="0.1" min="0" max="{{ $maxWfhHours }}"
                                    wire:model="wfhHours" class="form-control @error('wfhHours') is-invalid @enderror"
                                    id="wfhHours" placeholder="Enter working hours" autofocus>
                                @error('wfhHours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Date: {{ \Carbon\Carbon::parse($selectedDateForWFH)->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                wire:click="closeWfhModal">Cancel</button>
                            <button type="button" class="btn btn-primary" wire:click="saveWfh">
                                <span wire:loading.remove wire:target="saveWfh">Save</span>
                                <span wire:loading wire:target="saveWfh">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"
                                        aria-hidden="true"></span>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Breakdown Modal -->
        @if ($showBreakdownModal && !empty($breakdownData))
            <div class="modal-backdrop fade show" wire:click="closeBreakdownModal"
                style="opacity: 0.5; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1054;">
            </div>
            <div class="modal fade show" id="breakdownModal" tabindex="-1" aria-labelledby="breakdownModalLabel"
                aria-hidden="false" style="display: block; z-index: 1055;" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="breakdownModalLabel">
                                <i class="bi bi-calculator me-2"></i>Monthly Wages Breakdown
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeBreakdownModal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Summary Cards -->
                            <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <h5 class="text-primary">{{ $breakdownData['user_name'] ?? '' }} -
                                        {{ $breakdownData['month'] ?? '' }}</h5>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-primary shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Monthly Salary</div>
                                            <div class="h5 mb-0 font-weight-bold">PKR
                                                {{ number_format($breakdownData['monthly_salary'] ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-info shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Daily
                                                Wage</div>
                                            <div class="h5 mb-0 font-weight-bold">PKR
                                                {{ number_format($breakdownData['daily_wage'] ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-success shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Hourly Wage</div>
                                            <div class="h5 mb-0 font-weight-bold">PKR
                                                {{ number_format($breakdownData['hourly_wage'] ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-secondary shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                                Expected Days</div>
                                            <div class="h5 mb-0 font-weight-bold">
                                                {{ $breakdownData['expected_working_days'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Breakdown Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm sticky-header-table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="text-center">Date</th>
                                            <th class="text-center">Day</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Hours Worked</th>
                                            <th class="text-center">Wages Earned (PKR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($breakdownData['breakdown'] ?? [] as $day)
                                            <tr class="{{ $day['hours_worked'] == 0 ? 'table-secondary' : '' }}">
                                                <td class="text-center">
                                                    {{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</td>
                                                <td class="text-center">{{ $day['day'] }}</td>
                                                <td class="text-center">
                                                    @php
                                                        $statusClass = match ($day['status']) {
                                                            'present' => 'success',
                                                            'late' => 'warning',
                                                            'absent' => 'danger',
                                                            'wfh' => 'primary',
                                                            'paid_leave' => 'info',
                                                            'pending' => 'secondary',
                                                            default => 'secondary',
                                                        };
                                                    @endphp
                                                    <span
                                                        class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $day['status'])) }}</span>
                                                </td>
                                                <td class="text-center">{{ number_format($day['hours_worked'], 1) }}h
                                                </td>
                                                <td
                                                    class="text-center fw-bold {{ $day['wages_earned'] > 0 ? 'text-success' : 'text-muted' }}">
                                                    PKR {{ number_format($day['wages_earned'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <th colspan="3" class="text-end align-middle">
                                                <strong>Total:</strong>
                                            </th>
                                            <th class="text-center align-middle">
                                                <div class="fw-bold">{{ number_format($breakdownData['total_hours_worked'] ?? 0, 1) }}h</div>
                                                <small class="text-white-50">/ {{ number_format($breakdownData['expected_hours'] ?? 0, 1) }}h</small>
                                            </th>
                                            <th class="text-center align-middle bg-primary bg-opacity-25" style="padding: 15px !important;">
                                                <div class="mb-2">
                                                    <div class="fw-bold fs-5 text-white">PKR {{ number_format($breakdownData['final_wages'] ?? ($breakdownData['total_wages_earned'] ?? 0), 2) }}</div>
                                                </div>
                                                <div class="bg-white bg-opacity-10 rounded p-2 mt-2 border border-white border-opacity-25">
                                                    <div class="mb-1 small">
                                                        <span class="text-white-50">Expected:</span>
                                                        <span class="fw-semibold text-white ms-1">PKR {{ number_format($breakdownData['expected_wages'] ?? 0, 2) }}</span>
                                                    </div>
                                                    <div class="mb-1 small">
                                                        <span class="text-white-50">Gross:</span>
                                                        <span class="fw-semibold text-white ms-1">PKR {{ number_format($breakdownData['gross_wages'] ?? 0, 2) }}</span>
                                                    </div>
                                                    @if (($breakdownData['actual_short_late_penalty'] ?? 0) > 0)
                                                        <div class="mb-1 small">
                                                            <span class="text-white-50">Late Penalty:</span>
                                                            <span class="fw-semibold text-danger ms-1">-PKR {{ number_format($breakdownData['actual_short_late_penalty'] ?? 0, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if (($breakdownData['actual_absent_deduction'] ?? 0) > 0)
                                                        <div class="mb-1 small">
                                                            <span class="text-white-50">Absent Deduction:</span>
                                                            <span class="fw-semibold text-danger ms-1">-PKR {{ number_format($breakdownData['actual_absent_deduction'] ?? 0, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if (($breakdownData['total_bonus'] ?? 0) > 0)
                                                        <div class="mb-1 small">
                                                            <span class="text-white-50">Bonus:</span>
                                                            <span class="fw-semibold text-success ms-1">+PKR {{ number_format($breakdownData['total_bonus'] ?? 0, 2) }}</span>
                                                            @if (($breakdownData['punctual_bonus'] ?? 0) > 0)
                                                                <small class="text-white-50 d-block ms-2">(Punctual: PKR {{ number_format($breakdownData['punctual_bonus'] ?? 0, 2) }})</small>
                                                            @endif
                                                            @if (($breakdownData['manual_bonus'] ?? 0) > 0)
                                                                <small class="text-white-50 d-block ms-2">(Manual: PKR {{ number_format($breakdownData['manual_bonus'] ?? 0, 2) }})</small>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Bonus and Deduction Options - Only for users with manage_attendance permission -->
                            @if($canManageAttendance)
                                <div class="row mt-4 mb-3">
                                    <div class="col-md-12">
                                        <div class="card border-left-info shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-info mb-3">
                                                    <i class="bi bi-sliders me-2"></i>Adjustments
                                                </h6>
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Manual Bonus (PKR)</label>
                                                        <input type="number" step="0.01" min="0"
                                                            class="form-control @error('manualBonus') is-invalid @enderror"
                                                            wire:model.debounce.800ms="manualBonus"
                                                            wire:blur="validateManualBonus" placeholder="0.00"
                                                            id="manualBonusInput">
                                                        <small class="text-muted">Enter additional bonus amount (updates
                                                            after you stop typing)</small>
                                                        @error('manualBonus')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-4 mb-3 d-flex align-items-end">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="noDeductionSwitch" wire:model.live="noDeduction">
                                                            <label class="form-check-label" for="noDeductionSwitch">
                                                                <strong>No Deductions</strong>
                                                            </label>
                                                        </div>
                                                        <small class="text-muted ms-2">Exclude all penalties and absent
                                                            deductions</small>
                                                    </div>
                                                    <div class="col-md-4 mb-3 d-flex align-items-end">
                                                        <div class="alert alert-info mb-0 py-2 px-3 w-100">
                                                            <small>
                                                                <strong>Total Bonus:</strong> PKR
                                                                {{ number_format($breakdownData['total_bonus'] ?? 0, 2) }}<br>
                                                                <strong>Total Deductions:</strong> PKR
                                                                {{ number_format(($breakdownData['actual_short_late_penalty'] ?? 0) + ($breakdownData['actual_absent_deduction'] ?? 0), 2) }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Additional Summary -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card border-left-warning shadow-sm">
                                        <div class="card-body">
                                            <h6 class="text-warning mb-3">Summary</h6>
                                            <!-- Salary Summary Panel (formatted) -->
                                            <div class="row g-3 align-items-center mb-3">
                                                <div class="col-md-4">
                                                    <div class="small text-muted">Salary Summary –
                                                        {{ $breakdownData['month'] ?? '' }}</div>
                                                    <div class="fw-bold">Basic Salary: PKR
                                                        {{ number_format($breakdownData['monthly_salary'] ?? 0, 0) }}
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="row text-center">
                                                        <div class="col">
                                                            <div class="small text-muted">Total Working Days</div>
                                                            <div class="fw-bold">
                                                                {{ $breakdownData['expected_working_days'] ?? 0 }}
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="small text-muted">Per Day Wage</div>
                                                            <div class="fw-bold">PKR
                                                                {{ number_format($breakdownData['daily_wage'] ?? 0, 0) }}
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="small text-muted">Hourly Rate</div>
                                                            <div class="fw-bold">PKR
                                                                {{ number_format($breakdownData['hourly_wage'] ?? 0, 0) }}
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="small text-muted">Short Lates</div>
                                                            <div class="fw-bold">
                                                                {{ $breakdownData['short_late_count'] ?? 0 }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <div class="small text-muted">Deductions & Adjustments</div>
                                                    <ul class="mb-0 ps-3">
                                                        @if ($breakdownData['no_deduction'] ?? false)
                                                            <li class="text-muted"><em>All deductions excluded (No
                                                                    Deductions enabled)</em></li>
                                                        @else
                                                            <li>Late Deduction: PKR
                                                                {{ number_format($breakdownData['actual_short_late_penalty'] ?? 0, 0) }}
                                                            </li>
                                                            <li>Absent Deduction
                                                                ({{ $breakdownData['absent_days'] ?? 0 }}
                                                                day{{ ($breakdownData['absent_days'] ?? 0) == 1 ? '' : 's' }}):
                                                                PKR
                                                                {{ number_format($breakdownData['actual_absent_deduction'] ?? 0, 0) }}
                                                            </li>
                                                        @endif
                                                        @if (($breakdownData['total_bonus'] ?? 0) > 0)
                                                            <li class="text-success">
                                                                <strong>Total Bonus: +PKR
                                                                    {{ number_format($breakdownData['total_bonus'] ?? 0, 0) }}</strong>
                                                                @if (($breakdownData['punctual_bonus'] ?? 0) > 0)
                                                                    <small class="text-muted">(Punctual:
                                                                        {{ number_format($breakdownData['punctual_bonus'] ?? 0, 0) }})</small>
                                                                @endif
                                                                @if (($breakdownData['manual_bonus'] ?? 0) > 0)
                                                                    <small class="text-muted">(Manual:
                                                                        {{ number_format($breakdownData['manual_bonus'] ?? 0, 0) }})</small>
                                                                @endif
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-muted">Net Payable Salary</div>
                                                    <div class="display-6 fw-bold text-primary">PKR
                                                        {{ number_format($breakdownData['final_wages'] ?? ($breakdownData['total_wages_earned'] ?? 0), 0) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Total Hours Worked:</strong>
                                                        {{ number_format($breakdownData['total_hours_worked'] ?? 0, 1) }}h
                                                    </p>
                                                    <p class="mb-1"><strong>Expected Hours:</strong>
                                                        {{ number_format($breakdownData['expected_hours'] ?? 0, 1) }}h
                                                    </p>
                                                    <p class="mb-0"><strong>Hours Difference:</strong>
                                                        <span
                                                            class="{{ ($breakdownData['total_hours_worked'] ?? 0) >= ($breakdownData['expected_hours'] ?? 0) ? 'text-success' : 'text-danger' }}">
                                                            {{ number_format(($breakdownData['total_hours_worked'] ?? 0) - ($breakdownData['expected_hours'] ?? 0), 1) }}h
                                                        </span>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Short Late Count:</strong>
                                                        {{ $breakdownData['short_late_count'] ?? 0 }}</p>
                                                    <p class="mb-1"><strong>Full Day Penalties (3 short lates = 1
                                                            day):</strong>
                                                        {{ $breakdownData['full_day_penalty_days'] ?? 0 }}</p>
                                                    <p class="mb-1"><strong>Penalty Amount:</strong> PKR
                                                        {{ number_format($breakdownData['short_late_penalty'] ?? 0, 2) }}
                                                    </p>
                                                    <p class="mb-1"><strong>Gross Wages (before penalty):</strong>
                                                        PKR {{ number_format($breakdownData['gross_wages'] ?? 0, 2) }}
                                                    </p>
                                                    @if (($breakdownData['punctual_bonus'] ?? 0) > 0)
                                                        <p class="mb-1"><strong>Punctuality Bonus:</strong> PKR
                                                            {{ number_format($breakdownData['punctual_bonus'] ?? 0, 2) }}
                                                        </p>
                                                    @endif
                                                    <p class="mb-1"><strong>Final Wages (after
                                                            penalty/bonus):</strong> PKR
                                                        {{ number_format($breakdownData['final_wages'] ?? ($breakdownData['total_wages_earned'] ?? 0), 2) }}
                                                    </p>
                                                    <p class="mb-1"><strong>Expected Wages:</strong> PKR
                                                        {{ number_format($breakdownData['expected_wages'] ?? 0, 2) }}
                                                    </p>
                                                    <p class="mb-0"><strong>Wages Difference:</strong>
                                                        <span
                                                            class="{{ ($breakdownData['final_wages'] ?? ($breakdownData['total_wages_earned'] ?? 0)) >= ($breakdownData['expected_wages'] ?? 0) ? 'text-success' : 'text-danger' }}">
                                                            PKR
                                                            {{ number_format(($breakdownData['final_wages'] ?? ($breakdownData['total_wages_earned'] ?? 0)) - ($breakdownData['expected_wages'] ?? 0), 2) }}
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-between">
                            <div>
                                <a class="btn btn-outline-primary" target="_blank"
                                    href="{{ route('salary-summary.print', ['user_id' => $selectedUser, 'from' => $dateFrom, 'to' => $dateTo, 'no_deduction' => $noDeduction ? 1 : 0, 'manual_bonus' => $manualBonus ?? 0]) }}">
                                    <i class="bi bi-printer me-2"></i>Export PDF / Print
                                </a>
                                <button type="button" class="btn btn-outline-success"
                                    wire:click="sendBreakdownEmail">
                                    <i class="bi bi-envelope me-2"></i>Send Email
                                </button>
                            </div>
                            <button type="button" class="btn btn-secondary"
                                wire:click="closeBreakdownModal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Edit Attendance Modal -->
        @if ($showEditModal)
            <div class="modal-backdrop fade show"
                style="opacity: 0.5; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,.5); z-index:1054;">
            </div>
            <div class="modal fade show" tabindex="-1" aria-hidden="false" style="display:block; z-index:1055;"
                wire:ignore.self>
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Attendance</h5>
                            <button type="button" class="btn-close" aria-label="Close"
                                wire:click="closeEditModal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date</label>
                                    <input type="date" class="form-control" wire:model.defer="editDate">
                                    @error('editDate')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" wire:model.defer="editStatus">
                                        <option value="present">Present</option>
                                        <option value="late">Late</option>
                                        <option value="absent">Absent</option>
                                        <option value="wfh">WFH</option>
                                        <option value="paid_leave">Paid Leave</option>
                                        <option value="holiday">Holiday</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                    @error('editStatus')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Check In</label>
                                    <input type="time" class="form-control" wire:model.defer="editCheckIn">
                                    <div class="form-text">Late: {{ $editLateMinutes }} min
                                        ({{ $editLateHoursReadable }})</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Check Out</label>
                                    <input type="time" class="form-control" wire:model.defer="editCheckOut">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Hours Worked</label>
                                    <input type="number" step="0.1" min="0" class="form-control"
                                        wire:model.defer="editHoursWorked">
                                    @error('editHoursWorked')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Late (minutes)</label>
                                    <input type="number" min="0" class="form-control"
                                        wire:model.defer="editLateMinutes">
                                    @error('editLateMinutes')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                wire:click="closeEditModal">Cancel</button>
                            <button type="button" class="btn btn-primary" wire:click="saveAttendanceEdit">
                                <span wire:loading.remove wire:target="saveAttendanceEdit">Save</span>
                                <span wire:loading wire:target="saveAttendanceEdit"
                                    class="spinner-border spinner-border-sm"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

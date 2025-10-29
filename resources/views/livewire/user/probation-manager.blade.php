<div>
    <div class="container-fluid px-4 py-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-0 text-dark fw-bold">Probation Management</h2>
                <p class="text-muted mb-0">Manage employee probation periods and automatic conversion to permanent</p>
            </div>
            <div class="d-flex gap-2">
                <button wire:click="dryRunConversion" class="btn btn-info">
                    <i class="bi bi-eye me-2"></i>Preview Changes
                </button>
                <button wire:click="convertToPermanent" class="btn btn-success">
                    <i class="bi bi-check-circle me-2"></i>Convert to Permanent
                </button>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session()->has('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">On Probation</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['total_probation'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
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
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Eligible for Conversion</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['eligible_for_conversion'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-arrow-right-circle text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Permanent Employees</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['total_active'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-person-check text-primary" style="font-size: 2rem;"></i>
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
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Employees</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['total_users'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people text-info" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auto Conversion Info -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-gear me-2"></i>Automatic Conversion System
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="text-dark">🔄 Automatic Probation to Permanent Conversion</h6>
                        <p class="text-muted mb-2">
                            The system automatically converts employees from probation to permanent status after <strong>3 months</strong> from their joining date.
                        </p>
                        <ul class="list-unstyled text-muted">
                            <li><i class="bi bi-check-circle text-success me-2"></i>Runs daily at 3:00 AM</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Checks all probation employees</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Converts eligible employees automatically</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Logs all conversions for audit trail</li>
                        </ul>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="bg-light p-3 rounded">
                            <i class="bi bi-clock-history text-primary" style="font-size: 3rem;"></i>
                            <h6 class="mt-2 text-dark">Next Run</h6>
                            <small class="text-muted">Daily at 3:00 AM</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Search & Filters</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-dark fw-bold">Search Employee</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" wire:model.live.debounce.300ms="search" 
                                   class="form-control" placeholder="Search by name, email, or ID...">
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label text-dark fw-bold">Per Page</label>
                        <select wire:model.live="perPage" class="form-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label text-dark fw-bold">Filter</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.live="showEligibleOnly" id="eligibleOnly">
                            <label class="form-check-label" for="eligibleOnly">
                                Show only eligible for conversion
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button wire:click="$set('search', '')" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle me-2"></i>Clear Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Probation Users Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Probation Employees</h6>
                <div class="text-muted">
                    Showing {{ $probationUsers->firstItem() ?? 0 }} to {{ $probationUsers->lastItem() ?? 0 }} of {{ $probationUsers->total() }} employees
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="probationTable" width="100%" cellspacing="0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="cursor: pointer;" wire:click="sortBy('name')">
                                    Employee Name
                                    @if($sortField === 'name')
                                        @if($sortDirection === 'asc') <i class="bi bi-arrow-up"></i> @else <i class="bi bi-arrow-down"></i> @endif
                                    @endif
                                </th>
                                <th class="text-center" style="cursor: pointer;" wire:click="sortBy('device_user_id')">
                                    Employee ID
                                    @if($sortField === 'device_user_id')
                                        @if($sortDirection === 'asc') <i class="bi bi-arrow-up"></i> @else <i class="bi bi-arrow-down"></i> @endif
                                    @endif
                                </th>
                                <th class="text-center" style="cursor: pointer;" wire:click="sortBy('joining_date')">
                                    Joining Date
                                    @if($sortField === 'joining_date')
                                        @if($sortDirection === 'asc') <i class="bi bi-arrow-up"></i> @else <i class="bi bi-arrow-down"></i> @endif
                                    @endif
                                </th>
                                <th class="text-center">Months Employed</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Eligible for Conversion</th>
                                <th class="text-center">Salary</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($probationUsers as $user)
                                @php
                                    $joiningDate = \Carbon\Carbon::parse($user->joining_date);
                                    $monthsEmployed = $joiningDate->diffInMonths(\Carbon\Carbon::now());
                                    $isEligible = $monthsEmployed >= 3;
                                @endphp
                                <tr class="{{ $loop->even ? 'table-light' : '' }}">
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="me-2">
                                                <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 14px;">
                                                    {{ substr($user->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="text-start">
                                                <div class="fw-bold">{{ $user->name }}</div>
                                                <small class="text-muted">{{ $user->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $user->device_user_id ?: 'N/A' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold">{{ $joiningDate->format('M d, Y') }}</span>
                                        <br>
                                        <small class="text-muted">{{ $joiningDate->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $monthsEmployed >= 3 ? 'bg-success' : 'bg-warning' }}">
                                            {{ $monthsEmployed }} months
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning">Probation</span>
                                    </td>
                                    <td class="text-center">
                                        @if($isEligible)
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>Yes
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-clock me-1"></i>{{ 3 - $monthsEmployed }} months left
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold text-success">
                                            {{ $user->monthly_salary ? 'PKR ' . number_format($user->monthly_salary, 0) : 'Not Set' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-hourglass-split" style="font-size: 3rem;"></i>
                                            <p class="mt-2">No probation employees found matching your criteria.</p>
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
                        Showing {{ $probationUsers->firstItem() ?? 0 }} to {{ $probationUsers->lastItem() ?? 0 }} of {{ $probationUsers->total() }} employees
                    </div>
                    <div>
                        {{ $probationUsers->links() }}
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
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
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
    .text-info {
        color: #36b9cc !important;
    }
    .table-dark th {
        background-color: #5a5c69 !important;
        border-color: #5a5c69 !important;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,.075);
    }
    </style>
</div>
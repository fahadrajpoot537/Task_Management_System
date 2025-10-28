<div>
    <div class="container-fluid px-4 py-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-0 text-dark fw-bold">Salary Management</h2>
                <p class="text-muted mb-0">Manage employee salaries and employment details</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success">
                    <i class="bi bi-download me-2"></i>Export Salary Report
                </button>
                <button class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Bulk Update
                </button>
                <button wire:click="testSave" class="btn btn-warning">
                    <i class="bi bi-bug me-2"></i>Test Save
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

        <!-- Summary Cards -->
        <div class="row mb-4">
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
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">With Salary Set</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summaryStats['users_with_salary'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-currency-dollar text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Salary</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">PKR {{ number_format($summaryStats['total_salary'], 0) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-graph-up text-warning" style="font-size: 2rem;"></i>
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
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average Salary</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">PKR {{ number_format($summaryStats['avg_salary'], 0) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-calculator text-info" style="font-size: 2rem;"></i>
                            </div>
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
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button wire:click="$set('search', '')" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle me-2"></i>Clear Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salary Management Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Employee Salary Management</h6>
                <div class="text-muted">
                    Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} employees
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="salaryTable" width="100%" cellspacing="0">
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
                                <th class="text-center">Email</th>
                                <th class="text-center" style="cursor: pointer;" wire:click="sortBy('monthly_salary')">
                                    Monthly Salary
                                    @if($sortField === 'monthly_salary')
                                        @if($sortDirection === 'asc') <i class="bi bi-arrow-up"></i> @else <i class="bi bi-arrow-down"></i> @endif
                                    @endif
                                </th>
                                <th class="text-center">Bonus</th>
                                <th class="text-center">Incentive</th>
                                <th class="text-center">Joining Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr class="{{ $loop->even ? 'table-light' : '' }}">
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="me-2">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 14px;">
                                                    {{ substr($user->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="text-start">
                                                <div class="fw-bold">{{ $user->name }}</div>
                                                <small class="text-muted">{{ $user->role->name ?? 'No Role' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $user->device_user_id ?: 'N/A' }}</span>
                                    </td>
                                    <td class="text-center">{{ $user->email }}</td>
                                    <td class="text-center">
                                        @if($editingUserId === $user->id)
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">PKR</span>
                                                <input type="number" wire:model="monthlySalary" 
                                                       class="form-control" placeholder="0" step="0.01">
                                            </div>
                                        @else
                                            <span class="fw-bold text-success">
                                                {{ $user->monthly_salary ? 'PKR ' . number_format($user->monthly_salary, 0) : 'Not Set' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($editingUserId === $user->id)
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">PKR</span>
                                                <input type="number" wire:model="bonus" 
                                                       class="form-control" placeholder="0" step="0.01">
                                            </div>
                                        @else
                                            <span class="text-warning">
                                                {{ $user->bonus ? 'PKR ' . number_format($user->bonus, 0) : '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($editingUserId === $user->id)
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">PKR</span>
                                                <input type="number" wire:model="incentive" 
                                                       class="form-control" placeholder="0" step="0.01">
                                            </div>
                                        @else
                                            <span class="text-info">
                                                {{ $user->incentive ? 'PKR ' . number_format($user->incentive, 0) : '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($editingUserId === $user->id)
                                            <input type="date" wire:model="joiningDate" class="form-control form-control-sm">
                                        @else
                                            {{ $user->joining_date ? \Carbon\Carbon::parse($user->joining_date)->format('M d, Y') : 'Not Set' }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($editingUserId === $user->id)
                                            <select wire:model="employmentStatus" class="form-select form-select-sm">
                                                <option value="">Select Status</option>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="probation">Probation</option>
                                                <option value="terminated">Terminated</option>
                                            </select>
                                        @else
                                            @if($user->employment_status)
                                                <span class="badge 
                                                    @if($user->employment_status === 'active') bg-success
                                                    @elseif($user->employment_status === 'probation') bg-warning
                                                    @elseif($user->employment_status === 'inactive') bg-secondary
                                                    @else bg-danger @endif">
                                                    {{ ucfirst($user->employment_status) }}
                                                </span>
                                            @else
                                                <span class="text-muted">Not Set</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($editingUserId === $user->id)
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button wire:click="saveSalary" class="btn btn-success">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <button wire:click="cancelEdit" class="btn btn-secondary">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        @else
                                            <button wire:click="editSalary({{ $user->id }})" class="btn btn-primary btn-sm">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-people" style="font-size: 3rem;"></i>
                                            <p class="mt-2">No employees found matching your search criteria.</p>
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
                        Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} employees
                    </div>
                    <div>
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Card -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Bulk Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-dark fw-bold">Apply Salary Increase (%)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" placeholder="5" step="0.1" min="0" max="100">
                            <button class="btn btn-success">Apply to All</button>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-dark fw-bold">Set Minimum Salary</label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input type="number" class="form-control" placeholder="25000" step="100">
                            <button class="btn btn-warning">Update Below</button>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-dark fw-bold">Export Options</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-info">
                                <i class="bi bi-file-excel me-1"></i>Excel
                            </button>
                            <button class="btn btn-secondary">
                                <i class="bi bi-file-pdf me-1"></i>PDF
                            </button>
                        </div>
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
    .input-group-sm .form-control {
        font-size: 0.875rem;
    }
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    </style>
</div>
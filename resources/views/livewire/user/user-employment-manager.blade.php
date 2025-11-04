<div>
    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-0 text-dark fw-bold">User Employment Management</h2>
                <p class="text-muted mb-0">Terminate, make permanent, and edit user employment details</p>
            </div>
            <div class="d-flex gap-2">
                <input type="text" class="form-control" placeholder="Search by name, email, device id" wire:model.debounce.400ms="search" style="max-width: 280px;" />
                <select class="form-select" wire:model="perPage" style="max-width: 120px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

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

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="cursor:pointer" wire:click="sortBy('name')">Name</th>
                            <th>Email</th>
                            <th>Device User ID</th>
                            <th>Monthly Salary</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if ($editingUserId === $user->id)
                                        <input type="text" class="form-control form-control-sm" wire:model.defer="deviceUserId" />
                                    @else
                                        {{ $user->device_user_id ?? '—' }}
                                    @endif
                                </td>
                                <td style="width: 150px;">
                                    @if ($editingUserId === $user->id)
                                        <input type="number" step="0.01" class="form-control form-control-sm" wire:model.defer="monthlySalary" />
                                    @else
                                        {{ $user->monthly_salary ? number_format($user->monthly_salary, 2) : '—' }}
                                    @endif
                                </td>
                                <td style="width: 130px;">
                                    @if ($editingUserId === $user->id)
                                        <input type="time" class="form-control form-control-sm" wire:model.defer="checkInTime" />
                                    @else
                                        {{ $user->check_in_time ?? '—' }}
                                    @endif
                                </td>
                                <td style="width: 130px;">
                                    @if ($editingUserId === $user->id)
                                        <input type="time" class="form-control form-control-sm" wire:model.defer="checkOutTime" />
                                    @else
                                        {{ $user->check_out_time ?? '—' }}
                                    @endif
                                </td>
                                <td style="width: 170px;">
                                    @if ($editingUserId === $user->id)
                                        <select class="form-select form-select-sm" wire:model.defer="employmentStatus">
                                            <option value="">—</option>
                                            <option value="probation">Probation</option>
                                            <option value="permanent">Permanent</option>
                                            <option value="terminated">Terminated</option>
                                        </select>
                                    @else
                                        <span class="badge bg-{{ $user->employment_status === 'permanent' ? 'success' : ($user->employment_status === 'terminated' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($user->employment_status ?? 'unknown') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end" style="width: 260px;">
                                    @if ($editingUserId === $user->id)
                                        <button class="btn btn-sm btn-success" wire:click="saveUser"><i class="bi bi-check2 me-1"></i>Save</button>
                                        <button class="btn btn-sm btn-secondary" wire:click="cancelEdit"><i class="bi bi-x me-1"></i>Cancel</button>
                                    @else
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-primary" wire:click="editUser({{ $user->id }})"><i class="bi bi-pencil-square me-1"></i>Edit</button>
                                            <button class="btn btn-sm btn-outline-success" wire:click="makePermanent({{ $user->id }})" @if ($user->employment_status === 'permanent') disabled @endif><i class="bi bi-person-check me-1"></i>Permanent</button>
                                            <button class="btn btn-sm btn-outline-danger" wire:click="terminate({{ $user->id }})" @if ($user->employment_status === 'terminated') disabled @endif><i class="bi bi-person-x me-1"></i>Terminate</button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small">Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users</div>
                <div>{{ $users->links() }}</div>
            </div>
        </div>
    </div>
</div>



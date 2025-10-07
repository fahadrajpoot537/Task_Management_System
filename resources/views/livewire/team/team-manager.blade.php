<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Team Management</h2>
        <div class="text-muted">
            <i class="bi bi-people me-2"></i>
            Assign employees to managers
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search users..." 
                               wire:model.live="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="roleFilter">
                        <option value="">All Roles</option>
                        @foreach($this->roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="managerFilter">
                        <option value="">All Managers</option>
                        <option value="unassigned">Unassigned</option>
                        @foreach($this->managers as $manager)
                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            @if($this->users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Current Manager</th>
                                <th>Team Members</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            <strong>{{ $user->name }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ ucfirst(str_replace('_', ' ', $user->role->name)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->manager)
                                            <span class="badge bg-secondary">{{ $user->manager->name }}</span>
                                        @else
                                            <span class="text-muted">No manager assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->teamMembers->count() > 0)
                                            <span class="badge bg-success">{{ $user->teamMembers->count() }} members</span>
                                        @else
                                            <span class="text-muted">No team members</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                Assign Manager
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <button class="dropdown-item" 
                                                            wire:click="assignManager({{ $user->id }}, null)">
                                                        <span class="text-muted">No Manager</span>
                                                    </button>
                                                </li>
                                                @foreach($this->managers as $manager)
                                                    <li>
                                                        <button class="dropdown-item" 
                                                                wire:click="assignManager({{ $user->id }}, {{ $manager->id }})">
                                                            {{ $manager->name }}
                                                        </button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $this->users->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No users found</h5>
                    <p class="text-muted">No users match your current filters.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Team Structure Overview -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Team Structure</h6>
                </div>
                <div class="card-body">
                    @foreach($this->managers as $manager)
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                    {{ substr($manager->name, 0, 1) }}
                                </div>
                                <strong>{{ $manager->name }}</strong>
                                <span class="badge bg-primary ms-2">Manager</span>
                            </div>
                            @if($manager->teamMembers->count() > 0)
                                <div class="ms-4">
                                    @foreach($manager->teamMembers as $member)
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="avatar-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                                {{ substr($member->name, 0, 1) }}
                                            </div>
                                            <small>{{ $member->name }}</small>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="ms-4 text-muted">
                                    <small>No team members assigned</small>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Unassigned Employees</h6>
                </div>
                <div class="card-body">
                    @php
                        $unassignedEmployees = $this->users->where('manager_id', null)->where('role.name', 'employee');
                    @endphp
                    
                    @if($unassignedEmployees->count() > 0)
                        @foreach($unassignedEmployees as $employee)
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-sm bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                    {{ substr($employee->name, 0, 1) }}
                                </div>
                                <span>{{ $employee->name }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">All employees are assigned to managers.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
        }
    </style>
</div>

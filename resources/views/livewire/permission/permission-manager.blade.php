<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Permission Management</h2>
        <div class="text-muted">
            <i class="bi bi-info-circle me-2"></i>
            Manage permissions for each user (User-Based Permission System)
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search users by name or email..." 
                               wire:model.live="search">
                    </div>
                </div>
                <div class="col-md-6">
                    <select class="form-select" wire:model.live="roleFilter">
                        <option value="">All Roles</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($users->count() > 0 && $permissions->count() > 0)
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th style="min-width: 200px;">Permission</th>
                                @foreach($users as $user)
                                    <th class="text-center" style="min-width: 150px;">
                                        <div class="d-flex flex-column align-items-center">
                                            <strong>{{ $user->name }}</strong>
                                            <small class="text-muted">{{ $user->email }}</small>
                                            <span class="badge bg-{{ $user->role->color ?? 'secondary' }} mt-1">
                                                {{ ucfirst(str_replace('_', ' ', $user->role->name ?? 'N/A')) }}
                                            </span>
                                            @if($user->isSuperAdmin())
                                                <span class="badge bg-danger mt-1">Super Admin</span>
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissions as $permission)
                                <tr>
                                    <td>
                                        <strong>{{ $permission->display_name }}</strong>
                                        <small class="text-muted d-block">{{ $permission->name }}</small>
                                    </td>
                                    @foreach($users as $user)
                                        <td class="text-center">
                                            @if($user->isSuperAdmin())
                                                <span class="badge bg-danger" title="Super Admin - All Permissions">✓</span>
                                            @else
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="permission_{{ $permission->id }}_user_{{ $user->id }}"
                                                           {{ $this->hasPermission($user->id, $permission->id) ? 'checked' : '' }}
                                                           wire:click="togglePermission({{ $user->id }}, {{ $permission->id }})">
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-shield-check text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No users or permissions found</h5>
                    <p class="text-muted">
                        @if($users->count() === 0)
                            No users match your search criteria.
                        @else
                            No permissions available.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions for Users -->
    @if($users->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($users as $user)
                        <div class="col-md-3 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $user->name }}</h6>
                                    <p class="card-text text-muted small">{{ $user->email }}</p>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-success" 
                                                wire:click="assignAllPermissions({{ $user->id }})"
                                                onclick="return confirm('Assign all permissions to {{ $user->name }}?')">
                                            <i class="bi bi-check-all"></i> All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                wire:click="clearAllPermissions({{ $user->id }})"
                                                onclick="return confirm('Clear all permissions from {{ $user->name }}?')">
                                            <i class="bi bi-x-circle"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="alert alert-info mt-4">
        <h6><i class="bi bi-lightbulb me-2"></i>Permission Guidelines:</h6>
        <ul class="mb-0">
            <li><strong>Super Admin:</strong> Automatically has all permissions. Cannot be modified.</li>
            <li><strong>User-Based System:</strong> Permissions are assigned directly to users, not roles.</li>
            <li><strong>Individual Assignment:</strong> Each user can have custom permission sets.</li>
            <li><strong>Quick Actions:</strong> Use the quick action buttons to assign or clear all permissions for a user.</li>
        </ul>
    </div>
</div>

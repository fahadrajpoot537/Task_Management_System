<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>User Permissions</h2>
            <p class="text-muted mb-0">Managing permissions for: <strong>{{ $user->name }}</strong> ({{ $user->email }})</p>
        </div>
        <div>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Users
            </a>
        </div>
    </div>

    <!-- User Info Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>User Information</h5>
                    <p><strong>Name:</strong> {{ $user->name }}</p>
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p><strong>Role:</strong> 
                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $user->role->name)) }}</span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5>Permission Summary</h5>
                    <p><strong>Role Permissions:</strong> {{ $this->userRolePermissions->count() }}</p>
                    <p><strong>Custom Permissions:</strong> {{ $this->userCustomPermissions->count() }}</p>
                    <p><strong>Total Permissions:</strong> {{ $this->userRolePermissions->count() + $this->userCustomPermissions->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Quick Actions</h5>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-warning" wire:click="resetToRolePermissions" 
                        onclick="return confirm('Reset user permissions to role defaults?')">
                    <i class="bi bi-arrow-clockwise me-2"></i>Reset to Role Defaults
                </button>
                <button type="button" class="btn btn-danger" wire:click="clearAllCustomPermissions" 
                        onclick="return confirm('Clear all custom permissions? This will remove all permissions except role defaults.')">
                    <i class="bi bi-x-circle me-2"></i>Clear Custom Permissions
                </button>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" placeholder="Search permissions..." 
                       wire:model.live="search">
            </div>
        </div>
    </div>

    <!-- Permissions Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Available Permissions</h5>
        </div>
        <div class="card-body">
            @if($allPermissions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">Status</th>
                                <th>Permission</th>
                                <th>Description</th>
                                <th>Source</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allPermissions as $permission)
                                @php
                                    $hasRolePermission = $this->userRolePermissions->contains('id', $permission->id);
                                    $hasCustomPermission = in_array($permission->id, $userPermissions);
                                    $isEnabled = $hasRolePermission || $hasCustomPermission;
                                @endphp
                                <tr class="{{ $isEnabled ? 'table-success' : '' }}">
                                    <td>
                                        @if($hasRolePermission)
                                            <span class="badge bg-primary" title="From Role">R</span>
                                        @elseif($hasCustomPermission)
                                            <span class="badge bg-warning" title="Custom Permission">C</span>
                                        @else
                                            <span class="badge bg-secondary" title="Not Assigned">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $permission->name }}</strong>
                                    </td>
                                    <td>{{ $permission->description ?? 'No description available' }}</td>
                                    <td>
                                        @if($hasRolePermission)
                                            <span class="text-primary">Role: {{ ucfirst(str_replace('_', ' ', $user->role->name)) }}</span>
                                        @elseif($hasCustomPermission)
                                            <span class="text-warning">Custom</span>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($hasRolePermission)
                                            <span class="text-muted">Cannot modify (from role)</span>
                                        @else
                                            <button type="button" 
                                                    class="btn btn-sm {{ $hasCustomPermission ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                    wire:click="togglePermission({{ $permission->id }})">
                                                @if($hasCustomPermission)
                                                    <i class="bi bi-x-circle me-1"></i>Remove
                                                @else
                                                    <i class="bi bi-plus-circle me-1"></i>Add
                                                @endif
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-shield-check text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No permissions found</h5>
                    <p class="text-muted">No permissions match your current search.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-4">
        <div class="card-body">
            <h6>Legend:</h6>
            <div class="d-flex gap-3">
                <span class="badge bg-primary">R</span> <small>Permission from Role</small>
                <span class="badge bg-warning">C</span> <small>Custom Permission</small>
                <span class="badge bg-secondary">-</span> <small>Not Assigned</small>
            </div>
            <div class="mt-2">
                <small class="text-muted">
                    <strong>Note:</strong> Role permissions cannot be modified individually. 
                    To change role permissions, modify the role itself in the Permissions section.
                </small>
            </div>
        </div>
    </div>
</div>

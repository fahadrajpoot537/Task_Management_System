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
                    <p><strong>Assigned Permissions:</strong> {{ count($userPermissions) }}</p>
                    <p><strong>Total Available:</strong> {{ $allPermissions->count() }}</p>
                    @if($user->isSuperAdmin())
                        <p class="text-success"><strong>Super Admin:</strong> Has all permissions automatically</p>
                    @endif
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
                <button type="button" class="btn btn-success" wire:click="assignAllPermissions" 
                        onclick="return confirm('Assign all permissions to this user?')">
                    <i class="bi bi-check-all me-2"></i>Assign All Permissions
                </button>
                <button type="button" class="btn btn-danger" wire:click="clearAllPermissions" 
                        onclick="return confirm('Clear all permissions from this user? This will remove all assigned permissions.')">
                    <i class="bi bi-x-circle me-2"></i>Clear All Permissions
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
                                    $hasPermission = in_array($permission->id, $userPermissions);
                                    $isSuperAdmin = $user->isSuperAdmin();
                                @endphp
                                <tr class="{{ $hasPermission || $isSuperAdmin ? 'table-success' : '' }}">
                                    <td>
                                        @if($isSuperAdmin)
                                            <span class="badge bg-danger" title="Super Admin - All Permissions">SA</span>
                                        @elseif($hasPermission)
                                            <span class="badge bg-success" title="Assigned">✓</span>
                                        @else
                                            <span class="badge bg-secondary" title="Not Assigned">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $permission->display_name }}</strong>
                                        <small class="text-muted d-block">{{ $permission->name }}</small>
                                    </td>
                                    <td>{{ $permission->description ?? ucfirst(str_replace('_', ' ', $permission->name)) }}</td>
                                    <td>
                                        @if($isSuperAdmin)
                                            <span class="text-danger">Super Admin (Automatic)</span>
                                        @elseif($hasPermission)
                                            <span class="text-success">Assigned</span>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isSuperAdmin)
                                            <span class="text-muted">Cannot modify (Super Admin)</span>
                                        @else
                                            <button type="button" 
                                                    class="btn btn-sm {{ $hasPermission ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                    wire:click="togglePermission({{ $permission->id }})">
                                                @if($hasPermission)
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
                <span class="badge bg-danger">SA</span> <small>Super Admin (All Permissions Automatic)</small>
                <span class="badge bg-success">✓</span> <small>Permission Assigned</small>
                <span class="badge bg-secondary">-</span> <small>Not Assigned</small>
            </div>
            <div class="mt-2">
                <small class="text-muted">
                    <strong>Note:</strong> Permissions are assigned directly to users. 
                    Super admin automatically has all permissions and cannot have them removed.
                </small>
            </div>
        </div>
    </div>
</div>

<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Permission Management</h2>
        <div class="text-muted">
            <i class="bi bi-info-circle me-2"></i>
            Manage permissions for each role
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            @foreach($roles as $role)
                                <th class="text-center">
                                    {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $permission)
                            <tr>
                                <td>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $permission->name)) }}</strong>
                                </td>
                                @foreach($roles as $role)
                                    <td class="text-center">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="permission_{{ $permission->id }}_role_{{ $role->id }}"
                                                   {{ $this->hasPermission($role->id, $permission->id) ? 'checked' : '' }}
                                                   wire:click="togglePermission({{ $role->id }}, {{ $permission->id }})">
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <h6><i class="bi bi-lightbulb me-2"></i>Permission Guidelines:</h6>
        <ul class="mb-0">
            <li><strong>Super Admin:</strong> Should have all permissions</li>
            <li><strong>Admin:</strong> Can manage projects and tasks, but not users</li>
            <li><strong>Manager:</strong> Can manage their team's projects and tasks</li>
            <li><strong>Employee:</strong> Can create projects and tasks, view assigned tasks</li>
        </ul>
    </div>
</div>

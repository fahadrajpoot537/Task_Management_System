<div>
    <!-- Header Section -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2 text-white fw-bold">
                        <i class="bi bi-shield-check me-3"></i>Role Management
                    </h2>
                    <p class="mb-0 text-white-50 fs-6">Create and manage user roles with custom permissions</p>
                </div>
                <div class="d-flex gap-2">
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                        <button class="btn btn-light btn-lg px-4 py-2" wire:click="create">
                            <i class="bi bi-plus-circle me-2"></i>Create Role
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Role Creation/Edit Form -->
    @if($showForm)
        <div class="card mb-4">
            <div class="card-header bg-gradient-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">
                        <i class="bi bi-{{ $editingRole ? 'pencil' : 'plus' }}-circle me-2"></i>
                        {{ $editingRole ? 'Edit Role' : 'Create New Role' }}
                    </h5>
                    <button class="btn btn-outline-secondary btn-sm" wire:click="toggleForm">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form wire:submit="save">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">
                                Role Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" wire:model="name" required
                                   placeholder="Enter role name...">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="color" class="form-label fw-semibold">
                                Role Color <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('color') is-invalid @enderror" 
                                    id="color" wire:model="color" required>
                                <option value="primary">Primary (Blue)</option>
                                <option value="secondary">Secondary (Gray)</option>
                                <option value="success">Success (Green)</option>
                                <option value="danger">Danger (Red)</option>
                                <option value="warning">Warning (Yellow)</option>
                                <option value="info">Info (Cyan)</option>
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                            </select>
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">
                                Role Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" wire:model="description" rows="3" required
                                      placeholder="Describe the role's responsibilities and permissions..."></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Permissions</label>
                            <div class="row g-2">
                                @foreach($permissions as $permission)
                                    <div class="col-md-4 col-lg-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="permission_{{ $permission->id }}" 
                                                   value="{{ $permission->id }}" 
                                                   wire:model="selectedPermissions">
                                            <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                {{ $permission->display_name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <button type="button" class="btn btn-outline-secondary" wire:click="toggleForm">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>{{ $editingRole ? 'Update Role' : 'Create Role' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Roles List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>Available Roles
            </h5>
        </div>
        <div class="card-body">
            @if($this->roles->count() > 0)
                <div class="row g-3">
                    @foreach($this->roles as $role)
                        <div class="col-lg-6 col-xl-4">
                            <div class="card role-card h-100">
                                <div class="card-header bg-gradient-light">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1 fw-bold">
                                                <span class="badge bg-{{ $role->color }} me-2">{{ $role->name }}</span>
                                                @if($role->is_system_role)
                                                    <i class="bi bi-shield-check text-primary" title="System Role"></i>
                                                @endif
                                            </h6>
                                            <small class="text-muted">
                                                Hierarchy Level: {{ $role->hierarchy_level }}
                                            </small>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                @if(auth()->user()->role->canManageRole($role))
                                                    <li>
                                                        <button class="dropdown-item" wire:click="edit({{ $role->id }})">
                                                            <i class="bi bi-pencil me-2"></i>Edit Role
                                                        </button>
                                                    </li>
                                                @endif
                                                @if(auth()->user()->role->canManageRole($role) && !$role->is_system_role)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" 
                                                                onclick="confirmDelete({{ $role->id }})">
                                                            <i class="bi bi-trash me-2"></i>Delete Role
                                                        </button>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-muted mb-3">
                                        {{ Str::limit($role->description, 100) }}
                                    </p>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">Users</small>
                                            <span class="badge bg-secondary">{{ $role->users->count() }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">Permissions</small>
                                            <span class="badge bg-info">{{ $role->permissions->count() }}</span>
                                        </div>
                                    </div>

                                    @if($role->permissions->count() > 0)
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-2">Permissions:</small>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($role->permissions->take(3) as $permission)
                                                    <span class="badge bg-light text-dark small">
                                                        {{ $permission->display_name }}
                                                    </span>
                                                @endforeach
                                                @if($role->permissions->count() > 3)
                                                    <span class="badge bg-light text-dark small">
                                                        +{{ $role->permissions->count() - 3 }} more
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-shield-check text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No roles found</h5>
                    <p class="text-muted">Get started by creating your first custom role.</p>
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                        <button class="btn btn-primary" wire:click="create">
                            <i class="bi bi-plus-circle me-2"></i>Create Role
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this role? This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This role will be removed from all users assigned to it.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .role-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(59, 130, 246, 0.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .role-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.2);
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
        }
        
        .bg-gradient-light {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);
        }
        
        .card-header.bg-gradient-light {
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 0.75rem;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--blue-700);
        }
        
        .dropdown-item.text-danger:hover {
            background-color: rgba(220, 38, 38, 0.1);
            color: #dc2626;
        }
        
        @media (max-width: 768px) {
            .card-header.bg-gradient-primary {
                padding: 1rem;
            }
            
            .card-header.bg-gradient-primary h2 {
                font-size: 1.5rem;
            }
            
            .btn-lg {
                padding: 0.75rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>

    <script>
        let roleIdToDelete = null;

        function confirmDelete(roleId) {
            roleIdToDelete = roleId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (roleIdToDelete) {
                @this.delete(roleIdToDelete);
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                modal.hide();
            }
        });
    </script>
</div>

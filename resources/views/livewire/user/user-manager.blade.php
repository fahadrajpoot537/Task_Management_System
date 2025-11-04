<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Employee Management</h2>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isManager())
            <button class="btn btn-primary" wire:click="openForm" wire:loading.attr="disabled">
                <i class="bi bi-person-plus me-2"></i>Add Employee
                <span wire:loading wire:target="openForm">Loading...</span>
            </button>
        @endif
    </div>


    <!-- Create User Form -->
    @if($showCreateForm)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Create New Employee</h5>
                <button type="button" class="btn-close" wire:click="hideCreateForm"></button>
            </div>
            <div class="card-body">
                <form wire:submit="createUser">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" wire:model="name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" wire:model="email" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label">Role</label>
                            <input type="text" class="form-control" value="Employee" readonly>
                            <small class="text-muted">Only employees can be created here</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="manager_id" class="form-label">Assign to Manager</label>
                            <select class="form-select @error('manager_id') is-invalid @enderror" 
                                    id="manager_id" wire:model="manager_id">
                                <option value="">No manager</option>
                                @foreach($this->managers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                @endforeach
                            </select>
                            @error('manager_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendInvitation" wire:model="sendInvitation">
                            <label class="form-check-label" for="sendInvitation">
                                Send email invitation with temporary password
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="hideCreateForm">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Create Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search users..." 
                               wire:model.live="search">
                    </div>
                </div>
                <div class="col-md-3">
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
                                <th>Manager</th>
                                <th>Team Size</th>
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
                                            <span class="text-muted">No manager</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->teamMembers->count() > 0)
                                            <span class="badge bg-success">{{ $user->teamMembers->count() }} members</span>
                                        @else
                                            <span class="text-muted">No team</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('users.permissions', $user->id) }}" 
                                               class="btn btn-sm btn-outline-info" title="Manage Permissions">
                                                <i class="bi bi-shield-check"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    wire:click="openEdit({{ $user->id }})" title="Edit Employment">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            @if(auth()->user()->isSuperAdmin())
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        wire:click="resendInvitation({{ $user->id }})"
                                                        title="Resend Invitation">
                                                    <i class="bi bi-envelope"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete({{ $user->id }})"
                                                        title="Delete Employee">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                       <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $this->users->links('pagination::bootstrap-5') }}
                </div>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this user? This action cannot be undone and will remove all associated data.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Employment Modal -->
    <div class="modal fade" id="editEmploymentModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Employment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="cancelEdit"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Monthly Salary</label>
                            <input type="number" step="0.01" class="form-control @error('edit_monthly_salary') is-invalid @enderror" wire:model.defer="edit_monthly_salary">
                            @error('edit_monthly_salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Device User ID</label>
                            <input type="text" class="form-control @error('edit_device_user_id') is-invalid @enderror" wire:model.defer="edit_device_user_id">
                            @error('edit_device_user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check In Time</label>
                            <input type="time" class="form-control @error('edit_check_in_time') is-invalid @enderror" wire:model.defer="edit_check_in_time">
                            @error('edit_check_in_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check Out Time</label>
                            <input type="time" class="form-control @error('edit_check_out_time') is-invalid @enderror" wire:model.defer="edit_check_out_time">
                            @error('edit_check_out_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employment Status</label>
                            <select class="form-select @error('edit_employment_status') is-invalid @enderror" wire:model.defer="edit_employment_status">
                                <option value="">—</option>
                                <option value="probation">Probation</option>
                                <option value="permanent">Permanent</option>
                                <option value="terminated">Terminated</option>
                            </select>
                            @error('edit_employment_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="cancelEdit">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="saveEdit"><i class="bi bi-check2 me-1"></i>Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let userIdToDelete = null;

        function confirmDelete(userId) {
            userIdToDelete = userId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (userIdToDelete) {
                @this.deleteUser(userIdToDelete);
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                modal.hide();
            }
        });

        // Edit modal open/close listeners
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-edit-modal', () => {
                const modal = new bootstrap.Modal(document.getElementById('editEmploymentModal'));
                modal.show();
            });
            Livewire.on('close-edit-modal', () => {
                const instance = bootstrap.Modal.getInstance(document.getElementById('editEmploymentModal'));
                if (instance) instance.hide();
            });
        });
    </script>

    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
        }
    </style>
</div>

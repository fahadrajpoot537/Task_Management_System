<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manager Management</h2>
        @if(auth()->user()->isSuperAdmin())
            <button class="btn btn-primary" wire:click="openForm" wire:loading.attr="disabled">
                <i class="bi bi-person-plus me-2"></i>Add Manager
                <span wire:loading wire:target="openForm">Loading...</span>
            </button>
        @endif
    </div>


    <!-- Create Manager Form -->
    @if($showCreateForm)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Create New Manager</h5>
                <button type="button" class="btn-close" wire:click="hideCreateForm"></button>
            </div>
            <div class="card-body">
                <form wire:submit="createManager">
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
                            <i class="bi bi-check-circle me-2"></i>Create Manager
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search managers..." 
                               wire:model.live="search">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Managers Table -->
    <div class="card">
        <div class="card-body">
            @if($this->managers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Team Size</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->managers as $manager)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($manager->name, 0, 1) }}
                                            </div>
                                            <strong>{{ $manager->name }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $manager->email }}</td>
                                    <td>
                                        @if($manager->teamMembers->count() > 0)
                                            <span class="badge bg-success">{{ $manager->teamMembers->count() }} members</span>
                                        @else
                                            <span class="text-muted">No team</span>
                                        @endif
                                    </td>
                                    <td>{{ $manager->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('users.permissions', $manager->id) }}" 
                                               class="btn btn-sm btn-outline-info" title="Manage Permissions">
                                                <i class="bi bi-shield-check"></i>
                                            </a>
                                            @if(auth()->user()->isSuperAdmin())
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        wire:click="resendInvitation({{ $manager->id }})"
                                                        title="Resend Invitation">
                                                    <i class="bi bi-envelope"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete({{ $manager->id }})"
                                                        title="Delete Manager">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $this->managers->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No managers found</h5>
                    <p class="text-muted">No managers match your current search.</p>
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
                    <p>Are you sure you want to delete this manager? This action cannot be undone and will remove all associated data.</p>
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Make sure this manager has no team members before deleting.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let managerIdToDelete = null;

        function confirmDelete(managerId) {
            managerIdToDelete = managerId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (managerIdToDelete) {
                @this.deleteManager(managerIdToDelete);
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                modal.hide();
            }
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

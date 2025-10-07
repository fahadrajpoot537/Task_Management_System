<div>
    <!-- Header Section -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2 text-white fw-bold">
                        <i class="bi bi-folder me-3"></i>Project Management
                    </h2>
                    <p class="mb-0 text-white-50 fs-6">Create, manage, and track your projects efficiently</p>
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ route('projects.create') }}" class="btn btn-light btn-lg px-4 py-2">
                        <i class="bi bi-plus-circle me-2"></i>Create Project
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-primary"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" placeholder="Search projects by title or description..." 
                               wire:model.live="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="sortField">
                        <option value="created_at">Sort by Date</option>
                        <option value="title">Sort by Title</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="sortDirection">
                        <option value="desc">Newest First</option>
                        <option value="asc">Oldest First</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Grid -->
    @if($this->projects->count() > 0)
        <div class="row g-4">
            @foreach($this->projects as $project)
                <div class="col-lg-6 col-xl-4">
                    <div class="card project-card h-100">
                        <div class="card-header bg-gradient-light">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1 fw-bold text-primary">
                                        <i class="bi bi-folder me-2"></i>{{ $project->title }}
                                    </h5>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i>{{ $project->createdBy->name }}
                                    </small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('projects.details', $project->id) }}">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('tasks.create') }}?project={{ $project->id }}">
                                                <i class="bi bi-plus-circle me-2"></i>Add Task
                                            </a>
                                        </li>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $project->created_by_user_id === auth()->id())
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="confirmDelete({{ $project->id }})">
                                                    <i class="bi bi-trash me-2"></i>Delete Project
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="fw-bold text-primary">{{ $project->total_tasks_count }}</div>
                                        <small class="text-muted">Tasks</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="fw-bold text-success">{{ $project->completed_tasks_count }}</div>
                                        <small class="text-muted">Completed</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Progress</small>
                                    <small class="fw-bold">{{ $project->progress_percentage }}%</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-gradient-success" 
                                         style="width: {{ $project->progress_percentage }}%"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>{{ $project->created_at->format('M d, Y') }}
                                </small>
                                <a href="{{ route('projects.details', $project->id) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-arrow-right me-1"></i>View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $this->projects->links() }}
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-folder text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">No projects found</h4>
                <p class="text-muted mb-4">Get started by creating your first project to organize your tasks and track progress.</p>
                <a href="{{ route('projects.create') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Create Your First Project
                </a>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this project? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .project-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(59, 130, 246, 0.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.2);
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--blue-600) 0%, var(--blue-700) 50%, var(--blue-800) 100%);
        }
        
        .bg-gradient-light {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .card-header.bg-gradient-light {
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }
        
        .progress {
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        .progress-bar.bg-gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            .project-card {
                margin-bottom: 1rem;
            }
            
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
        
        @media (max-width: 576px) {
            .row.g-4 {
                margin: 0 -0.5rem;
            }
            
            .row.g-4 > * {
                padding: 0 0.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .card-header {
                padding: 0.75rem;
            }
        }
    </style>

    <script>
        let projectIdToDelete = null;

        function confirmDelete(projectId) {
            projectIdToDelete = projectId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (projectIdToDelete) {
                @this.deleteProject(projectIdToDelete);
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                modal.hide();
            }
        });
    </script>
</div>

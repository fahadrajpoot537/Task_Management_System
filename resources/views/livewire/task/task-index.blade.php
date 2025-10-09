<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Tasks</h2>
        <a href="{{ route('tasks.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Create Task
        </a>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body bg-light">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search tasks..." 
                               wire:model.live="search">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model.live="priorityFilter">
                        <option value="">All Priority</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isManager())
                <div class="col-12 col-md-2">
                    <select class="form-select" wire:model.live="userFilter">
                        <option value="">All Users</option>
                        @foreach($this->availableUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            
            <!-- Mobile Filter Toggle -->
            <div class="d-md-none mt-2">
                <button class="btn btn-outline-secondary btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#mobileFiltersIndex" aria-expanded="false">
                    <i class="bi bi-funnel me-2"></i>More Filters
                </button>
                <div class="collapse mt-2" id="mobileFiltersIndex">
                    <div class="row g-2">
                        <div class="col-6">
                            <select class="form-select form-select-sm" wire:model.live="statusFilter">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <select class="form-select form-select-sm" wire:model.live="priorityFilter">
                                <option value="">All Priority</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isManager())
                        <div class="col-12">
                            <select class="form-select form-select-sm" wire:model.live="userFilter">
                                <option value="">All Users</option>
                                @foreach($this->availableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="card">
        <div class="card-body">
            @if($this->tasks->count() > 0)
                <div class="table-responsive" style="overflow-x: auto; max-height: 70vh;">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th wire:click="sortBy('title')" style="cursor: pointer; min-width: 200px;">
                                    Title
                                    @if($sortField === 'title')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th style="min-width: 120px;">Project</th>
                                <th style="min-width: 150px;">Assigned To</th>
                                <th wire:click="sortBy('priority')" style="cursor: pointer; min-width: 100px;">
                                    Priority
                                    @if($sortField === 'priority')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th wire:click="sortBy('status')" style="cursor: pointer; min-width: 120px;">
                                    Status
                                    @if($sortField === 'status')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th wire:click="sortBy('due_date')" style="cursor: pointer; min-width: 120px;">
                                    Due Date
                                    @if($sortField === 'due_date')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th style="min-width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->tasks as $task)
                                <tr class="{{ $task->is_overdue ? 'table-danger' : '' }}">
                                    <td>
                                        <div>
                                            <strong>{{ $task->title }}</strong>
                                            @if($task->is_overdue)
                                                <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue"></i>
                                            @endif
                                        </div>
                                        <small class="text-muted">
                                            {{ Str::limit($task->description, 50) }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $task->project->title }}</span>
                                    </td>
                                    <td>{{ $task->assignedTo->name }}</td>
                                    <td>
                                        <span class="badge {{ $task->priority_badge_class }}">
                                            {{ ucfirst($task->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <span class="badge {{ $task->status_badge_class }}">
                                                    {{ ucfirst($task->status) }}
                                                </span>
                                            </button>
                                            <ul class="dropdown-menu">
                                                @foreach($this->statuses as $status)
                                                    @if(!auth()->user()->isEmployee() || $status->name !== 'Complete')
                                                        <li>
                                                            <button class="dropdown-item" 
                                                                    wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                                                <span class="badge bg-{{ $status->color }} me-2">{{ $status->name }}</span>
                                                            </button>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        @if($task->due_date)
                                            <small class="text-muted">
                                                {{ $task->due_date->format('M d, Y') }}
                                            </small>
                                        @else
                                            <small class="text-muted">No due date</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('tasks.details', $task->id) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if(auth()->user()->isSuperAdmin() || $task->assigned_by_user_id === auth()->id())
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete({{ $task->id }})">
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
                    {{ $this->tasks->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-list-task text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No tasks found</h5>
                    <p class="text-muted">Get started by creating your first task.</p>
                    <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create Task
                    </a>
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
                    <p>Are you sure you want to delete this task? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Responsive table styles */
        .table-responsive {
            position: relative;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            overflow-x: auto !important;
            overflow-y: auto;
        }
        
        .table-responsive .sticky-top {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #212529 !important;
        }
        
        .table-responsive table {
            min-width: 900px !important; /* Ensure table has minimum width for horizontal scroll */
            width: 100%;
        }
        
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                border: none;
            }
            
            .table {
                font-size: 0.875rem;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
            }
            
            .dropdown-menu {
                font-size: 0.8rem;
            }
            
            .table-responsive table {
                min-width: 700px !important; /* Smaller minimum width on mobile */
            }
        }
        
        @media (max-width: 576px) {
            .table {
                font-size: 0.8rem;
            }
            
            .btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }
            
            .badge {
                font-size: 0.65rem;
            }
        }
    </style>

    <script>
        let taskIdToDelete = null;

        function confirmDelete(taskId) {
            taskIdToDelete = taskId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (taskIdToDelete) {
                @this.deleteTask(taskIdToDelete);
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                modal.hide();
            }
        });
    </script>
</div>

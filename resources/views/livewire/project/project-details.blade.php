<div>
    <!-- Project Header -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2 text-white fw-bold">
                        <i class="bi bi-folder me-3"></i>{{ $project->title }}
                    </h2>
                    <p class="mb-0 text-white-50 fs-6">
                        <i class="bi bi-person me-2"></i>Created by {{ $project->createdBy->name }}
                        <span class="mx-3">•</span>
                        <i class="bi bi-calendar me-2"></i>{{ $project->created_at->format('M d, Y') }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('projects.index') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-2"></i>Back to Projects
                    </a>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Project Overview -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Project Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="project-description">
                        {!! $project->description !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Project Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="fs-4 fw-bold text-primary">{{ $this->projectStats['total'] }}</div>
                                <div class="text-muted small">Total Tasks</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="fs-4 fw-bold text-success">{{ $this->projectStats['completed'] }}</div>
                                <div class="text-muted small">Completed</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="fs-4 fw-bold text-warning">{{ $this->projectStats['in_progress'] }}</div>
                                <div class="text-muted small">In Progress</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="fs-4 fw-bold text-secondary">{{ $this->projectStats['pending'] }}</div>
                                <div class="text-muted small">Pending</div>
                            </div>
                        </div>
                    </div>
                    
                    @if($this->projectStats['overdue'] > 0)
                        <div class="mt-3 p-3 bg-danger bg-opacity-10 rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                                <div>
                                    <div class="fw-bold text-danger">{{ $this->projectStats['overdue'] }}</div>
                                    <div class="text-muted small">Overdue Tasks</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Progress</span>
                            <span class="fw-bold">{{ $this->projectStats['progress_percentage'] }}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-gradient-success" 
                                 style="width: {{ $this->projectStats['progress_percentage'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Section -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-task me-2"></i>Project Tasks
                </h5>
                <div class="d-flex gap-2">
                   
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filters -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search tasks..." 
                               wire:model.live="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Submit for Approval">Submit for Approval</option>
                        <option value="Complete">Complete</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="priorityFilter">
                        <option value="">All Priority</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>

            <!-- Tasks Table -->
            @if($this->tasks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Assignee</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->tasks as $task)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                @if($task->priority && $task->priority->name === 'High')
                                                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                                                @elseif($task->priority && $task->priority->name === 'Medium')
                                                    <i class="bi bi-dash-circle-fill text-warning fs-5"></i>
                                                @else
                                                    <i class="bi bi-info-circle-fill text-info fs-5"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <strong class="fs-6">{{ $task->title }}</strong>
                                                @if($task->description)
                                                    <br><small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($task->assignedTo)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    {{ substr($task->assignedTo->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $task->assignedTo->name }}</div>
                                                    @if($task->assignedTo->role)
                                                        <small class="text-muted">{{ $task->assignedTo->role->name }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->priority)
                                            <span class="badge bg-{{ $task->priority->color }}">
                                                {{ $task->priority->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">No Priority</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->status)
                                            <span class="badge bg-{{ $task->status->color }}">
                                                {{ $task->status->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">No Status</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->due_date)
                                            <span class="badge bg-{{ $task->due_date < now() && (!$task->status || $task->status->name !== 'Complete') ? 'danger' : 'secondary' }}">
                                                {{ $task->due_date->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">No due date</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('tasks.details', $task->id) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
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
                    <p class="text-muted">Get started by creating your first task for this project.</p>
                    <a href="{{ route('tasks.create') }}?project={{ $project->id }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create Task
                    </a>
                </div>
            @endif
        </div>
    </div>

    <style>
        .project-description {
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .avatar-sm {
            width: 40px;
            height: 40px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--blue-600) 0%, var(--blue-700) 50%, var(--blue-800) 100%);
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        /* Responsive Design */
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
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
            }
            
            .avatar-sm {
                width: 32px;
                height: 32px;
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .row.g-3 {
                margin: 0 -0.5rem;
            }
            
            .row.g-3 > * {
                padding: 0 0.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .card-header {
                padding: 0.75rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }
    </style>
</div>

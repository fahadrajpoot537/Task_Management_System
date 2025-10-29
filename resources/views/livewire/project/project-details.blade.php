<div class="project-details-container">
    <!-- Compact Project Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <i class="bi bi-folder text-primary me-2"></i>
            <h4 class="mb-0 text-primary fw-bold">Project Details</h4>
        </div>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <!-- Compact Project Header Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-gradient-primary text-white border-0 py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-folder-open me-2"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">{{ $project->title }}</h6>
                        <p class="mb-0 text-white-50 small mt-1">
                            <i class="bi bi-person me-1"></i>Created by {{ $project->createdBy->name }}
                            <span class="mx-2">•</span>
                            <i class="bi bi-calendar me-1"></i>{{ $project->created_at->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Project Overview -->
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-gradient-primary text-white border-0 py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-info-circle me-2"></i>Project Overview
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="project-description">
                        {!! $project->description !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-gradient-primary text-white border-0 py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-graph-up me-2"></i>Statistics
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-card-compact">
                                <i class="bi bi-list-task text-primary me-1"></i>
                                <div>
                                    <div class="stat-number text-primary">{{ $this->projectStats['total'] }}</div>
                                    <div class="stat-label">Total</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card-compact">
                                <i class="bi bi-check-circle text-success me-1"></i>
                                <div>
                                    <div class="stat-number text-success">{{ $this->projectStats['completed'] }}</div>
                                    <div class="stat-label">Done</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card-compact">
                                <i class="bi bi-play-circle text-warning me-1"></i>
                                <div>
                                    <div class="stat-number text-warning">{{ $this->projectStats['in_progress'] }}</div>
                                    <div class="stat-label">Active</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card-compact">
                                <i class="bi bi-pause-circle text-secondary me-1"></i>
                                <div>
                                    <div class="stat-number text-secondary">{{ $this->projectStats['pending'] }}</div>
                                    <div class="stat-label">Pending</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($this->projectStats['overdue'] > 0)
                        <div class="alert-card-compact alert-danger mt-2">
                            <i class="bi bi-exclamation-triangle text-danger me-1"></i>
                            <div>
                                <div class="fw-bold text-danger">{{ $this->projectStats['overdue'] }}</div>
                                <div class="text-muted small">Overdue</div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Progress</span>
                            <span class="fw-bold small">{{ $this->projectStats['progress_percentage'] }}%</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-gradient-success" 
                                 style="width: {{ $this->projectStats['progress_percentage'] }}%; border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Tasks Section -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient-primary text-white border-0 py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-list-task me-2"></i>Project Tasks
                </h6>
            </div>
        </div>
        
        <div class="card-body p-3">
            <!-- Compact Filters -->
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <div class="search-box-compact">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control form-control-sm" placeholder="Search tasks..." 
                               wire:model.live="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="statusFilter">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Submit for Approval">Submit for Approval</option>
                        <option value="Complete">Complete</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="priorityFilter">
                        <option value="">All Priority</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>

            <!-- Compact Tasks Table -->
            @if($this->tasks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
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
                                            <div class="me-2">
                                                @if($task->priority && $task->priority->name === 'High')
                                                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                                @elseif($task->priority && $task->priority->name === 'Medium')
                                                    <i class="bi bi-dash-circle-fill text-warning"></i>
                                                @else
                                                    <i class="bi bi-info-circle-fill text-info"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <strong class="small">{{ $task->title }}</strong>
                                                @if($task->description)
                                                    <br><small class="text-muted">{{ Str::limit($task->description, 30) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($task->assignedTo)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    {{ substr($task->assignedTo->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="small fw-semibold">{{ $task->assignedTo->name }}</div>
                                                    @if($task->assignedTo->role)
                                                        <small class="text-muted">{{ $task->assignedTo->role->name }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted small">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->priority)
                                            <span class="badge bg-{{ $task->priority->color }} small">
                                                {{ $task->priority->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary small">No Priority</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->status)
                                            <span class="badge bg-{{ $task->status->color }} small">
                                                {{ $task->status->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary small">No Status</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->due_date)
                                            <span class="badge bg-{{ $task->due_date < now() && (!$task->status || $task->status->name !== 'Complete') ? 'danger' : 'secondary' }} small">
                                                {{ $task->due_date->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted small">No due date</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('tasks.details', $task->id) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $this->tasks->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-list-task text-muted" style="font-size: 2rem;"></i>
                    <h6 class="text-muted mt-2">No tasks found</h6>
                    <p class="text-muted small">Get started by creating your first task for this project.</p>
                    <a href="{{ route('tasks.create') }}?project={{ $project->id }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Create Task
                    </a>
                </div>
            @endif
        </div>
    </div>

    <style>
        /* Minimized Project Details Styles with Dark Theme Support */
        :root {
            --bg-primary: #f8f9fa;
            --bg-secondary: #e9ecef;
            --bg-card: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #e9ecef;
            --shadow-light: rgba(0,0,0,0.1);
        }

        [data-bs-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --bg-card: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #adb5bd;
            --border-color: #495057;
            --shadow-light: rgba(0,0,0,0.3);
        }

        .project-details-container {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            min-height: 100vh;
            padding: 1rem 0;
            color: var(--text-primary);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%) !important;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .card {
            border-radius: 8px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
        }

        .card-header {
            border-radius: 8px 8px 0 0 !important;
            border-bottom: 1px solid var(--border-color);
        }

        .card-body {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        .project-description {
            line-height: 1.5;
            font-size: 0.875rem;
            color: var(--text-primary);
        }

        .stat-card-compact {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            background: var(--bg-secondary);
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        .stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-top: 0.125rem;
        }

        .alert-card-compact {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.5rem;
            display: flex;
            align-items: center;
        }

        .alert-card-compact.alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.2);
        }

        [data-bs-theme="dark"] .alert-card-compact.alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.3);
        }

        .search-box-compact {
            position: relative;
        }

        .search-box-compact i {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            z-index: 10;
        }

        .search-box-compact .form-control {
            padding-left: 28px;
        }

        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge {
            border-radius: 4px;
            font-weight: 600;
        }

        /* Dark theme adjustments */
        [data-bs-theme="dark"] .text-primary {
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .text-muted {
            color: #adb5bd !important;
        }

        [data-bs-theme="dark"] .text-dark {
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .form-control {
            background-color: var(--bg-card);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        [data-bs-theme="dark"] .form-control:focus {
            background-color: var(--bg-card);
            border-color: #0d6efd;
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        [data-bs-theme="dark"] .form-select {
            background-color: var(--bg-card);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        [data-bs-theme="dark"] .form-select:focus {
            background-color: var(--bg-card);
            border-color: #0d6efd;
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        [data-bs-theme="dark"] .table {
            color: var(--text-primary);
        }

        [data-bs-theme="dark"] .table-hover tbody tr:hover {
            background-color: var(--bg-secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stat-card-compact {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Smooth transitions */
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
    </style>
</div>
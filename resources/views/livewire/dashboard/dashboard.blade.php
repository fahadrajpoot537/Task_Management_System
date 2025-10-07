<div>
<div>
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">Welcome back, {{ $user->name }}!</h2>
            <p class="text-muted">Here's what's happening with your tasks today.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        @if($user->isSuperAdmin())
            <div class="col-6 col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="bi bi-folder text-primary fs-1 mb-2"></i>
                        <h5 class="card-title mb-1">{{ $stats['total_projects'] }}</h5>
                        <p class="card-text text-muted small mb-0">Total Projects</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="bi bi-people text-success fs-1 mb-2"></i>
                        <h5 class="card-title mb-1">{{ $stats['total_users'] }}</h5>
                        <p class="card-text text-muted small mb-0">Total Users</p>
                    </div>
                </div>
            </div>
        @elseif($user->isManager())
            <div class="col-6 col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="bi bi-folder text-primary fs-1 mb-2"></i>
                        <h5 class="card-title mb-1">{{ $stats['total_projects'] }}</h5>
                        <p class="card-text text-muted small mb-0">Team Projects</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="bi bi-people text-info fs-1 mb-2"></i>
                        <h5 class="card-title mb-1">{{ $stats['team_members'] }}</h5>
                        <p class="card-text text-muted small mb-0">Team Members</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-6 col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <i class="bi bi-list-task text-warning fs-1 mb-2"></i>
                    <h5 class="card-title mb-1">{{ $stats['total_tasks'] }}</h5>
                    <p class="card-text text-muted small mb-0">Total Tasks</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <i class="bi bi-check-circle text-success fs-1 mb-2"></i>
                    <h5 class="card-title mb-1">{{ $stats['completed_tasks'] }}</h5>
                    <p class="card-text text-muted small mb-0">Completed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Status Overview -->
    <div class="row mb-4">
        <div class="col-12 col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-1">Pending Tasks</h6>
                            <h4 class="text-secondary mb-0">{{ $stats['pending_tasks'] }}</h4>
                        </div>
                        <i class="bi bi-clock text-secondary fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-1">In Progress</h6>
                            <h4 class="text-primary mb-0">{{ $stats['in_progress_tasks'] }}</h4>
                        </div>
                        <i class="bi bi-arrow-repeat text-primary fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-1">Overdue Tasks</h6>
                            <h4 class="text-danger mb-0">{{ $stats['overdue_tasks'] }}</h4>
                        </div>
                        <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Performance Statistics -->
    @if(isset($stats['delayed_tasks']) || isset($stats['early_tasks']) || isset($stats['on_time_tasks']))
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Task Performance</h5>
        </div>
        @if(isset($stats['delayed_tasks']))
        <div class="col-12 col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-1">Delayed Tasks</h6>
                            <h4 class="text-danger mb-0">{{ $stats['delayed_tasks'] }}</h4>
                        </div>
                        <i class="bi bi-clock-history text-danger fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if(isset($stats['early_tasks']))
        <div class="col-12 col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-1">Early Completion</h6>
                            <h4 class="text-success mb-0">{{ $stats['early_tasks'] }}</h4>
                        </div>
                        <i class="bi bi-lightning text-success fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if(isset($stats['on_time_tasks']))
        <div class="col-12 col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-1">On Time</h6>
                            <h4 class="text-primary mb-0">{{ $stats['on_time_tasks'] }}</h4>
                        </div>
                        <i class="bi bi-check-circle-fill text-primary fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Recent Projects -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                    <h5 class="mb-2 mb-sm-0">Recent Projects</h5>
                    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($this->recentProjects->count() > 0)
                        @foreach($this->recentProjects as $project)
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
                                <div class="mb-2 mb-sm-0">
                                    <h6 class="mb-1">{{ $project->title }}</h6>
                                    <small class="text-muted">{{ $project->createdBy->name }}</small>
                                </div>
                                <div class="text-start text-sm-end">
                                    <div class="progress mb-1" style="width: 60px; height: 8px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: {{ $project->progress_percentage ?? 0 }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $project->progress_percentage ?? 0 }}%</small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">No recent projects found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tasks -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                    <h5 class="mb-2 mb-sm-0">Recent Tasks</h5>
                    <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($this->recentTasks->count() > 0)
                        @foreach($this->recentTasks as $task)
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
                                <div class="mb-2 mb-sm-0">
                                    <h6 class="mb-1">{{ $task->title }}</h6>
                                    <small class="text-muted">{{ $task->project ? $task->project->title : 'No Project' }}</small>
                                </div>
                                <div class="text-start text-sm-end">
                                    @if($task->status)
                                        <span class="badge bg-{{ $task->status->color }} mb-1">{{ $task->status->name }}</span>
                                    @else
                                        <span class="badge bg-secondary mb-1">No Status</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">{{ $task->assignedTo ? $task->assignedTo->name : 'Unassigned' }}</small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">No recent tasks found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Dashboard Responsive Styles */
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem 0.75rem;
        }
        
        .card-header {
            padding: 0.75rem;
        }
        
        .fs-1 {
            font-size: 2rem !important;
        }
        
        h2 {
            font-size: 1.5rem;
        }
        
        h4 {
            font-size: 1.25rem;
        }
        
        h5 {
            font-size: 1rem;
        }
        
        h6 {
            font-size: 0.875rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .progress {
            width: 50px !important;
            height: 6px !important;
        }
        
        .badge {
            font-size: 0.7rem;
        }
    }
    
    @media (max-width: 576px) {
        .card-body {
            padding: 0.75rem 0.5rem;
        }
        
        .fs-1 {
            font-size: 1.75rem !important;
        }
        
        h2 {
            font-size: 1.25rem;
        }
        
        h4 {
            font-size: 1.1rem;
        }
        
        .card-title {
            font-size: 0.9rem;
        }
        
        .card-text {
            font-size: 0.8rem;
        }
        
        .progress {
            width: 40px !important;
            height: 5px !important;
        }
        
        .badge {
            font-size: 0.65rem;
        }
        
        small {
            font-size: 0.75rem;
        }
    }
    
    /* Ensure cards have equal height */
    .card.h-100 {
        height: 100% !important;
    }
    
    /* Better spacing for mobile */
    @media (max-width: 768px) {
        .mb-4 {
            margin-bottom: 1.5rem !important;
        }
        
        .mb-3 {
            margin-bottom: 1rem !important;
        }
    }
    
    /* Icon adjustments for mobile */
    @media (max-width: 576px) {
        .bi {
            font-size: 1.5rem;
        }
        
        .fs-1 {
            font-size: 1.5rem !important;
        }
    }
</style>

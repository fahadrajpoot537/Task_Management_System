<?php

namespace App\Livewire\Dashboard;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public $user;
    public $stats = [];

    public function mount()
    {
        $this->user = auth()->user();
        $this->loadStats();
    }

    public function loadStats()
    {
        $user = $this->user;

        if ($user->isSuperAdmin()) {
            $this->stats = [
                'total_projects' => Project::count(),
                'total_tasks' => Task::count(),
                'completed_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Complete');
                })->count(),
                'pending_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Pending');
                })->count(),
                'in_progress_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'In Progress');
                })->count(),
                'overdue_tasks' => Task::where('due_date', '<', now())
                    ->whereDoesntHave('status', function ($query) {
                        $query->where('name', 'Complete');
                    })->count(),
                'total_users' => User::count(),
                'categories' => [
                    'development' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Development');
                    })->count(),
                    'design' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Design');
                    })->count(),
                    'testing' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Testing');
                    })->count(),
                    'documentation' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Documentation');
                    })->count(),
                    'meeting' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Meeting');
                    })->count(),
                    'general' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'General');
                    })->count(),
                ],
                'total_estimated_hours' => Task::sum('estimated_hours') ?? 0,
                'total_actual_hours' => Task::sum('actual_hours') ?? 0,
                'delayed_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Complete');
                })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) > DATE(due_date)')
                    ->count(),
                'early_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Complete');
                })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) < DATE(due_date)')
                    ->count(),
                'on_time_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Complete');
                })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) = DATE(due_date)')
                    ->count(),
            ];
        } elseif ($user->isAdmin()) {
            // Admin can see all projects and tasks but cannot manage permissions
            $this->stats = [
                'total_projects' => Project::count(),
                'total_tasks' => Task::count(),
                'completed_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Complete');
                })->count(),
                'pending_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Pending');
                })->count(),
                'in_progress_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'In Progress');
                })->count(),
                'overdue_tasks' => Task::where('due_date', '<', now())
                    ->whereDoesntHave('status', function ($query) {
                        $query->where('name', 'Complete');
                    })->count(),
                'total_users' => User::count(),
                'categories' => [
                    'development' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Development');
                    })->count(),
                    'design' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Design');
                    })->count(),
                    'testing' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Testing');
                    })->count(),
                    'documentation' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Documentation');
                    })->count(),
                    'meeting' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'Meeting');
                    })->count(),
                    'general' => Task::whereHas('category', function ($query) {
                        $query->where('name', 'General');
                    })->count(),
                ],
                'total_estimated_hours' => Task::sum('estimated_hours') ?? 0,
                'total_actual_hours' => Task::sum('actual_hours') ?? 0,
                'delayed_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Complete');
                })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) > DATE(due_date)')
                    ->count(),
                'early_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Complete');
                })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) < DATE(due_date)')
                    ->count(),
                'on_time_tasks' => Task::whereHas('status', function ($query) {
                    $query->where('name', 'Complete');
                })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) = DATE(due_date)')
                    ->count(),
            ];
        } elseif ($user->isManager()) {
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            
            $this->stats = [
                'total_projects' => Project::whereIn('created_by_user_id', $teamMemberIds)->count(),
                'total_tasks' => Task::whereIn('assigned_to_user_id', $teamMemberIds)->count(),
                'completed_tasks' => Task::whereIn('assigned_to_user_id', $teamMemberIds)
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Complete');
                    })->count(),
                'pending_tasks' => Task::whereIn('assigned_to_user_id', $teamMemberIds)
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Pending');
                    })->count(),
                'in_progress_tasks' => Task::whereIn('assigned_to_user_id', $teamMemberIds)
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'In Progress');
                    })->count(),
                'overdue_tasks' => Task::whereIn('assigned_to_user_id', $teamMemberIds)
                    ->where('due_date', '<', now())
                    ->whereDoesntHave('status', function ($query) {
                        $query->where('name', 'Complete');
                    })->count(),
                'team_members' => $user->teamMembers->count(),
                'delayed_tasks' => Task::whereIn('assigned_to_user_id', $teamMemberIds)
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Complete');
                    })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) > DATE(due_date)')
                    ->count(),
                'early_tasks' => Task::whereIn('assigned_to_user_id', $teamMemberIds)
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Complete');
                    })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) < DATE(due_date)')
                    ->count(),
                'on_time_tasks' => Task::whereIn('assigned_to_user_id', $teamMemberIds)
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Complete');
                    })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) = DATE(due_date)')
                    ->count(),
            ];
        } else {
            $this->stats = [
                'total_tasks' => $user->assignedTasks()->count(),
                'completed_tasks' => $user->assignedTasks()
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Complete');
                    })->count(),
                'pending_tasks' => $user->assignedTasks()
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Pending');
                    })->count(),
                'in_progress_tasks' => $user->assignedTasks()
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'In Progress');
                    })->count(),
                'overdue_tasks' => $user->assignedTasks()
                    ->where('due_date', '<', now())
                    ->whereDoesntHave('status', function ($query) {
                        $query->where('name', 'Complete');
                    })->count(),
                'delayed_tasks' => $user->assignedTasks()
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Complete');
                    })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) > DATE(due_date)')
                    ->count(),
                'early_tasks' => $user->assignedTasks()
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Complete');
                    })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) < DATE(due_date)')
                    ->count(),
                'on_time_tasks' => $user->assignedTasks()
                    ->whereHas('status', function ($query) {
                        $query->where('name', 'Complete');
                    })
                    ->whereNotNull('due_date')
                    ->whereNotNull('completed_at')
                    ->whereRaw('DATE(completed_at) = DATE(due_date)')
                    ->count(),
            ];
        }
    }

    public function getRecentTasksProperty()
    {
        $user = $this->user;
        
        if ($user->isSuperAdmin()) {
            return Task::with(['project', 'assignedTo', 'assignedBy', 'status'])
                ->latest()
                ->limit(5)
                ->get();
        } elseif ($user->isAdmin()) {
            return Task::with(['project', 'assignedTo', 'assignedBy', 'status'])
                ->latest()
                ->limit(5)
                ->get();
        } elseif ($user->isManager()) {
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            
            return Task::with(['project', 'assignedTo', 'assignedBy', 'status'])
                ->whereIn('assigned_to_user_id', $teamMemberIds)
                ->latest()
                ->limit(5)
                ->get();
        } else {
            return $user->assignedTasks()
                ->with(['project', 'assignedTo', 'assignedBy', 'status'])
                ->latest()
                ->limit(5)
                ->get();
        }
    }

    public function getRecentProjectsProperty()
    {
        $user = $this->user;
        
        if ($user->isSuperAdmin()) {
            return Project::with(['createdBy', 'tasks' => function($query) {
                $query->with('status');
            }])->latest()->limit(5)->get()->map(function($project) {
                $totalTasks = $project->tasks->count();
                $completedTasks = $project->tasks->where('status.name', 'Complete')->count();
                $project->progress_percentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                return $project;
            });
        } elseif ($user->isAdmin()) {
            return Project::with(['createdBy', 'tasks' => function($query) {
                $query->with('status');
            }])->latest()->limit(5)->get()->map(function($project) {
                $totalTasks = $project->tasks->count();
                $completedTasks = $project->tasks->where('status.name', 'Complete')->count();
                $project->progress_percentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                return $project;
            });
        } elseif ($user->isManager()) {
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            
            return Project::with(['createdBy', 'tasks' => function($query) {
                $query->with('status');
            }])
                ->whereIn('created_by_user_id', $teamMemberIds)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($project) {
                    $totalTasks = $project->tasks->count();
                    $completedTasks = $project->tasks->where('status.name', 'Complete')->count();
                    $project->progress_percentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                    return $project;
                });
        } else {
            return Project::with(['createdBy', 'tasks' => function($query) {
                $query->with('status');
            }])
                ->where('created_by_user_id', $user->id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($project) {
                    $totalTasks = $project->tasks->count();
                    $completedTasks = $project->tasks->where('status.name', 'Complete')->count();
                    $project->progress_percentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                    return $project;
                });
        }
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard')
            ->layout('layouts.app');
    }
}

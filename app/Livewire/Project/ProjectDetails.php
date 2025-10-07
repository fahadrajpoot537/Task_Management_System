<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\Task;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectDetails extends Component
{
    use WithPagination;

    public Project $project;
    public $search = '';
    public $statusFilter = '';
    public $priorityFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
    ];

    public function mount($projectId)
    {
        $this->project = Project::with(['createdBy', 'tasks.assignedTo', 'tasks.assignedBy'])
            ->findOrFail($projectId);
        
        // Check if user has permission to view this project
        $user = auth()->user();
        
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            // Super admin and admin can view all projects
            return;
        } elseif ($user->isManager()) {
            // Managers can view:
            // 1. Projects they created
            // 2. Projects where their team members have tasks
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            
            $hasAccess = $this->project->created_by_user_id === $user->id || 
                        $this->project->tasks()->whereIn('assigned_to_user_id', $teamMemberIds)->exists();
            
            if (!$hasAccess) {
                abort(403, 'You do not have permission to view this project.');
            }
        } else {
            // Employees can view:
            // 1. Projects they created
            // 2. Projects where they have tasks assigned
            $hasAccess = $this->project->created_by_user_id === $user->id || 
                        $this->project->tasks()->where('assigned_to_user_id', $user->id)->exists();
            
            if (!$hasAccess) {
                abort(403, 'You do not have permission to view this project.');
            }
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter()
    {
        $this->resetPage();
    }

    public function getTasksProperty()
    {
        $query = $this->project->tasks()
            ->with(['assignedTo', 'assignedBy', 'status', 'priority', 'category'])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter, function ($query) {
                $query->whereHas('status', function ($statusQuery) {
                    $statusQuery->where('name', $this->statusFilter);
                });
            })
            ->when($this->priorityFilter, function ($query) {
                $query->whereHas('priority', function ($priorityQuery) {
                    $priorityQuery->where('name', $this->priorityFilter);
                });
            });

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getProjectStatsProperty()
    {
        $totalTasks = $this->project->tasks()->count();
        $completedTasks = $this->project->tasks()->whereHas('status', function ($query) {
            $query->where('name', 'Complete');
        })->count();
        $inProgressTasks = $this->project->tasks()->whereHas('status', function ($query) {
            $query->where('name', 'In Progress');
        })->count();
        $pendingTasks = $this->project->tasks()->whereHas('status', function ($query) {
            $query->where('name', 'Pending');
        })->count();
        $overdueTasks = $this->project->tasks()
            ->where('due_date', '<', now())
            ->whereDoesntHave('status', function ($query) {
                $query->where('name', 'Complete');
            })
            ->count();

        return [
            'total' => $totalTasks,
            'completed' => $completedTasks,
            'in_progress' => $inProgressTasks,
            'pending' => $pendingTasks,
            'overdue' => $overdueTasks,
            'progress_percentage' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0,
        ];
    }

    public function render()
    {
        return view('livewire.project.project-details')
            ->layout('layouts.app');
    }
}

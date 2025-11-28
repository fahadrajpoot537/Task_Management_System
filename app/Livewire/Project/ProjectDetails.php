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
        $user = auth()->user();
        
        // Check if user has permission to view projects
        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('view_all_projects');
        $canViewOwn = $user->isSuperAdmin() || $user->hasPermission('view_own_projects');
        
        if (!$canViewAll && !$canViewOwn) {
            abort(403, 'You do not have permission to view projects.');
        }

        $this->project = Project::with(['createdBy', 'tasks.assignedTo', 'tasks.assignedBy'])
            ->findOrFail($projectId);
        
        // If user can only view own projects, check ownership
        if (!$canViewAll && $canViewOwn) {
            if ($this->project->created_by_user_id !== $user->id) {
                abort(403, 'You do not have permission to view this project. You can only view projects you created.');
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

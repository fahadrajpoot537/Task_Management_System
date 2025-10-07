<?php

namespace App\Livewire\Project;

use App\Models\Log;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    public function deleteProject($projectId)
    {
        $project = Project::findOrFail($projectId);
        $user = auth()->user();
        
        // Check if user can delete this project
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            // Super admin and admin can delete any project
        } elseif ($user->isManager()) {
            // Managers can only delete projects they created
            if ($project->created_by_user_id !== $user->id) {
                session()->flash('error', 'You can only delete projects you created.');
                return;
            }
        } else {
            // Employees can only delete projects they created
            if ($project->created_by_user_id !== $user->id) {
                session()->flash('error', 'You can only delete projects you created.');
                return;
            }
        }

        // Log the deletion
        Log::createLog(auth()->id(), 'delete_project', "Deleted project: {$project->title}");

        $project->delete();
        
        session()->flash('success', 'Project deleted successfully.');
    }

    public function getProjectsProperty()
    {
        $user = auth()->user();
        
        $query = Project::with(['createdBy', 'tasks'])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            });

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            // Super admin and admin can see all projects
            // No additional filtering needed
        } elseif ($user->isManager()) {
            // Managers can see:
            // 1. Projects they created
            // 2. Projects where their team members have tasks
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            
            $query->where(function ($q) use ($teamMemberIds, $user) {
                $q->where('created_by_user_id', $user->id) // Projects they created
                  ->orWhereHas('tasks', function ($taskQuery) use ($teamMemberIds) {
                      $taskQuery->whereIn('assigned_to_user_id', $teamMemberIds); // Projects with tasks assigned to their team
                  });
            });
        } else {
            // Employees can see:
            // 1. Projects they created
            // 2. Projects where they have tasks assigned
            $query->where(function ($q) use ($user) {
                $q->where('created_by_user_id', $user->id) // Projects they created
                  ->orWhereHas('tasks', function ($taskQuery) use ($user) {
                      $taskQuery->where('assigned_to_user_id', $user->id); // Projects with tasks assigned to them
                  });
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
                    ->paginate(10);
    }

    public function render()
    {
        return view('livewire.project.project-index')
            ->layout('layouts.app');
    }
}

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
        $user = auth()->user();
        
        // Check permission
        if (!$user->isSuperAdmin() && !$user->hasPermission('delete_project')) {
            session()->flash('error', 'You do not have permission to delete projects.');
            return;
        }

        $project = Project::findOrFail($projectId);
        
        // If user can only view own projects, check ownership
        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('view_all_projects');
        if (!$canViewAll) {
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

    public function mount()
    {
        $user = auth()->user();
        
        // Check if user has permission to view projects
        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('view_all_projects');
        $canViewOwn = $user->isSuperAdmin() || $user->hasPermission('view_own_projects');
        
        if (!$canViewAll && !$canViewOwn) {
            abort(403, 'You do not have permission to view projects.');
        }
    }

    public function getProjectsProperty()
    {
        $user = auth()->user();
        
        // Check permissions
        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('view_all_projects');
        $canViewOwn = $user->isSuperAdmin() || $user->hasPermission('view_own_projects');
        
        $query = Project::with(['createdBy', 'tasks'])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            });

        if ($canViewAll) {
            // User can view all projects - no filtering needed
        } elseif ($canViewOwn) {
            // User can only view projects they created
            $query->where('created_by_user_id', $user->id);
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

<?php

namespace App\Livewire\Project;

use App\Models\Log;
use App\Models\Project;
use Livewire\Component;

class ProjectCreate extends Component
{
    public $title = '';
    public $description = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'required|string|min:10',
    ];

    public function mount()
    {
        $user = auth()->user();
        
        // Check permission
        if (!$user->isSuperAdmin() && !$user->hasPermission('create_project')) {
            abort(403, 'You do not have permission to create projects.');
        }
    }

    public function createProject()
    {
        $user = auth()->user();
        
        // Check permission
        if (!$user->isSuperAdmin() && !$user->hasPermission('create_project')) {
            session()->flash('error', 'You do not have permission to create projects.');
            return;
        }

        $this->validate();

        $project = Project::create([
            'title' => $this->title,
            'description' => $this->description,
            'created_by_user_id' => auth()->id(),
        ]);

        // Log the creation
        Log::createLog(auth()->id(), 'create_project', "Created project: {$project->title}");

        session()->flash('success', 'Project created successfully.');

        return redirect()->route('projects.index');
    }

    public function render()
    {
        return view('livewire.project.project-create')
            ->layout('layouts.app');
    }
}

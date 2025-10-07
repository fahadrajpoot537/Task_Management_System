<?php

namespace App\Livewire\Task;

use App\Mail\TaskAssigned;
use App\Models\Log;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\TaskCategory;
use App\Services\EmailNotificationService;
use Livewire\Component;
use Livewire\WithFileUploads;

class TaskCreate extends Component
{
    use WithFileUploads;

    protected $emailService;

    public function mount()
    {
        $this->emailService = new EmailNotificationService();
        $this->emailService->configureMailSettings();
        
        $user = auth()->user();
        
        // Set default assigned user based on role
        if ($user->isEmployee()) {
            $this->assigned_to_user_id = $user->id;
        }
    }

    public function boot()
    {
        // Ensure email service is initialized even if mount() wasn't called
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
            $this->emailService->configureMailSettings();
        }
    }

    public $project_id = '';
    public $title = '';
    public $description = '';
    public $priority_id = '';
    public $category_id = '';
    public $status_id = '';
    public $duration = '';
    public $due_date = '';
    public $assigned_to_user_id = '';
    public $notes = '';
    public $attachments = [];

    // Add form properties
    public $showAddPriorityForm = false;
    public $showAddCategoryForm = false;
    public $showAddStatusForm = false;
    public $newPriorityName = '';
    public $newPriorityColor = 'secondary';
    public $newCategoryName = '';
    public $newCategoryIcon = 'bi-list-task';
    public $newCategoryColor = 'secondary';
    public $newStatusName = '';
    public $newStatusColor = 'secondary';
    public $newStatusIsDefault = false;

    protected $rules = [
        'project_id' => 'required|exists:projects,id',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'priority_id' => 'required|exists:task_priorities,id',
        'category_id' => 'required|exists:task_categories,id',
        'status_id' => 'nullable|exists:task_statuses,id',
        'duration' => 'nullable|integer|min:1',
        'due_date' => 'nullable|date|after:today',
        'assigned_to_user_id' => 'required|exists:users,id',
        'notes' => 'nullable|string',
        'attachments.*' => 'nullable|file|max:10240', // 10MB max
    ];

    public function createTask()
    {
        $this->validate();

        $task = Task::create([
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority_id' => $this->priority_id,
            'category_id' => $this->category_id,
            'status_id' => $this->status_id ?: $this->getDefaultStatusId(),
            'duration' => $this->duration,
            'due_date' => $this->due_date,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assigned_by_user_id' => auth()->id(),
            'notes' => $this->notes,
        ]);

        // Handle file uploads
        if ($this->attachments) {
            foreach ($this->attachments as $attachment) {
                $path = $attachment->store('attachments');
                
                $task->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $attachment->getClientOriginalName(),
                    'uploaded_by_user_id' => auth()->id(),
                ]);
            }
        }

        // Log the creation
        Log::createLog(auth()->id(), 'create_task', "Created task: {$task->title}");

        // Send email notifications
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
            $this->emailService->configureMailSettings();
        }
        
        $this->emailService->sendTaskCreatedNotification($task);
        if ($task->assignedTo) {
            $this->emailService->sendTaskAssignedNotification($task);
        }

        session()->flash('success', 'Task created successfully.');

        return redirect()->route('tasks.index');
    }

    private function getDefaultStatusId()
    {
        $defaultStatus = TaskStatus::where('is_default', true)->first();
        return $defaultStatus ? $defaultStatus->id : null;
    }

    // Priority management methods
    public function showAddPriorityForm()
    {
        $this->showAddPriorityForm = true;
        $this->newPriorityName = '';
        $this->newPriorityColor = 'secondary';
    }

    public function hideAddPriorityForm()
    {
        $this->showAddPriorityForm = false;
        $this->newPriorityName = '';
        $this->newPriorityColor = 'secondary';
    }

    public function addNewPriority()
    {
        $this->validate([
            'newPriorityName' => 'required|string|max:255|unique:task_priorities,name',
            'newPriorityColor' => 'required|string|in:primary,secondary,success,danger,warning,info,dark',
        ]);

        $priority = TaskPriority::create([
            'name' => $this->newPriorityName,
            'color' => $this->newPriorityColor,
        ]);

        $this->priority_id = $priority->id;
        $this->hideAddPriorityForm();
        session()->flash('success', 'Priority added successfully!');
    }

    // Category management methods
    public function showAddCategoryForm()
    {
        $this->showAddCategoryForm = true;
        $this->newCategoryName = '';
        $this->newCategoryIcon = 'bi-list-task';
        $this->newCategoryColor = 'secondary';
    }

    public function hideAddCategoryForm()
    {
        $this->showAddCategoryForm = false;
        $this->newCategoryName = '';
        $this->newCategoryIcon = 'bi-list-task';
        $this->newCategoryColor = 'secondary';
    }

    public function addNewCategory()
    {
        $this->validate([
            'newCategoryName' => 'required|string|max:255|unique:task_categories,name',
            'newCategoryIcon' => 'required|string|max:255',
            'newCategoryColor' => 'required|string|in:primary,secondary,success,danger,warning,info,dark',
        ]);

        $category = TaskCategory::create([
            'name' => $this->newCategoryName,
            'icon' => $this->newCategoryIcon,
            'color' => $this->newCategoryColor,
        ]);

        $this->category_id = $category->id;
        $this->hideAddCategoryForm();
        session()->flash('success', 'Category added successfully!');
    }

    // Status management methods
    public function showAddStatusForm()
    {
        $this->showAddStatusForm = true;
        $this->newStatusName = '';
        $this->newStatusColor = 'secondary';
        $this->newStatusIsDefault = false;
    }

    public function hideAddStatusForm()
    {
        $this->showAddStatusForm = false;
        $this->newStatusName = '';
        $this->newStatusColor = 'secondary';
        $this->newStatusIsDefault = false;
    }

    public function addNewStatus()
    {
        $this->validate([
            'newStatusName' => 'required|string|max:255|unique:task_statuses,name',
            'newStatusColor' => 'required|string|in:primary,secondary,success,danger,warning,info,dark',
            'newStatusIsDefault' => 'boolean',
        ]);

        if ($this->newStatusIsDefault) {
            TaskStatus::where('is_default', true)->update(['is_default' => false]);
        }

        $status = TaskStatus::create([
            'name' => $this->newStatusName,
            'color' => $this->newStatusColor,
            'is_default' => $this->newStatusIsDefault,
        ]);

        $this->status_id = $status->id;
        $this->hideAddStatusForm();
        session()->flash('success', 'Status added successfully!');
    }

    public function getProjectsProperty()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            return Project::all();
        } elseif ($user->isManager()) {
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            return Project::whereIn('created_by_user_id', $teamMemberIds)->get();
        } else {
            return Project::where('created_by_user_id', $user->id)->get();
        }
    }

    public function getPrioritiesProperty()
    {
        return TaskPriority::orderBy('name')->get();
    }

    public function getCategoriesProperty()
    {
        return TaskCategory::orderBy('name')->get();
    }

    public function getStatusesProperty()
    {
        return TaskStatus::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.task.task-create')
            ->layout('layouts.app');
    }
}

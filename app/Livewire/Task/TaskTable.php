<?php

namespace App\Livewire\Task;

use App\Models\Log;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\TaskCategory;
use App\Models\TaskNoteComment;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\RecurringTaskService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class TaskTable extends Component
{
    use WithPagination, WithFileUploads;

    protected $emailService;

    public function mount()
    {
        $this->emailService = new EmailNotificationService();
        $this->emailService->configureMailSettings();
    }

    public function boot()
    {
        // Ensure email service is initialized even if mount() wasn't called
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
            $this->emailService->configureMailSettings();
        }
    }

    public $search = '';
    public $projectFilter = '';
    public $statusFilter = '';
    public $categoryFilter = '';
    public $assigneeFilter = '';
    
    // Inline editing properties
    public $editingTaskId = null;
    public $newTaskTitle = '';
    public $newTaskDescription = '';
    public $newTaskProjectId = '';
    public $newTaskAssigneeId = '';
    public $newTaskPriority = '';
    public $newTaskCategory = '';
    public $newTaskDueDate = '';
    public $newTaskEstimatedHours = '';
    public $newTaskNotes = '';
    public $newTaskNature = 'daily';
    
    // Modal properties
    public $showNotesModal = false;
    public $notesModalTitle = '';
    public $notesModalContent = '';
    public $notesModalTaskId = null;
    public $notesModalMode = 'view'; // 'view' or 'commit'
    public $commitMessage = '';
    
    // Comment properties
    public $newComment = '';
    public $commentAttachments = [];
    
    // Custom option creation properties
    public $showCustomStatusForm = false;
    public $showCustomPriorityForm = false;
    public $showCustomCategoryForm = false;
    public $customStatusName = '';
    public $customStatusColor = 'secondary';
    public $customPriorityName = '';
    public $customPriorityColor = 'secondary';
    public $customCategoryName = '';
    public $customCategoryIcon = 'bi-list-task';
    public $customCategoryColor = 'secondary';

    protected $queryString = [
        'search' => ['except' => ''],
        'projectFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'assigneeFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingProjectFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingAssigneeFilter()
    {
        $this->resetPage();
    }

    public function startEditing($taskId)
    {
        if ($taskId == 0) {
            // Starting to create a new task
            $this->editingTaskId = 0;
            $this->resetNewTaskFields();
        } else {
            // Editing existing task
            $task = Task::findOrFail($taskId);
            $this->editingTaskId = $taskId;
            $this->newTaskTitle = $task->title;
            $this->newTaskDescription = $task->description;
            $this->newTaskProjectId = $task->project_id;
            $this->newTaskAssigneeId = $task->assigned_to_user_id;
            $this->newTaskPriority = $task->priority_id;
            $this->newTaskCategory = $task->category_id;
            $this->newTaskDueDate = $task->due_date ? $task->due_date->format('Y-m-d') : '';
            $this->newTaskEstimatedHours = $task->estimated_hours;
            $this->newTaskNotes = $task->notes;
        }
    }

    public function cancelEditing()
    {
        $this->editingTaskId = null;
        $this->resetNewTaskFields();
    }

    public function saveTask()
    {
        if ($this->editingTaskId) {
            $this->updateTask();
        } else {
            $this->createTask();
        }
    }

    public function createTask()
    {
        $this->validate([
            'newTaskTitle' => 'required|string|max:255',
            'newTaskDescription' => 'nullable|string',
            'newTaskProjectId' => 'required|exists:projects,id',
            'newTaskAssigneeId' => 'nullable|exists:users,id',
            'newTaskPriority' => 'required|exists:task_priorities,id',
            'newTaskCategory' => 'required|exists:task_categories,id',
            'newTaskDueDate' => 'nullable|date',
            'newTaskEstimatedHours' => 'nullable|numeric|min:0',
            'newTaskNotes' => 'nullable|string',
            'newTaskNature' => 'required|in:daily,recurring',
        ]);

        // Get Pending status
        $pendingStatus = TaskStatus::where('name', 'Pending')->first();

        $task = Task::create([
            'title' => $this->newTaskTitle,
            'description' => $this->newTaskDescription,
            'project_id' => $this->newTaskProjectId,
            'assigned_to_user_id' => $this->newTaskAssigneeId,
            'priority_id' => $this->newTaskPriority,
            'category_id' => $this->newTaskCategory,
            'status_id' => $pendingStatus ? $pendingStatus->id : null,
            'due_date' => $this->newTaskDueDate,
            'estimated_hours' => $this->newTaskEstimatedHours,
            'notes' => $this->newTaskNotes,
            'nature_of_task' => $this->newTaskNature,
            'is_recurring' => $this->newTaskNature === 'recurring',
            'is_recurring_active' => $this->newTaskNature === 'recurring',
            'assigned_by_user_id' => auth()->id(),
        ]);

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

        session()->flash('success', 'Task created successfully!');
        $this->resetNewTaskFields();
    }

    public function updateTask()
    {
        $this->validate([
            'newTaskTitle' => 'required|string|max:255',
            'newTaskDescription' => 'nullable|string',
            'newTaskProjectId' => 'required|exists:projects,id',
            'newTaskAssigneeId' => 'nullable|exists:users,id',
            'newTaskPriority' => 'required|exists:task_priorities,id',
            'newTaskCategory' => 'required|exists:task_categories,id',
            'newTaskDueDate' => 'nullable|date',
            'newTaskEstimatedHours' => 'nullable|numeric|min:0',
            'newTaskNotes' => 'nullable|string',
            'newTaskNature' => 'required|in:daily,recurring',
        ]);

        $task = Task::findOrFail($this->editingTaskId);
        $task->update([
            'title' => $this->newTaskTitle,
            'description' => $this->newTaskDescription,
            'project_id' => $this->newTaskProjectId,
            'assigned_to_user_id' => $this->newTaskAssigneeId,
            'priority_id' => $this->newTaskPriority,
            'category_id' => $this->newTaskCategory,
            'due_date' => $this->newTaskDueDate,
            'estimated_hours' => $this->newTaskEstimatedHours,
            'notes' => $this->newTaskNotes,
            'nature_of_task' => $this->newTaskNature,
            'is_recurring' => $this->newTaskNature === 'recurring',
            'is_recurring_active' => $this->newTaskNature === 'recurring',
        ]);

        // Log the update
        Log::createLog(auth()->id(), 'update_task', "Updated task: {$task->title}");

        // Send email notification for task update
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
            $this->emailService->configureMailSettings();
        }
        
        $this->emailService->sendTaskUpdatedNotification($task, 'Task Details Updated');

        session()->flash('success', 'Task updated successfully!');
        $this->cancelEditing();
    }

    public function deleteTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        
        // Check permissions
        if (!auth()->user()->isSuperAdmin() && $task->created_by !== auth()->id()) {
            session()->flash('error', 'You can only delete your own tasks.');
            return;
        }

        // Log the deletion
        Log::createLog(auth()->id(), 'delete_task', "Deleted task: {$task->title}");

        $task->delete();
        session()->flash('success', 'Task deleted successfully.');
    }

    public function updateTaskStatus($taskId, $statusId)
    {
        $user = auth()->user();
        $task = Task::findOrFail($taskId);
        
        // Check if user can update this task
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            if ($user->isManager()) {
                // Managers can update tasks assigned to their team members and themselves
                $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                if (!in_array($task->assigned_to_user_id, $teamMemberIds->toArray()) && 
                    $task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            } else {
                // Employees can only update tasks assigned to them
                if ($task->assigned_to_user_id !== $user->id && 
                    $task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            }
        }

        // Check if employee is trying to set Complete status
        if ($user->isEmployee()) {
            $status = TaskStatus::findOrFail($statusId);
            if ($status->name === 'Complete') {
                session()->flash('error', 'Only managers, admins, and super admins can mark tasks as complete.');
                return;
            }
        }

        $oldStatus = $task->status;
        $task->update(['status_id' => $statusId]);
        $task->load('status');
        
        $newStatus = $task->status;

        // Log the status change
        Log::createLog(auth()->id(), 'update_task_status', "Changed task '{$task->title}' status from " . ($oldStatus ? $oldStatus->name : 'No Status') . " to {$newStatus->name}");

        // Send email notification for status change
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
            $this->emailService->configureMailSettings();
        }
        
        $this->emailService->sendTaskStatusChangedNotification($task, $oldStatus, $newStatus);

        // Process recurring task if status is "Complete"
        if ($newStatus->name === 'Complete') {
            $recurringService = new RecurringTaskService();
            $recurringService->processRecurringTask($task);
        }

        session()->flash('success', 'Task status updated successfully!');
    }

    public function stopRecurringTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        
        if ($task->nature_of_task === 'recurring') {
            $recurringService = new RecurringTaskService();
            $recurringService->stopRecurringTask($task);
            
            // Log the action
            Log::createLog(auth()->id(), 'stop_recurring_task', "Stopped recurring task: {$task->title}");
            
            session()->flash('success', 'Recurring task generation stopped successfully!');
        } else {
            session()->flash('error', 'This task is not a recurring task.');
        }
    }

    public function updateTaskPriority($taskId, $priorityId)
    {
        $task = Task::findOrFail($taskId);
        $task->update(['priority_id' => $priorityId]);

        // Log the priority change
        Log::createLog(auth()->id(), 'update_task_priority', "Changed task '{$task->title}' priority");

        // Send email notification for priority change
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
            $this->emailService->configureMailSettings();
        }
        
        $this->emailService->sendTaskUpdatedNotification($task, 'Task Priority Updated');

        session()->flash('success', 'Task priority updated successfully!');
    }

    public function updateTaskCategory($taskId, $categoryId)
    {
        $task = Task::findOrFail($taskId);
        $task->update(['category_id' => $categoryId]);

        // Log the category change
        Log::createLog(auth()->id(), 'update_task_category', "Changed task '{$task->title}' category");

        // Send email notification for category change
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
            $this->emailService->configureMailSettings();
        }
        
        $this->emailService->sendTaskUpdatedNotification($task, 'Task Category Updated');

        session()->flash('success', 'Task category updated successfully!');
    }

    public function openNotesModal($taskId, $mode = 'view')
    {
        if ($taskId == 0) {
            // For new task creation
            $this->notesModalTaskId = 0;
            $this->notesModalMode = $mode;
            $this->notesModalTitle = 'Add Notes';
            $this->notesModalContent = $this->newTaskNotes;
            $this->commitMessage = '';
            $this->newComment = '';
            $this->showNotesModal = true;
        } else {
            // For existing task
            $task = Task::findOrFail($taskId);
            $this->notesModalTaskId = $taskId;
            $this->notesModalMode = $mode;
            
            // Check permissions for commit mode
            if ($mode === 'commit') {
                $user = auth()->user();
                if (!$user->isSuperAdmin() && !$user->isAdmin() && $task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'Only the task creator can edit notes.');
                    return;
                }
            }
            
            $this->notesModalTitle = $mode === 'commit' ? 'Commit Notes' : 'View Notes';
            $this->notesModalContent = $task->notes ?? '';
            $this->commitMessage = '';
            $this->newComment = '';
            $this->showNotesModal = true;
        }
    }

    public function closeNotesModal()
    {
        $this->showNotesModal = false;
        $this->notesModalTaskId = null;
        $this->notesModalContent = '';
        $this->notesModalMode = 'view';
        $this->commitMessage = '';
        $this->newComment = '';
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|string|max:1000',
            'commentAttachments.*' => 'nullable|file|max:10240'
        ]);

        if ($this->notesModalTaskId && $this->notesModalTaskId != 0) {
            $comment = TaskNoteComment::create([
                'task_id' => $this->notesModalTaskId,
                'user_id' => auth()->id(),
                'comment' => $this->newComment,
            ]);

            // Handle comment attachments
            if ($this->commentAttachments) {
                foreach ($this->commentAttachments as $attachment) {
                    $path = $attachment->store('comment-attachments');
                    
                    $comment->attachments()->create([
                        'task_id' => $this->notesModalTaskId,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_size' => $attachment->getSize(),
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            // Log the comment addition
            $task = Task::findOrFail($this->notesModalTaskId);
            Log::createLog(auth()->id(), 'add_task_note_comment', "Added comment to task '{$task->title}'");

            // Send email notification for the comment
            if (!$this->emailService) {
                $this->emailService = new EmailNotificationService();
                $this->emailService->configureMailSettings();
            }
            
            $this->emailService->sendTaskNoteCommentNotification($task, $comment);

            session()->flash('success', 'Comment added successfully!');
            $this->newComment = '';
            $this->commentAttachments = [];
        }
    }

    public function commitNotes()
    {
        $this->validate([
            'notesModalContent' => 'nullable|string',
            'commitMessage' => 'nullable|string|max:255',
        ]);

        if ($this->notesModalTaskId == 0) {
            // For new task creation, just update the newTaskNotes property
            $this->newTaskNotes = $this->notesModalContent;
            $this->closeNotesModal();
        } elseif ($this->notesModalTaskId) {
            // For existing task
            $task = Task::findOrFail($this->notesModalTaskId);
            $task->update(['notes' => $this->notesModalContent]);
            
            // Log the notes commit with commit message
            Log::createLog(auth()->id(), 'commit_task_notes', "Committed notes for task '{$task->title}': {$this->commitMessage}");
            
            session()->flash('success', 'Notes committed successfully!');
            $this->closeNotesModal();
        }
    }

    public function resetNewTaskFields()
    {
        $this->newTaskTitle = '';
        $this->newTaskDescription = '';
        $this->newTaskProjectId = '';
        $this->newTaskAssigneeId = '';
        $this->newTaskPriority = '';
        $this->newTaskCategory = '';
        $this->newTaskDueDate = '';
        $this->newTaskEstimatedHours = '';
        $this->newTaskNotes = '';
    }

    // Custom option creation methods
    public function toggleCustomStatusForm()
    {
        if (!$this->canManageStatuses()) {
            session()->flash('error', 'You do not have permission to create custom statuses.');
            return;
        }
        
        $this->showCustomStatusForm = !$this->showCustomStatusForm;
        if (!$this->showCustomStatusForm) {
            $this->resetCustomStatusForm();
        }
    }

    public function toggleCustomPriorityForm()
    {
        if (!$this->canManagePriorities()) {
            session()->flash('error', 'You do not have permission to create custom priorities.');
            return;
        }
        
        $this->showCustomPriorityForm = !$this->showCustomPriorityForm;
        if (!$this->showCustomPriorityForm) {
            $this->resetCustomPriorityForm();
        }
    }

    public function toggleCustomCategoryForm()
    {
        if (!$this->canManageCategories()) {
            session()->flash('error', 'You do not have permission to create custom categories.');
            return;
        }
        
        $this->showCustomCategoryForm = !$this->showCustomCategoryForm;
        if (!$this->showCustomCategoryForm) {
            $this->resetCustomCategoryForm();
        }
    }

    public function createCustomStatus()
    {
        // Check permissions
        if (!$this->canManageStatuses()) {
            session()->flash('error', 'You do not have permission to create custom statuses.');
            return;
        }

        $this->validate([
            'customStatusName' => 'required|string|max:255|unique:task_statuses,name',
            'customStatusColor' => 'required|string|max:50',
        ]);

        $status = TaskStatus::create([
            'name' => $this->customStatusName,
            'color' => $this->customStatusColor,
            'is_default' => false
        ]);

        // Log the creation
        Log::createLog(auth()->id(), 'create_custom_status', "Created custom status: {$status->name}");

        session()->flash('success', 'Custom status created successfully!');
        $this->resetCustomStatusForm();
    }

    public function createCustomPriority()
    {
        // Check permissions
        if (!$this->canManagePriorities()) {
            session()->flash('error', 'You do not have permission to create custom priorities.');
            return;
        }

        $this->validate([
            'customPriorityName' => 'required|string|max:255|unique:task_priorities,name',
            'customPriorityColor' => 'required|string|max:50',
        ]);

        $priority = TaskPriority::create([
            'name' => $this->customPriorityName,
            'color' => $this->customPriorityColor,
            'is_default' => false
        ]);

        // Log the creation
        Log::createLog(auth()->id(), 'create_custom_priority', "Created custom priority: {$priority->name}");

        session()->flash('success', 'Custom priority created successfully!');
        $this->resetCustomPriorityForm();
    }

    public function createCustomCategory()
    {
        // Check permissions
        if (!$this->canManageCategories()) {
            session()->flash('error', 'You do not have permission to create custom categories.');
            return;
        }

        $this->validate([
            'customCategoryName' => 'required|string|max:255|unique:task_categories,name',
            'customCategoryIcon' => 'required|string|max:50',
            'customCategoryColor' => 'required|string|max:50',
        ]);

        $category = TaskCategory::create([
            'name' => $this->customCategoryName,
            'icon' => $this->customCategoryIcon,
            'color' => $this->customCategoryColor,
            'is_default' => false
        ]);

        // Log the creation
        Log::createLog(auth()->id(), 'create_custom_category', "Created custom category: {$category->name}");

        session()->flash('success', 'Custom category created successfully!');
        $this->resetCustomCategoryForm();
    }

    public function resetCustomStatusForm()
    {
        $this->customStatusName = '';
        $this->customStatusColor = 'secondary';
        $this->showCustomStatusForm = false;
    }

    public function resetCustomPriorityForm()
    {
        $this->customPriorityName = '';
        $this->customPriorityColor = 'secondary';
        $this->showCustomPriorityForm = false;
    }

    public function resetCustomCategoryForm()
    {
        $this->customCategoryName = '';
        $this->customCategoryIcon = 'bi-list-task';
        $this->customCategoryColor = 'secondary';
        $this->showCustomCategoryForm = false;
    }

    public function getTasksProperty()
    {
        $user = auth()->user();
        
        $query = Task::with(['project', 'assignedTo', 'assignedBy', 'status', 'priority', 'category', 'noteComments.user'])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->projectFilter, function ($query) {
                $query->where('project_id', $this->projectFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status_id', $this->statusFilter);
            })
            ->when($this->categoryFilter, function ($query) {
                $query->where('category_id', $this->categoryFilter);
            })
            ->when($this->assigneeFilter, function ($query) {
                $query->where('assigned_to_user_id', $this->assigneeFilter);
            });

        if ($user->isSuperAdmin()) {
            // Super admin can see all tasks
        } elseif ($user->isAdmin()) {
            // Admin can see all tasks
        } elseif ($user->isManager()) {
            // Managers can see tasks assigned to their team members and themselves
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            $query->where(function ($q) use ($teamMemberIds, $user) {
                $q->whereIn('assigned_to_user_id', $teamMemberIds)
                  ->orWhere('assigned_by_user_id', $user->id);
            });
        } else {
            // Employees can only see tasks assigned to them
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to_user_id', $user->id)
                  ->orWhere('assigned_by_user_id', $user->id);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getProjectsProperty()
    {
        return Project::orderBy('title')->get();
    }

    public function getUsersProperty()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            return User::orderBy('name')->get();
        } elseif ($user->isAdmin()) {
            return User::orderBy('name')->get();
        } elseif ($user->isManager()) {
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            return User::whereIn('id', $teamMemberIds)->orderBy('name')->get();
        } else {
            return collect([$user]);
        }
    }

    public function getStatusesProperty()
    {
        return TaskStatus::orderBy('name')->get();
    }

    public function getPrioritiesProperty()
    {
        return TaskPriority::orderBy('name')->get();
    }

    public function getCategoriesProperty()
    {
        return TaskCategory::orderBy('name')->get();
    }

    public function getTaskComments()
    {
        if ($this->notesModalTaskId && $this->notesModalTaskId != 0) {
            return TaskNoteComment::with(['user', 'attachments.uploadedBy'])
                ->where('task_id', $this->notesModalTaskId)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return collect();
    }

    public function getTaskCommentsCount()
    {
        if ($this->notesModalTaskId && $this->notesModalTaskId != 0) {
            return TaskNoteComment::where('task_id', $this->notesModalTaskId)->count();
        }
        return 0;
    }

    public function canEditNotes()
    {
        if ($this->notesModalTaskId && $this->notesModalTaskId != 0) {
            $task = Task::find($this->notesModalTaskId);
            if ($task) {
                $user = auth()->user();
                return $user->isSuperAdmin() || $user->isAdmin() || $task->assigned_by_user_id === $user->id;
            }
        }
        return false;
    }

    public function canManageCategories()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function canManagePriorities()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function canManageStatuses()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function render()
    {
        return view('livewire.task.task-table')
            ->layout('layouts.app');
    }
}

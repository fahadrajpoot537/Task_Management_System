<?php

namespace App\Livewire\Task;

use App\Models\Attachment;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Validation\ValidationException;
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
    }

    public function boot()
    {
        // Ensure email service is initialized even if mount() wasn't called
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
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
    public $newTaskAssigneeIds = [];
    public $newTaskPriority = '';
    
    // Employee selection modal properties
    public $showEmployeeModal = false;
    public $selectedEmployeeNames = [];
    public $employeeSearch = '';
    public $newTaskCategory = '';
    
    // Task creation modal properties
    public $showTaskModal = false;
    public $modalTaskTitle = '';
    public $modalTaskDescription = '';
    public $modalTaskProjectId = '';
    public $modalTaskAssigneeId = '';
    public $modalTaskAssigneeIds = []; // For multiple assignees
    public $modalTaskPriority = '';
    public $modalTaskCategory = '';
    public $modalTaskDueDate = '';
    public $modalTaskEstimatedHours = '';
    public $modalTaskNotes = '';
    public $modalTaskNature = 'one_time';
    public $modalTaskRecurrenceFrequency = 'daily';
    public $modalTaskReminderTime = '';
    public $modalTaskAttachments = [];
    
    // Task edit modal properties
    public $showEditModal = false;
    public $editModalTaskId = null;
    public $editModalTaskTitle = '';
    public $editModalTaskDescription = '';
    public $editModalTaskProjectId = '';
    public $editModalTaskAssigneeIds = [];
    public $editModalTaskPriority = '';
    public $editModalTaskCategory = '';
    public $editModalTaskDueDate = '';
    public $editModalTaskEstimatedHours = '';
    public $editModalTaskNature = 'one_time';
    public $editModalTaskRecurrenceFrequency = 'daily';
    public $editModalTaskReminderTime = '';
    public $editModalTaskAttachments = [];
    
    // Task clone modal properties
    public $showCloneModal = false;
    public $cloneModalTaskId = null;
    public $cloneModalDueDate = '';
    
    // Project creation modal properties
    public $showProjectCreateModal = false;
    public $newProjectTitle = '';
    public $newProjectDescription = '';
    
    // Category creation modal properties
    public $showCategoryCreateModal = false;
    public $newCategoryTitle = '';
    public $newCategoryIcon = 'bi-list-task';
    public $newCategoryColor = 'secondary';
    
    public $newTaskDueDate = '';
    public $newTaskEstimatedHours = '';
    public $newTaskNotes = '';
    public $newTaskNature = 'one_time';
    
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
    
    // Notes file upload properties
    public $notesAttachments = [];
    public $notesAttachmentsToDelete = [];
    
    // File preview properties
    public $showFilePreviewModal = false;
    public $previewFile = null;
    
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
    
    // Admin review properties
    public $showAdminReviewModal = false;
    public $adminReviewTaskId = null;
    public $adminReviewComments = '';
    public $adminReviewAction = ''; // 'approve' or 'revisit'

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
        // Editing existing task in modal
        $task = Task::with('assignees')->findOrFail($taskId);
        
        $this->editModalTaskId = $taskId;
        $this->editModalTaskTitle = $task->title;
        $this->editModalTaskDescription = $task->description;
        $this->editModalTaskProjectId = $task->project_id;
        $this->editModalTaskAssigneeIds = $task->assignees->pluck('id')->toArray();
        $this->editModalTaskPriority = $task->priority_id;
        $this->editModalTaskCategory = $task->category_id;
        $this->editModalTaskDueDate = $task->due_date ? $task->due_date->format('Y-m-d') : '';
        $this->editModalTaskEstimatedHours = $task->estimated_hours;
        
        // Set nature and frequency based on task
        if ($task->is_recurring) {
            $this->editModalTaskNature = 'recurring';
            $this->editModalTaskRecurrenceFrequency = $task->nature_of_task; // 'daily', 'weekly', 'monthly'
        } else {
            $this->editModalTaskNature = 'one_time';
            $this->editModalTaskRecurrenceFrequency = 'daily';
        }
        
        // Set reminder time if exists
        $this->editModalTaskReminderTime = $task->reminder_time ? $task->reminder_time->format('Y-m-d\TH:i') : '';
        
        $this->showEditModal = true;
        $this->updateSelectedEmployeeNames();
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editModalTaskId = null;
        $this->resetEditModalFields();
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
            'newTaskProjectId' => 'nullable|exists:projects,id',
            'newTaskAssigneeId' => 'nullable|exists:users,id',
            'newTaskAssigneeIds' => 'nullable|array',
            'newTaskAssigneeIds.*' => 'exists:users,id',
            'newTaskPriority' => 'required|exists:task_priorities,id',
            'newTaskCategory' => 'required|exists:task_categories,id',
            'newTaskDueDate' => 'nullable|date',
            'newTaskEstimatedHours' => 'nullable|numeric|min:0',
            'newTaskNotes' => 'nullable|string',
            'newTaskNature' => 'required|in:daily,weekly,monthly,until_stop',
        ], [
            'newTaskAssigneeIds.*.exists' => 'One or more selected users do not exist.',
            'newTaskAssigneeId.exists' => 'The selected assignee does not exist.',
        ]);

        // Get Pending status
        $pendingStatus = TaskStatus::where('name', 'Pending')->first();

        // Determine if task is recurring based on nature
        $isRecurring = in_array($this->newTaskNature, ['weekly', 'monthly', 'until_stop']);
        $isRecurringActive = $isRecurring ? 1 : 0;

        // Determine primary assignee (for backward compatibility)
        $primaryAssigneeId = null;
        if (!empty($this->newTaskAssigneeIds)) {
            // Use the first selected assignee as the primary assignee
            $primaryAssigneeId = $this->newTaskAssigneeIds[0];
        } elseif (!empty($this->newTaskAssigneeId)) {
            // Fallback to single assignee selection
            $primaryAssigneeId = $this->newTaskAssigneeId;
        }

        $task = Task::create([
            'title' => $this->newTaskTitle,
            'description' => $this->newTaskDescription,
            'project_id' => $this->newTaskProjectId ?: null,
            'assigned_to_user_id' => $primaryAssigneeId,
            'priority_id' => $this->newTaskPriority,
            'category_id' => $this->newTaskCategory,
            'status_id' => $pendingStatus ? $pendingStatus->id : null,
            'due_date' => $this->newTaskDueDate,
            'estimated_hours' => $this->newTaskEstimatedHours,
            'notes' => $this->newTaskNotes,
            'nature_of_task' => $this->newTaskNature,
            'is_recurring' => $isRecurring,
            'is_recurring_active' => $isRecurringActive,
            'assigned_by_user_id' => auth()->id(),
        ]);

        // Assign multiple users if provided
        if (!empty($this->newTaskAssigneeIds)) {
            $task->syncAssignees($this->newTaskAssigneeIds, auth()->id());
        }

        // Log the creation
        Log::createLog(auth()->id(), 'create_task', "Created task: {$task->title}");

        // Send email notifications
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
        }
        
        $this->emailService->sendTaskCreatedNotification($task);
        if ($task->assignedTo) {
            $this->emailService->sendTaskAssignedNotification($task);
        }

        session()->flash('success', 'Task created successfully!');
        $this->resetNewTaskFields();
        
        // Emit event for Select2 re-initialization
        $this->dispatch('task-created');
    }

    public function updateTask()
    {
        $this->validate([
            'newTaskTitle' => 'required|string|max:255',
            'newTaskDescription' => 'nullable|string',
            'newTaskProjectId' => 'nullable|exists:projects,id',
            'newTaskAssigneeId' => 'nullable|exists:users,id',
            'newTaskAssigneeIds' => 'nullable|array',
            'newTaskAssigneeIds.*' => 'exists:users,id',
            'newTaskPriority' => 'required|exists:task_priorities,id',
            'newTaskCategory' => 'required|exists:task_categories,id',
            'newTaskDueDate' => 'nullable|date',
            'newTaskEstimatedHours' => 'nullable|numeric|min:0',
            'newTaskNotes' => 'nullable|string',
            'newTaskNature' => 'required|in:daily,weekly,monthly,until_stop',
        ], [
            'newTaskAssigneeIds.*.exists' => 'One or more selected users do not exist.',
            'newTaskAssigneeId.exists' => 'The selected assignee does not exist.',
        ]);

        // Determine if task is recurring based on nature
        $isRecurring = in_array($this->newTaskNature, ['weekly', 'monthly', 'until_stop']);
        $isRecurringActive = $isRecurring ? 1 : 0;

        // Determine primary assignee (for backward compatibility)
        $primaryAssigneeId = null;
        if (!empty($this->newTaskAssigneeIds)) {
            // Use the first selected assignee as the primary assignee
            $primaryAssigneeId = $this->newTaskAssigneeIds[0];
        } elseif (!empty($this->newTaskAssigneeId)) {
            // Fallback to single assignee selection
            $primaryAssigneeId = $this->newTaskAssigneeId;
        }

        $task = Task::findOrFail($this->editingTaskId);
        $task->update([
            'title' => $this->newTaskTitle,
            'description' => $this->newTaskDescription,
            'project_id' => $this->newTaskProjectId ?: null,
            'assigned_to_user_id' => $primaryAssigneeId,
            'priority_id' => $this->newTaskPriority,
            'category_id' => $this->newTaskCategory,
            'due_date' => $this->newTaskDueDate,
            'estimated_hours' => $this->newTaskEstimatedHours,
            'notes' => $this->newTaskNotes,
            'nature_of_task' => $this->newTaskNature,
            'is_recurring' => $isRecurring,
            'is_recurring_active' => $isRecurringActive,
        ]);

        // Update multiple assignees if provided
        if (!empty($this->newTaskAssigneeIds)) {
            $task->syncAssignees($this->newTaskAssigneeIds, auth()->id());
        } else {
            // Clear all assignees if none selected
            $task->assignees()->detach();
        }

        // Log the update
        Log::createLog(auth()->id(), 'update_task', "Updated task: {$task->title}");

        // Send email notification for task update
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
        }
        
        $this->emailService->sendTaskUpdatedNotification($task, 'Task Details Updated');

        session()->flash('success', 'Task updated successfully!');
        $this->cancelEditing();
        
        // Emit event for Select2 re-initialization
        $this->dispatch('task-updated');
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
        
        // Check if task is already approved - prevent status changes
        if ($task->is_approved) {
            session()->flash('error', 'Cannot change status of an approved task.');
            return;
        }
        
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
                if (!$task->isAssignedTo($user) && 
                    $task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
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
        
        if (in_array($task->nature_of_task, ['daily', 'weekly', 'monthly', 'until_stop'])) {
            DB::transaction(function () use ($task) {
                // Stop the current task
                $task->update(['is_recurring_active' => 0]);
                
                // Stop all child tasks (clones) as well
                $childTasks = Task::where('parent_task_id', $task->id)
                    ->where('is_recurring_active', 1)
                    ->get();
                
                foreach ($childTasks as $childTask) {
                    $childTask->update(['is_recurring_active' => 0]);
                }
                
                // Log the action
                Log::createLog(auth()->id(), 'stop_recurring_task', "Stopped recurring task: {$task->title} and {$childTasks->count()} child tasks");
            });
            
            session()->flash('success', 'Recurring task generation stopped successfully! All related tasks have been deactivated.');
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
            $this->notesAttachments = [];
            $this->notesAttachmentsToDelete = [];
            $this->showNotesModal = true;
            
            // Dispatch event to initialize tooltips in the modal
            $this->dispatch('notes-modal-opened');
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
            $this->notesAttachments = [];
            $this->notesAttachmentsToDelete = [];
            $this->showNotesModal = true;
            
            // Dispatch event to initialize tooltips in the modal
            $this->dispatch('notes-modal-opened');
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
        $this->notesAttachments = [];
        $this->notesAttachmentsToDelete = [];
        $this->showFilePreviewModal = false;
        $this->previewFile = null;
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
            'notesAttachments.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,zip,rar,mp4,webm,ogg,avi,mov,wmv,flv,mkv'
        ]);

        if ($this->notesModalTaskId == 0) {
            // For new task creation, just update the newTaskNotes property
            $this->newTaskNotes = $this->notesModalContent;
            $this->closeNotesModal();
        } elseif ($this->notesModalTaskId) {
            // For existing task
            $task = Task::findOrFail($this->notesModalTaskId);
            $task->update(['notes' => $this->notesModalContent]);
            
            // Handle file uploads for notes
            if ($this->notesAttachments) {
                foreach ($this->notesAttachments as $attachment) {
                    $path = $attachment->store('task-notes-attachments');
                    
                    Attachment::create([
                        'task_id' => $this->notesModalTaskId,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_size' => $attachment->getSize(),
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }
            
            // Handle file deletions
            if ($this->notesAttachmentsToDelete) {
                foreach ($this->notesAttachmentsToDelete as $attachmentId) {
                    $attachment = Attachment::find($attachmentId);
                    if ($attachment) {
                        // Delete file from storage
                        $fullPath = storage_path('app/private/' . $attachment->file_path);
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                        $attachment->delete();
                    }
                }
            }
            
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
        $this->newTaskAssigneeIds = [];
        $this->selectedEmployeeNames = [];
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
        
        $query = Task::with(['project', 'assignedTo', 'assignedBy', 'assignees', 'status', 'priority', 'category', 'noteComments.user'])
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
                  ->orWhere('assigned_by_user_id', $user->id)
                  ->orWhereHas('assignees', function ($subQ) use ($user) {
                      $subQ->where('user_id', $user->id);
                  });
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
            // Managers can see ALL users (employees, other managers, admins)
            return User::orderBy('name')->get();
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

    // Employee modal methods
    public function showEmployeeSelectionModal()
    {
        $this->showEmployeeModal = true;
        $this->employeeSearch = '';
        $this->updateSelectedEmployeeNames();
    }
    
    public function showEmployeeSelectionModalForEdit()
    {
        $this->showEmployeeModal = true;
        $this->employeeSearch = '';
        $this->updateSelectedEmployeeNames();
    }
    
    public function closeEmployeeModal()
    {
        $this->showEmployeeModal = false;
        $this->employeeSearch = '';
    }
    
    public function selectEmployee($userId)
    {
        // Check if we're in task creation modal, edit modal, or inline editing
        if ($this->showTaskModal) {
            // For task creation modal
            if (!in_array($userId, $this->modalTaskAssigneeIds)) {
                $this->modalTaskAssigneeIds[] = $userId;
            }
        } elseif ($this->showEditModal) {
            // For edit modal
            if (!in_array($userId, $this->editModalTaskAssigneeIds)) {
                $this->editModalTaskAssigneeIds[] = $userId;
            }
        } else {
            // For inline editing
            if (!in_array($userId, $this->newTaskAssigneeIds)) {
                $this->newTaskAssigneeIds[] = $userId;
            }
        }
        $this->updateSelectedEmployeeNames();
    }

    public function removeEmployee($userId)
    {
        // Check if we're in task creation modal, edit modal, or inline editing
        if ($this->showTaskModal) {
            // For task creation modal
            $this->modalTaskAssigneeIds = array_filter($this->modalTaskAssigneeIds, function($id) use ($userId) {
                return $id != $userId;
            });
        } elseif ($this->showEditModal) {
            // For edit modal
            $this->editModalTaskAssigneeIds = array_filter($this->editModalTaskAssigneeIds, function($id) use ($userId) {
                return $id != $userId;
            });
        } else {
            // For inline editing
            $this->newTaskAssigneeIds = array_filter($this->newTaskAssigneeIds, function($id) use ($userId) {
                return $id != $userId;
            });
        }
        $this->updateSelectedEmployeeNames();
    }

    public function updateSelectedEmployeeNames()
    {
        $this->selectedEmployeeNames = [];
        
        // Determine which array to use based on context
        if ($this->showTaskModal) {
            $assigneeIds = $this->modalTaskAssigneeIds;
        } elseif ($this->showEditModal) {
            $assigneeIds = $this->editModalTaskAssigneeIds;
        } else {
            $assigneeIds = $this->newTaskAssigneeIds;
        }
        
        foreach ($assigneeIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->selectedEmployeeNames[] = $user->name;
            }
        }
    }
    
    public function getFilteredEmployeesProperty()
    {
        $employees = $this->users;
        
        if ($this->employeeSearch) {
            $employees = $employees->filter(function ($user) {
                return stripos($user->name, $this->employeeSearch) !== false;
            });
        }
        
        return $employees;
    }

    // Task modal methods
    public function showTaskCreationModal()
    {
        $this->showTaskModal = true;
        $this->resetModalTaskFields();
    }

    public function closeTaskModal()
    {
        $this->showTaskModal = false;
        $this->resetModalTaskFields();
    }

    public function resetModalTaskFields()
    {
        $this->modalTaskTitle = '';
        $this->modalTaskDescription = '';
        $this->modalTaskProjectId = '';
        $this->modalTaskAssigneeId = '';
        $this->modalTaskAssigneeIds = [];
        $this->modalTaskPriority = '';
        $this->modalTaskCategory = '';
        $this->modalTaskDueDate = '';
        $this->modalTaskEstimatedHours = '';
        $this->modalTaskReminderTime = '';
        $this->modalTaskNotes = '';
        $this->modalTaskNature = 'one_time';
        $this->modalTaskRecurrenceFrequency = 'daily';
        $this->modalTaskAttachments = [];
    }
    
    public function resetEditModalFields()
    {
        $this->editModalTaskId = null;
        $this->editModalTaskTitle = '';
        $this->editModalTaskDescription = '';
        $this->editModalTaskProjectId = '';
        $this->editModalTaskAssigneeIds = [];
        $this->editModalTaskPriority = '';
        $this->editModalTaskCategory = '';
        $this->editModalTaskDueDate = '';
        $this->editModalTaskEstimatedHours = '';
        $this->editModalTaskNature = 'one_time';
        $this->editModalTaskRecurrenceFrequency = 'daily';
        $this->editModalTaskReminderTime = '';
        $this->editModalTaskAttachments = [];
    }

    public function createTaskFromModal()
    {
        // Debug: Log the assignee IDs before validation
        LogFacade::info('Creating task from modal:', [
            'modalTaskAssigneeIds' => $this->modalTaskAssigneeIds,
            'count' => count($this->modalTaskAssigneeIds)
        ]);
        
        $this->validate([
            'modalTaskTitle' => 'required|string|max:255',
            'modalTaskDescription' => 'nullable|string',
            'modalTaskProjectId' => 'nullable|exists:projects,id',
            'modalTaskAssigneeIds' => 'required|array|min:1',
            'modalTaskAssigneeIds.*' => 'exists:users,id',
            'modalTaskPriority' => 'required|exists:task_priorities,id',
            'modalTaskCategory' => 'required|exists:task_categories,id',
            'modalTaskDueDate' => 'nullable|date|after_or_equal:today',
            'modalTaskEstimatedHours' => 'nullable|integer|min:0',
            'modalTaskReminderTime' => 'nullable',
            'modalTaskNotes' => 'nullable|string',
            'modalTaskNature' => 'required|in:one_time,recurring',
            'modalTaskRecurrenceFrequency' => 'required_if:modalTaskNature,recurring|in:daily,weekly,monthly',
            'modalTaskAttachments.*' => 'nullable|file|max:10240',
        ]);

        try {
            // Determine if task is recurring
            $isRecurring = $this->modalTaskNature === 'recurring';
            $isRecurringActive = $isRecurring ? 1 : 0;
            
            // Set nature_of_task based on type
            $natureOfTask = $this->modalTaskNature === 'recurring' ? $this->modalTaskRecurrenceFrequency : 'one_time';

            // Create the main task with the first assignee
            $primaryAssigneeId = !empty($this->modalTaskAssigneeIds) ? $this->modalTaskAssigneeIds[0] : null;
            
            if (!$primaryAssigneeId) {
                throw new \Exception('No assignee selected');
            }
            
            // Convert reminder time from datetime-local format to proper datetime
            $reminderTime = null;
            if ($this->modalTaskReminderTime && $this->modalTaskReminderTime != '') {
                try {
                    // Parse datetime-local format (2025-10-29T22:15)
                    $reminderTime = date('Y-m-d H:i:s', strtotime($this->modalTaskReminderTime));
                } catch (\Exception $e) {
                    LogFacade::error('Failed to parse reminder time: ' . $e->getMessage());
                    $reminderTime = null;
                }
            }
            
            $task = Task::create([
                'project_id' => $this->modalTaskProjectId ?: null,
                'title' => $this->modalTaskTitle,
                'description' => $this->modalTaskDescription,
                'assigned_to_user_id' => $primaryAssigneeId, // First assignee as primary
                'assigned_by_user_id' => auth()->id(),
                'priority_id' => $this->modalTaskPriority,
                'category_id' => $this->modalTaskCategory,
                'status_id' => TaskStatus::where('name', 'Pending')->first()->id,
                'due_date' => $this->modalTaskDueDate,
                'estimated_hours' => $this->modalTaskEstimatedHours,
                'reminder_time' => $reminderTime,
                'notes' => $this->modalTaskNotes,
                'nature_of_task' => $natureOfTask,
                'is_recurring' => $isRecurring,
                'is_recurring_active' => $isRecurringActive,
            ]);

            // Attach all assignees to the assignees relationship
            if (!empty($this->modalTaskAssigneeIds)) {
                // Debug: Log the assignee IDs
                LogFacade::info('Attaching assignees:', [
                    'task_id' => $task->id,
                    'assignees' => $this->modalTaskAssigneeIds
                ]);
                
                // Attach all assignees with pivot data
                $assignments = [];
                foreach ($this->modalTaskAssigneeIds as $userId) {
                    $assignments[$userId] = [
                        'assigned_by_user_id' => auth()->id(),
                        'assigned_at' => now(),
                    ];
                }
                
                $task->assignees()->attach($assignments);
                
                // Debug: Verify attachment
                $attachedCount = $task->assignees()->count();
                LogFacade::info('Assignees attached successfully. Total count: ' . $attachedCount);
            }

            // Handle attachments
            if ($this->modalTaskAttachments) {
                foreach ($this->modalTaskAttachments as $attachment) {
                    $path = $attachment->store('attachments');
                    
                    \App\Models\Attachment::create([
                        'task_id' => $task->id,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_size' => $attachment->getSize(),
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            // Send email notification to all assignees
            $this->emailService->sendTaskAssignedNotification($task);
            
            // Send notifications to additional assignees
            if (count($this->modalTaskAssigneeIds) > 1) {
                foreach ($this->modalTaskAssigneeIds as $assigneeId) {
                    if ($assigneeId != $task->assigned_to_user_id) {
                        $assignee = User::find($assigneeId);
                        if ($assignee) {
                            $this->emailService->sendTaskAssignedNotification($task, $assignee);
                        }
                    }
                }
            }

            session()->flash('success', 'Task created successfully!');
            $this->closeTaskModal();
            $this->dispatch('tasksUpdated');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are automatically displayed by Livewire
            throw $e;
        } catch (\Exception $e) {
            LogFacade::error('Task creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to create task: ' . $e->getMessage());
        }
    }
    
    public function updateTaskFromModal()
    {
        $this->validate([
            'editModalTaskTitle' => 'required|string|max:255',
            'editModalTaskDescription' => 'nullable|string',
            'editModalTaskProjectId' => 'nullable|exists:projects,id',
            'editModalTaskAssigneeIds' => 'required|array|min:1',
            'editModalTaskAssigneeIds.*' => 'exists:users,id',
            'editModalTaskPriority' => 'required|exists:task_priorities,id',
            'editModalTaskCategory' => 'required|exists:task_categories,id',
            'editModalTaskDueDate' => 'nullable|date|after_or_equal:today',
            'editModalTaskEstimatedHours' => 'nullable|integer|min:0',
            'editModalTaskNature' => 'required|in:one_time,recurring',
            'editModalTaskRecurrenceFrequency' => 'required_if:editModalTaskNature,recurring|in:daily,weekly,monthly',
            'editModalTaskReminderTime' => 'nullable',
            'editModalTaskAttachments.*' => 'nullable|file|max:10240',
        ]);

        try {
            // Determine if task is recurring
            $isRecurring = $this->editModalTaskNature === 'recurring';
            $isRecurringActive = $isRecurring ? 1 : 0;
            
            // Set nature_of_task based on type
            $natureOfTask = $this->editModalTaskNature === 'recurring' ? $this->editModalTaskRecurrenceFrequency : 'one_time';

            // Create the main task with the first assignee
            $primaryAssigneeId = !empty($this->editModalTaskAssigneeIds) ? $this->editModalTaskAssigneeIds[0] : null;
            
            if (!$primaryAssigneeId) {
                throw new \Exception('No assignee selected');
            }
            
            // Convert reminder time from datetime-local format to proper datetime
            $reminderTime = null;
            if ($this->editModalTaskReminderTime && $this->editModalTaskReminderTime != '') {
                try {
                    // Parse datetime-local format (2025-10-29T22:15)
                    $reminderTime = date('Y-m-d H:i:s', strtotime($this->editModalTaskReminderTime));
                } catch (\Exception $e) {
                    LogFacade::error('Failed to parse reminder time: ' . $e->getMessage());
                    $reminderTime = null;
                }
            }
            
            $task = Task::findOrFail($this->editModalTaskId);
            $task->update([
                'project_id' => $this->editModalTaskProjectId ?: null,
                'title' => $this->editModalTaskTitle,
                'description' => $this->editModalTaskDescription,
                'assigned_to_user_id' => $primaryAssigneeId,
                'assigned_by_user_id' => auth()->id(),
                'priority_id' => $this->editModalTaskPriority,
                'category_id' => $this->editModalTaskCategory,
                'due_date' => $this->editModalTaskDueDate,
                'estimated_hours' => $this->editModalTaskEstimatedHours,
                'reminder_time' => $reminderTime,
                'nature_of_task' => $natureOfTask,
                'is_recurring' => $isRecurring,
                'is_recurring_active' => $isRecurringActive,
            ]);

            // Update multiple assignees
            $task->syncAssignees($this->editModalTaskAssigneeIds, auth()->id());

            // Handle new attachments
            if ($this->editModalTaskAttachments) {
                foreach ($this->editModalTaskAttachments as $attachment) {
                    $path = $attachment->store('attachments');
                    
                    \App\Models\Attachment::create([
                        'task_id' => $task->id,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_size' => $attachment->getSize(),
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            // Send email notification for task update
            $this->emailService->sendTaskUpdatedNotification($task, 'Task Details Updated');

            session()->flash('success', 'Task updated successfully!');
            $this->closeEditModal();
            $this->dispatch('tasksUpdated');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            LogFacade::error('Task update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to update task: ' . $e->getMessage());
        }
    }
    
    public function openCloneModal($taskId)
    {
        $this->cloneModalTaskId = $taskId;
        $this->cloneModalDueDate = '';
        $this->showCloneModal = true;
    }
    
    public function closeCloneModal()
    {
        $this->showCloneModal = false;
        $this->cloneModalTaskId = null;
        $this->cloneModalDueDate = '';
    }
    
    public function cloneTask()
    {
        $this->validate([
            'cloneModalDueDate' => 'required|date|after:today',
        ]);

        try {
            $originalTask = Task::with('assignees')->findOrFail($this->cloneModalTaskId);
            
            // Create the cloned task
            $clonedTask = Task::create([
                'project_id' => $originalTask->project_id,
                'title' => $originalTask->title . ' (Copy)',
                'description' => $originalTask->description,
                'assigned_to_user_id' => $originalTask->assigned_to_user_id,
                'assigned_by_user_id' => auth()->id(),
                'priority_id' => $originalTask->priority_id,
                'category_id' => $originalTask->category_id,
                'status_id' => TaskStatus::where('name', 'Pending')->first()->id,
                'due_date' => $this->cloneModalDueDate,
                'estimated_hours' => $originalTask->estimated_hours,
                'reminder_time' => $originalTask->reminder_time,
                'notes' => $originalTask->notes,
                'nature_of_task' => $originalTask->nature_of_task,
                'is_recurring' => $originalTask->is_recurring,
                'is_recurring_active' => $originalTask->is_recurring_active,
            ]);

            // Clone assignees
            if ($originalTask->assignees && $originalTask->assignees->count() > 0) {
                $assigneeIds = $originalTask->assignees->pluck('id')->toArray();
                $clonedTask->syncAssignees($assigneeIds, auth()->id());
            }

            // Clone attachments
            if ($originalTask->attachments && $originalTask->attachments->count() > 0) {
                foreach ($originalTask->attachments as $attachment) {
                    \App\Models\Attachment::create([
                        'task_id' => $clonedTask->id,
                        'file_path' => $attachment->file_path,
                        'file_name' => $attachment->file_name,
                        'file_size' => $attachment->file_size,
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            LogFacade::info('Task cloned successfully:', [
                'original_task_id' => $originalTask->id,
                'cloned_task_id' => $clonedTask->id
            ]);

            session()->flash('success', 'Task cloned successfully!');
            $this->closeCloneModal();
            $this->dispatch('tasksUpdated');

        } catch (\Exception $e) {
            LogFacade::error('Task clone failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to clone task: ' . $e->getMessage());
        }
    }
    
    public function openProjectCreateModal()
    {
        $this->showProjectCreateModal = true;
        $this->newProjectTitle = '';
        $this->newProjectDescription = '';
    }
    
    public function closeProjectCreateModal()
    {
        $this->showProjectCreateModal = false;
        $this->newProjectTitle = '';
        $this->newProjectDescription = '';
    }
    
    public function createProjectFromModal()
    {
        $this->validate([
            'newProjectTitle' => 'required|string|max:255',
            'newProjectDescription' => 'required|string|min:10',
        ]);

        try {
            $project = Project::create([
                'title' => $this->newProjectTitle,
                'description' => $this->newProjectDescription,
                'created_by_user_id' => auth()->id(),
            ]);

            // Log the creation
            Log::createLog(auth()->id(), 'create_project', "Created project: {$project->title}");

            // Close the modal and set the newly created project in the task form
            $this->modalTaskProjectId = $project->id;
            
            // Clear the form fields
            $this->newProjectTitle = '';
            $this->newProjectDescription = '';
            
            // Close the modal
            $this->closeProjectCreateModal();
            
            // Dispatch event to refresh projects list
            $this->dispatch('project-created');
            
            session()->flash('success', 'Project created successfully! The project has been selected in the task form.');

        } catch (\Exception $e) {
            LogFacade::error('Project creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to create project: ' . $e->getMessage());
        }
    }
    
    // Category creation modal methods
    public function openCategoryCreateModal()
    {
        $this->showCategoryCreateModal = true;
        $this->newCategoryTitle = '';
        $this->newCategoryIcon = 'bi-list-task';
        $this->newCategoryColor = 'secondary';
    }
    
    public function closeCategoryCreateModal()
    {
        $this->showCategoryCreateModal = false;
        $this->newCategoryTitle = '';
        $this->newCategoryIcon = 'bi-list-task';
        $this->newCategoryColor = 'secondary';
    }
    
    public function createCategoryFromModal()
    {
        $this->validate([
            'newCategoryTitle' => 'required|string|max:255|unique:task_categories,name',
            'newCategoryIcon' => 'required|string|max:50',
            'newCategoryColor' => 'required|string|in:primary,secondary,success,danger,warning,info,dark',
        ]);

        try {
            $category = TaskCategory::create([
                'name' => $this->newCategoryTitle,
                'icon' => $this->newCategoryIcon,
                'color' => $this->newCategoryColor,
                'is_default' => false,
            ]);

            // Log the creation
            Log::createLog(auth()->id(), 'create_category', "Created category: {$category->name}");

            // Close the modal and set the newly created category in the task form
            $this->modalTaskCategory = $category->id;
            
            // Clear the form fields
            $this->newCategoryTitle = '';
            $this->newCategoryIcon = 'bi-list-task';
            $this->newCategoryColor = 'secondary';
            
            // Close the modal
            $this->closeCategoryCreateModal();
            
            // Dispatch event to refresh categories list
            $this->dispatch('category-created');
            
            session()->flash('success', 'Category created successfully! The category has been selected in the task form.');

        } catch (\Exception $e) {
            LogFacade::error('Category creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to create category: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.task.task-table');
    }

    public function refreshSelect2()
    {
        $this->dispatch('refresh-select2');
    }

    public function removeNotesAttachment($index)
    {
        unset($this->notesAttachments[$index]);
        $this->notesAttachments = array_values($this->notesAttachments);
    }

    public function deleteNotesAttachment($attachmentId)
    {
        $this->notesAttachmentsToDelete[] = $attachmentId;
    }

    public function getTaskNotesAttachments()
    {
        if ($this->notesModalTaskId && $this->notesModalTaskId != 0) {
            return Attachment::where('task_id', $this->notesModalTaskId)
                ->whereNull('comment_id')
                ->with('uploadedBy')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return collect();
    }

    public function openFilePreview($attachmentId)
    {
        $this->previewFile = Attachment::find($attachmentId);
        $this->showFilePreviewModal = true;
    }

    public function closeFilePreview()
    {
        $this->showFilePreviewModal = false;
        $this->previewFile = null;
    }

    public function isPreviewableFile($fileExtension)
    {
        $previewableExtensions = ['pdf', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv'];
        return in_array(strtolower($fileExtension), $previewableExtensions);
    }

    public function getFilePreviewUrl($attachment)
    {
        if ($this->isPreviewableFile(pathinfo($attachment->file_name, PATHINFO_EXTENSION))) {
            return route('attachments.preview', $attachment->id);
        }
        return null;
    }

    public function initializeModalTooltips()
    {
        // This method is called when the modal content is initialized
        // The actual tooltip initialization happens in JavaScript
        $this->dispatch('initialize-modal-tooltips');
    }

    // File Attachment Modal Properties
    public $attachFiles = [];

    public function updatedAttachFiles()
    {
        // Process the uploaded files
        if ($this->attachFiles && count($this->attachFiles) > 0) {
            foreach ($this->attachFiles as $file) {
                $this->attachFileToTask($file);
            }
            
            // Clear the files array after processing
            $this->attachFiles = [];
            
            // Show success message
            session()->flash('message', 'Files attached successfully!');
        }
    }

    public function removeAttachFile($index)
    {
        if (isset($this->attachFiles[$index])) {
            unset($this->attachFiles[$index]);
            $this->attachFiles = array_values($this->attachFiles); // Re-index array
        }
    }

    private function attachFileToTask($file)
    {
        $originalName = $file->getClientOriginalName();
        $fileName = time() . '_' . $originalName;
        $filePath = $file->storeAs('attachments', $fileName, 'public');

        Attachment::create([
            'task_id' => $this->notesModalTaskId,
            'file_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'uploaded_by_user_id' => auth()->id(),
        ]);
    }

    // Bulk Actions
    public function bulkUpdateStatus($taskIds, $statusId)
    {
        try {
            $tasks = Task::whereIn('id', $taskIds)->get();
            $updatedCount = 0;
            $skippedCount = 0;
            
            foreach ($tasks as $task) {
                // Skip approved tasks
                if ($task->is_approved) {
                    $skippedCount++;
                    continue;
                }
                
                $oldStatusId = $task->status_id;
                $task->status_id = $statusId;
                $task->save();
                $updatedCount++;
                
                // Log the status change
                Log::createLog(auth()->id(), 'bulk_update_status', 
                    "Bulk updated task '{$task->title}' status from {$oldStatusId} to {$statusId}");
            }
            
            $message = "Successfully updated {$updatedCount} task(s) status.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} task(s) were skipped (already approved).";
            }
            
            session()->flash('success', $message);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating task status: ' . $e->getMessage());
        }
    }

    public function bulkUpdatePriority($taskIds, $priorityId)
    {
        try {
            $tasks = Task::whereIn('id', $taskIds)->get();
            $updatedCount = 0;
            
            foreach ($tasks as $task) {
                $oldPriorityId = $task->priority_id;
                $task->priority_id = $priorityId;
                $task->save();
                $updatedCount++;
                
                // Log the priority change
                Log::createLog(auth()->id(), 'bulk_update_priority', 
                    "Bulk updated task '{$task->title}' priority from {$oldPriorityId} to {$priorityId}");
            }
            
            session()->flash('success', "Successfully updated {$updatedCount} task(s) priority.");
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating task priority: ' . $e->getMessage());
        }
    }

    public function bulkUpdateAssignee($taskIds, $userId)
    {
        try {
            \Illuminate\Support\Facades\Log::info('Bulk update assignee called', [
                'task_ids' => $taskIds,
                'user_id' => $userId,
                'user' => auth()->user()->name ?? 'Unknown'
            ]);
            
            $tasks = Task::whereIn('id', $taskIds)->get();
            $updatedCount = 0;
            
            \Illuminate\Support\Facades\Log::info('Found tasks for bulk assignee update', [
                'task_count' => $tasks->count(),
                'task_ids' => $tasks->pluck('id')->toArray()
            ]);
            
            foreach ($tasks as $task) {
                $oldAssigneeId = $task->assigned_to_user_id;
                $task->assigned_to_user_id = $userId;
                $task->save();
                
                // Sync the assignees relationship to update the assignees table
                $task->syncAssignees([$userId], auth()->id());
                
                // Reload the task with relationships to ensure fresh data
                $task->load(['assignedTo', 'assignedBy', 'assignees']);
                $updatedCount++;
                
                \Illuminate\Support\Facades\Log::info('Updated task assignee', [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'old_assignee' => $oldAssigneeId,
                    'new_assignee' => $userId
                ]);
                
                // Log the assignee change
                Log::createLog(auth()->id(), 'bulk_update_assignee', 
                    "Bulk updated task '{$task->title}' assignee from {$oldAssigneeId} to {$userId}");
            }
            
            \Illuminate\Support\Facades\Log::info('Bulk assignee update completed', [
                'updated_count' => $updatedCount
            ]);
            
            session()->flash('success', "Successfully assigned {$updatedCount} task(s) to user.");
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in bulk assignee update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Error updating task assignee: ' . $e->getMessage());
        }
    }

    public function bulkUpdateNature($taskIds, $nature)
    {
        try {
            $tasks = Task::whereIn('id', $taskIds)->get();
            $updatedCount = 0;
            
            // Convert 'recurring' to appropriate nature_of_task value
            // For bulk update, we need to determine the nature based on the input
            // If nature is 'recurring', we'll need to ask for frequency or use default
            // For now, if nature is 'recurring', we'll set it to 'weekly' as default
            $natureOfTask = ($nature === 'recurring') ? 'weekly' : $nature;
            
            foreach ($tasks as $task) {
                $oldNature = $task->nature_of_task;
                
                // Update nature_of_task
                $task->nature_of_task = $natureOfTask;
                
                // If setting to recurring type, ensure is_recurring_active is true
                if (in_array($natureOfTask, ['daily', 'weekly', 'monthly', 'until_stop'])) {
                    $task->is_recurring = true;
                    $task->is_recurring_active = true;
                } else {
                    $task->is_recurring = false;
                    $task->is_recurring_active = false;
                }
                
                $task->save();
                $updatedCount++;
                
                // Log the nature change
                Log::createLog(auth()->id(), 'bulk_update_nature', 
                    "Bulk updated task '{$task->title}' nature from {$oldNature} to {$natureOfTask}");
            }
            
            session()->flash('success', "Successfully updated {$updatedCount} task(s) nature.");
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating task nature: ' . $e->getMessage());
        }
    }

    public function bulkDeleteTasks($taskIds)
    {
        try {
            $tasks = Task::whereIn('id', $taskIds)->get();
            $deletedCount = 0;
            
            foreach ($tasks as $task) {
                $taskTitle = $task->title;
                
                // Log the deletion
                Log::createLog(auth()->id(), 'bulk_delete_task', 
                    "Bulk deleted task '{$taskTitle}'");
                
                $task->delete();
                $deletedCount++;
            }
            
            session()->flash('success', "Successfully deleted {$deletedCount} task(s).");
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting tasks: ' . $e->getMessage());
        }
    }

    /**
     * Show admin review modal for completed tasks
     */
    public function showAdminReview($taskId)
    {
        $task = Task::findOrFail($taskId);
        
        // Check if user is admin or super admin
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            session()->flash('error', 'Only administrators can review completed tasks.');
            return;
        }
        
        // Check if task is completed
        if (!$task->status || $task->status->name !== 'Complete') {
            session()->flash('error', 'Only completed tasks can be reviewed.');
            return;
        }
        
        $this->adminReviewTaskId = $taskId;
        $this->adminReviewComments = '';
        $this->adminReviewAction = '';
        $this->showAdminReviewModal = true;
        
        // Debug: Log the action
        \Illuminate\Support\Facades\Log::info('Admin review modal opened', [
            'task_id' => $taskId,
            'user_id' => $user->id,
            'user_role' => $user->role ? $user->role->name : 'no_role',
            'modal_state' => $this->showAdminReviewModal
        ]);
    }

    /**
     * Approve completed task (mark as final completed)
     */
    public function approveTask()
    {
        $this->validate([
            'adminReviewComments' => 'nullable|string|max:1000',
        ]);
        
        $task = Task::findOrFail($this->adminReviewTaskId);
        
        // Update task to mark as approved
        $task->update(['is_approved' => true]);
        
        // Log the approval
        Log::createLog(auth()->id(), 'task_approved', 
            "Approved completed task '{$task->title}'" . 
            ($this->adminReviewComments ? " with comments: {$this->adminReviewComments}" : ''));
        
        $this->closeAdminReviewModal();
        session()->flash('success', 'Task has been approved and marked as completed.');
    }

    /**
     * Mark task for revisit
     */
    public function revisitTask()
    {
        $this->validate([
            'adminReviewComments' => 'nullable|string|max:1000',
        ]);
        
        $task = Task::findOrFail($this->adminReviewTaskId);
        $oldStatus = $task->status ? $task->status->name : 'No Status';
        
        // Get "Needs Revisit" status
        $needsRevisitStatus = TaskStatus::where('name', 'Needs Revisit')->first();
        
        if (!$needsRevisitStatus) {
            session()->flash('error', 'Needs Revisit status not found. Please contact system administrator.');
            return;
        }
        
        // Update task status
        $task->update(['status_id' => $needsRevisitStatus->id]);
        
        // Log the revisit action
        Log::createLog(auth()->id(), 'task_revisit', 
            "Marked task '{$task->title}' for revisit" . 
            ($this->adminReviewComments ? " with comments: {$this->adminReviewComments}" : ''));
        
        // Send email notification to assignees
        $adminName = auth()->user()->name;
        $this->emailService->sendTaskRevisitNotification($task, $this->adminReviewComments, $adminName);
        
        $this->closeAdminReviewModal();
        session()->flash('success', 'Task has been marked for revisit. Email notification sent to assignees.');
    }

    /**
     * Close admin review modal
     */
    public function closeAdminReviewModal()
    {
        $this->showAdminReviewModal = false;
        $this->adminReviewTaskId = null;
        $this->adminReviewComments = '';
        $this->adminReviewAction = '';
    }

    /**
     * Direct approve task with comments modal
     */
    public function showApproveModal($taskId)
    {
        $task = Task::findOrFail($taskId);
        
        // Check permissions
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isAdmin() && $task->assigned_by_user_id !== $user->id) {
            session()->flash('error', 'You are not authorized to approve this task.');
            return;
        }
        
        // Check if task is completed
        if (!$task->status || $task->status->name !== 'Complete') {
            session()->flash('error', 'Only completed tasks can be approved.');
            return;
        }
        
        // Show comments modal for approval
        $this->adminReviewTaskId = $taskId;
        $this->adminReviewComments = '';
        $this->adminReviewAction = 'approve';
        $this->showAdminReviewModal = true;
    }

    /**
     * Direct revisit task with comments modal
     */
    public function showRevisitModal($taskId)
    {
        $task = Task::findOrFail($taskId);
        
        // Check permissions
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isAdmin() && $task->assigned_by_user_id !== $user->id) {
            session()->flash('error', 'You are not authorized to mark this task for revisit.');
            return;
        }
        
        // Check if task is completed
        if (!$task->status || $task->status->name !== 'Complete') {
            session()->flash('error', 'Only completed tasks can be marked for revisit.');
            return;
        }
        
        // Show comments modal for revisit
        $this->adminReviewTaskId = $taskId;
        $this->adminReviewComments = '';
        $this->adminReviewAction = 'revisit';
        $this->showAdminReviewModal = true;
    }
}

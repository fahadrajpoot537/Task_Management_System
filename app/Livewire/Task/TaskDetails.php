<?php

namespace App\Livewire\Task;

use App\Models\Log;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskNoteComment;
use App\Models\Attachment;
use App\Services\EmailNotificationService;
use Livewire\Component;
use Livewire\WithFileUploads;

class TaskDetails extends Component
{
    use WithFileUploads;

    public Task $task;
    public $newNote = '';
    public $newAttachments = [];
    public $newComment = '';
    public $commentAttachments = [];
    
    // Admin review properties
    public $showAdminReviewModal = false;
    public $adminReviewComments = '';
    public $adminReviewAction = ''; // 'approve' or 'revisit'
    
    // Email service
    protected $emailService;

    protected $rules = [
        'newNote' => 'nullable|string',
        'newAttachments.*' => 'nullable|file|max:10240', // 10MB max
        'newComment' => 'nullable|string',
        'commentAttachments.*' => 'nullable|file|max:10240', // 10MB max
    ];

    public function mount($taskId)
    {
        $user = auth()->user();
        
        $this->task = Task::with(['project', 'assignedTo', 'assignedBy', 'status', 'priority', 'category', 'attachments.uploadedBy', 'noteComments.user', 'noteComments.attachments.uploadedBy', 'assignees'])
            ->findOrFail($taskId);
            
        // Check if user can access this task
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            // Check if user is in the assignees list (including the primary assignee)
            $isAssignee = $this->task->assigned_to_user_id == $user->id || 
                          $this->task->assignees->contains('id', $user->id);
            $isCreator = $this->task->assigned_by_user_id == $user->id;
            
            if ($user->isManager()) {
                // Managers can see tasks assigned to their team members and themselves
                $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                $isTeamTask = in_array($this->task->assigned_to_user_id, $teamMemberIds->toArray());
                
                if (!$isAssignee && !$isCreator && !$isTeamTask) {
                    abort(403, 'You do not have permission to view this task.');
                }
            } else {
                // Employees can only see tasks assigned to them or created by them
                if (!$isAssignee && !$isCreator) {
                    abort(403, 'You do not have permission to view this task.');
                }
            }
        }
    }

    public function updateStatus($status)
    {
        $user = auth()->user();
        
        // Check if user can update this task
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $isAssignee = $this->task->assigned_to_user_id == $user->id || 
                          $this->task->assignees->contains('id', $user->id);
            $isCreator = $this->task->assigned_by_user_id == $user->id;
            
            if ($user->isManager()) {
                // Managers can update tasks assigned to their team members and themselves
                $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                $isTeamTask = in_array($this->task->assigned_to_user_id, $teamMemberIds->toArray());
                
                if (!$isAssignee && !$isCreator && !$isTeamTask) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            } else {
                // Employees can only update tasks assigned to them or created by them
                if (!$isAssignee && !$isCreator) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            }
        }

        $oldStatus = $this->task->status;
        $this->task->update(['status' => $status]);

        // Log the status change
        Log::createLog(auth()->id(), 'update_task_status', 
            "Changed task '{$this->task->title}' status from {$oldStatus} to {$status}");

        session()->flash('success', 'Task status updated successfully.');
    }

    public function addNote()
    {
        $this->validate(['newNote' => 'required|string']);

        $currentNotes = $this->task->notes ? $this->task->notes . "\n\n" : '';
        $newNote = "[" . now()->format('Y-m-d H:i:s') . "] " . auth()->user()->name . ": " . $this->newNote;
        
        $this->task->update([
            'notes' => $currentNotes . $newNote
        ]);

        // Log the note addition
        Log::createLog(auth()->id(), 'add_task_note', 
            "Added note to task: {$this->task->title}");

        $this->newNote = '';
        session()->flash('success', 'Note added successfully.');
    }

    public function addAttachments()
    {
        $this->validate(['newAttachments.*' => 'required|file|max:10240']);

        foreach ($this->newAttachments as $attachment) {
            $path = $attachment->store('attachments');
            
            $this->task->attachments()->create([
                'file_path' => $path,
                'file_name' => $attachment->getClientOriginalName(),
                'uploaded_by_user_id' => auth()->id(),
            ]);
        }

        // Log the attachment addition
        Log::createLog(auth()->id(), 'add_task_attachment', 
            "Added attachments to task: {$this->task->title}");

        $this->newAttachments = [];
        $this->task->load('attachments.uploadedBy');
        session()->flash('success', 'Attachments uploaded successfully.');
    }

    public function deleteAttachment($attachmentId)
    {
        $user = auth()->user();
        $attachment = $this->task->attachments()->findOrFail($attachmentId);
        
        // Check if user can delete this attachment
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            if ($user->isManager()) {
                // Managers can delete attachments from tasks assigned to their team members and themselves
                $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                if (!in_array($this->task->assigned_to_user_id, $teamMemberIds->toArray()) && 
                    $this->task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'You do not have permission to delete this attachment.');
                    return;
                }
            } else {
                // Employees can only delete attachments from tasks assigned to them
                if ($this->task->assigned_to_user_id !== $user->id && 
                    $this->task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'You do not have permission to delete this attachment.');
                    return;
                }
            }
        }

        // Delete file from storage
        if (file_exists(storage_path('app/' . $attachment->file_path))) {
            unlink(storage_path('app/' . $attachment->file_path));
        }

        // Log the deletion
        Log::createLog(auth()->id(), 'delete_task_attachment', 
            "Deleted attachment from task: {$this->task->title}");

        $attachment->delete();
        $this->task->load('attachments.uploadedBy');
        
        session()->flash('success', 'Attachment deleted successfully.');
    }

    public function updateTaskStatus($taskId, $statusId)
    {
        $user = auth()->user();
        
        // Check if task is already approved - prevent status changes
        if ($this->task->is_approved) {
            session()->flash('error', 'Cannot change status of an approved task.');
            return;
        }
        
        // Check if user can update this task
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $isAssignee = $this->task->assigned_to_user_id == $user->id || 
                          $this->task->assignees->contains('id', $user->id);
            $isCreator = $this->task->assigned_by_user_id == $user->id;
            
            if ($user->isManager()) {
                // Managers can update tasks assigned to their team members and themselves
                $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                $isTeamTask = in_array($this->task->assigned_to_user_id, $teamMemberIds->toArray());
                
                if (!$isAssignee && !$isCreator && !$isTeamTask) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            } else {
                // Employees can only update tasks assigned to them or created by them
                if (!$isAssignee && !$isCreator) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            }
        }

        $oldStatus = $this->task->status ? $this->task->status->name : 'No Status';
        $this->task->update(['status_id' => $statusId]);
        $this->task->load('status');
        
        $newStatus = $this->task->status->name;

        // Log the status change
        Log::createLog(auth()->id(), 'update_task_status', 
            "Changed task '{$this->task->title}' status from {$oldStatus} to {$newStatus}");

        // Process recurring task if status is "Complete"
        if ($newStatus === 'Complete') {
            $recurringService = new \App\Services\RecurringTaskService();
            $recurringService->processRecurringTask($this->task);
        }

        session()->flash('success', 'Task status updated successfully.');
    }

    public function stopRecurringTask()
    {
        if ($this->task->nature_of_task === 'recurring') {
            $recurringService = new \App\Services\RecurringTaskService();
            $recurringService->stopRecurringTask($this->task);
            
            // Log the action
            Log::createLog(auth()->id(), 'stop_recurring_task', "Stopped recurring task: {$this->task->title}");
            
            session()->flash('success', 'Recurring task generation stopped successfully!');
        } else {
            session()->flash('error', 'This task is not a recurring task.');
        }
    }

    public function getStatusesProperty()
    {
        return TaskStatus::orderBy('name')->get();
    }

    public function addComment()
    {
        try {
            $this->validate([
                'newComment' => 'required|string',
                'commentAttachments.*' => 'nullable|file|max:10240'
            ]);

            // Create the comment
            $comment = TaskNoteComment::create([
                'task_id' => $this->task->id,
                'user_id' => auth()->id(),
                'comment' => $this->newComment,
            ]);

            // Handle comment attachments
            if ($this->commentAttachments && count($this->commentAttachments) > 0) {
                foreach ($this->commentAttachments as $attachment) {
                    $path = $attachment->store('attachments');
                    
                    Attachment::create([
                        'task_id' => $this->task->id,
                        'comment_id' => $comment->id,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_size' => $attachment->getSize(),
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            // Log the comment addition
            Log::createLog(auth()->id(), 'add_task_comment', 
                "Added comment to task: {$this->task->title}");

            $this->newComment = '';
            $this->commentAttachments = [];
            
            // Reload task with all relationships including comment attachments
            $this->task = $this->task->fresh();
            $this->task->load([
                'noteComments.user', 
                'noteComments.attachments.uploadedBy'
            ]);
            
            session()->flash('success', 'Comment added successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error adding comment: ' . $e->getMessage());
        }
    }

    public function deleteComment($commentId)
    {
        $user = auth()->user();
        $comment = TaskNoteComment::findOrFail($commentId);
        
        // Check if user can delete this comment
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            if ($comment->user_id !== $user->id) {
                session()->flash('error', 'You can only delete your own comments.');
                return;
            }
        }

        // Delete comment attachments
        foreach ($comment->attachments as $attachment) {
            if (file_exists(storage_path('app/' . $attachment->file_path))) {
                unlink(storage_path('app/' . $attachment->file_path));
            }
        }

        // Log the deletion
        Log::createLog(auth()->id(), 'delete_task_comment', 
            "Deleted comment from task: {$this->task->title}");

        $comment->delete();
        $this->task->load('noteComments.user', 'noteComments.attachments');
        
        session()->flash('success', 'Comment deleted successfully.');
    }

    /**
     * Show admin review modal for completed tasks
     */
    public function showAdminReview()
    {
        // Check if user is admin, super admin, manager, or task creator
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager() && $this->task->assigned_by_user_id !== $user->id) {
            session()->flash('error', 'Only administrators, managers, or task creators can review completed tasks.');
            return;
        }
        
        // Check if task is completed
        if (!$this->task->status || $this->task->status->name !== 'Complete') {
            session()->flash('error', 'Only completed tasks can be reviewed.');
            return;
        }
        
        $this->adminReviewComments = '';
        $this->adminReviewAction = '';
        $this->showAdminReviewModal = true;
    }

    /**
     * Approve completed task (mark as final completed)
     */
    public function approveTask()
    {
        $this->validate([
            'adminReviewComments' => 'nullable|string|max:1000',
        ]);
        
        // Update task to mark as approved
        $this->task->update(['is_approved' => true]);
        
        // Log the approval
        Log::createLog(auth()->id(), 'task_approved', 
            "Approved completed task '{$this->task->title}'" . 
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
        
        $oldStatus = $this->task->status ? $this->task->status->name : 'No Status';
        
        // Get "Needs Revisit" status
        $needsRevisitStatus = TaskStatus::where('name', 'Needs Revisit')->first();
        
        if (!$needsRevisitStatus) {
            session()->flash('error', 'Needs Revisit status not found. Please contact system administrator.');
            return;
        }
        
        // Update task status
        $this->task->update(['status_id' => $needsRevisitStatus->id]);
        $this->task->load('status');
        
        // Log the revisit action
        Log::createLog(auth()->id(), 'task_revisit', 
            "Marked task '{$this->task->title}' for revisit" . 
            ($this->adminReviewComments ? " with comments: {$this->adminReviewComments}" : ''));
        
        // Send email notification to assignees
        if (!$this->emailService) {
            $this->emailService = new EmailNotificationService();
        }
        
        $adminName = auth()->user()->name;
        $this->emailService->sendTaskRevisitNotification($this->task, $this->adminReviewComments, $adminName);
        
        $this->closeAdminReviewModal();
        session()->flash('success', 'Task has been marked for revisit. Email notification sent to assignees.');
    }

    /**
     * Close admin review modal
     */
    public function closeAdminReviewModal()
    {
        $this->showAdminReviewModal = false;
        $this->adminReviewComments = '';
        $this->adminReviewAction = '';
    }

    /**
     * Direct approve task with comments modal
     */
    public function showApproveModal()
    {
        // Check permissions
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager() && $this->task->assigned_by_user_id !== $user->id) {
            session()->flash('error', 'You are not authorized to approve this task.');
            return;
        }
        
        // Check if task is completed
        if (!$this->task->status || $this->task->status->name !== 'Complete') {
            session()->flash('error', 'Only completed tasks can be approved.');
            return;
        }
        
        // Show comments modal for approval
        $this->adminReviewComments = '';
        $this->adminReviewAction = 'approve';
        $this->showAdminReviewModal = true;
    }

    /**
     * Direct revisit task with comments modal
     */
    public function showRevisitModal()
    {
        // Check permissions
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager() && $this->task->assigned_by_user_id !== $user->id) {
            session()->flash('error', 'You are not authorized to mark this task for revisit.');
            return;
        }
        
        // Check if task is completed
        if (!$this->task->status || $this->task->status->name !== 'Complete') {
            session()->flash('error', 'Only completed tasks can be marked for revisit.');
            return;
        }
        
        // Show comments modal for revisit
        $this->adminReviewComments = '';
        $this->adminReviewAction = 'revisit';
        $this->showAdminReviewModal = true;
    }

    public function render()
    {
        return view('livewire.task.task-details')
            ->layout('layouts.app');
    }
}

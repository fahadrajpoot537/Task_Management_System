<?php

namespace App\Livewire\Task;

use App\Models\Log;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskNoteComment;
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

    protected $rules = [
        'newNote' => 'nullable|string',
        'newAttachments.*' => 'nullable|file|max:10240', // 10MB max
        'newComment' => 'nullable|string',
        'commentAttachments.*' => 'nullable|file|max:10240', // 10MB max
    ];

    public function mount($taskId)
    {
        $user = auth()->user();
        
        $this->task = Task::with(['project', 'assignedTo', 'assignedBy', 'attachments.uploadedBy', 'noteComments.user', 'noteComments.attachments'])
            ->findOrFail($taskId);
            
        // Check if user can access this task
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            if ($user->isManager()) {
                // Managers can see tasks assigned to their team members and themselves
                $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                if (!in_array($this->task->assigned_to_user_id, $teamMemberIds->toArray()) && 
                    $this->task->assigned_by_user_id !== $user->id) {
                    abort(403, 'You do not have permission to view this task.');
                }
            } else {
                // Employees can only see tasks assigned to them
                if ($this->task->assigned_to_user_id !== $user->id && 
                    $this->task->assigned_by_user_id !== $user->id) {
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
            if ($user->isManager()) {
                // Managers can update tasks assigned to their team members and themselves
                $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                if (!in_array($this->task->assigned_to_user_id, $teamMemberIds->toArray()) && 
                    $this->task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            } else {
                // Employees can only update tasks assigned to them
                if ($this->task->assigned_to_user_id !== $user->id && 
                    $this->task->assigned_by_user_id !== $user->id) {
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
        
        // Check if user can update this task
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            if ($user->isManager()) {
                // Managers can update tasks assigned to their team members and themselves
                $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                if (!in_array($this->task->assigned_to_user_id, $teamMemberIds->toArray()) && 
                    $this->task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            } else {
                // Employees can only update tasks assigned to them
                if ($this->task->assigned_to_user_id !== $user->id && 
                    $this->task->assigned_by_user_id !== $user->id) {
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

        $oldStatus = $this->task->status ? $this->task->status->name : 'No Status';
        $this->task->update(['status_id' => $statusId]);
        $this->task->load('status');
        
        $newStatus = $this->task->status->name;

        // Log the status change
        Log::createLog(auth()->id(), 'update_task_status', 
            "Changed task '{$this->task->title}' status from {$oldStatus} to {$newStatus}");

        session()->flash('success', 'Task status updated successfully.');
    }

    public function getStatusesProperty()
    {
        return TaskStatus::orderBy('name')->get();
    }

    public function addComment()
    {
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
        if ($this->commentAttachments) {
            foreach ($this->commentAttachments as $attachment) {
                $path = $attachment->store('comment-attachments');
                
                $comment->attachments()->create([
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
        $this->task->load('noteComments.user', 'noteComments.attachments');
        
        session()->flash('success', 'Comment added successfully.');
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

    public function render()
    {
        return view('livewire.task.task-details')
            ->layout('layouts.app');
    }
}

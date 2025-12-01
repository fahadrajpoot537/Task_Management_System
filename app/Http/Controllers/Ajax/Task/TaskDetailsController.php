<?php

namespace App\Http\Controllers\Ajax\Task;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Log;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskNoteComment;
use App\Services\EmailNotificationService;
use App\Services\RecurringTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskDetailsController extends Controller
{
    protected $emailService;

    public function __construct()
    {
        $this->emailService = new EmailNotificationService();
    }

    /**
     * Display the task details page
     */
    public function index($taskId)
    {
        return view('ajax.task.task-details', compact('taskId'));
    }

    /**
     * Get task details
     */
    public function getTask($taskId)
    {
        try {
            $user = auth()->user();
            
            $task = Task::with(['project', 'assignedTo', 'assignedBy', 'status', 'priority', 'category', 'attachments.uploadedBy', 'noteComments.user', 'noteComments.attachments.uploadedBy', 'assignees'])
                ->findOrFail($taskId);
                
            // Check if user can access this task
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                $isAssignee = $task->assigned_to_user_id == $user->id || 
                              $task->assignees->contains('id', $user->id);
                $isCreator = $task->assigned_by_user_id == $user->id;
                
                if ($user->isManager()) {
                    $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                    $isTeamTask = in_array($task->assigned_to_user_id, $teamMemberIds->toArray());
                    
                    if (!$isAssignee && !$isCreator && !$isTeamTask) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to view this task.'
                        ], 403);
                    }
                } else {
                    if (!$isAssignee && !$isCreator) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to view this task.'
                        ], 403);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'task' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statuses for dropdown
     */
    public function getStatuses()
    {
        $statuses = TaskStatus::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'statuses' => $statuses
        ]);
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'status_id' => 'required|exists:task_statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $task = Task::findOrFail($taskId);
            
            // Check permissions
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                $isAssignee = $task->assigned_to_user_id == $user->id || 
                              $task->assignees->contains('id', $user->id);
                $isCreator = $task->assigned_by_user_id == $user->id;
                
                if ($user->isManager()) {
                    $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                    $isTeamTask = in_array($task->assigned_to_user_id, $teamMemberIds->toArray());
                    
                    if (!$isAssignee && !$isCreator && !$isTeamTask) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to update this task.'
                        ], 403);
                    }
                } else {
                    if (!$isAssignee && !$isCreator) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to update this task.'
                        ], 403);
                    }
                }
            }

            // Check if task is already approved
            if ($task->is_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change status of an approved task.'
                ], 400);
            }

            $oldStatus = $task->status ? $task->status->name : 'No Status';
            $task->update(['status_id' => $request->status_id]);
            $task->load('status');
            
            $newStatus = $task->status->name;

            Log::createLog(auth()->id(), 'update_task_status', 
                "Changed task '{$task->title}' status from {$oldStatus} to {$newStatus}");

            // Process recurring task if status is "Complete"
            if ($newStatus === 'Complete') {
                $recurringService = new RecurringTaskService();
                $recurringService->processRecurringTask($task);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully.',
                'task' => $task->load(['status'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add note to task
     */
    public function addNote(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($taskId);
            
            $currentNotes = $task->notes ? $task->notes . "\n\n" : '';
            $newNote = "[" . now()->format('Y-m-d H:i:s') . "] " . auth()->user()->name . ": " . $request->note;
            
            $task->update([
                'notes' => $currentNotes . $newNote
            ]);

            Log::createLog(auth()->id(), 'add_task_note', "Added note to task: {$task->title}");

            return response()->json([
                'success' => true,
                'message' => 'Note added successfully.',
                'task' => $task->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add attachments to task
     */
    public function addAttachments(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'attachments.*' => 'required|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($taskId);
            
            foreach ($request->file('attachments') as $attachment) {
                $path = $attachment->store('attachments');
                
                $task->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $attachment->getClientOriginalName(),
                    'uploaded_by_user_id' => auth()->id(),
                ]);
            }

            Log::createLog(auth()->id(), 'add_task_attachment', "Added attachments to task: {$task->title}");

            return response()->json([
                'success' => true,
                'message' => 'Attachments uploaded successfully.',
                'task' => $task->load('attachments.uploadedBy')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload attachments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment($taskId, $attachmentId)
    {
        try {
            $user = auth()->user();
            $task = Task::findOrFail($taskId);
            $attachment = $task->attachments()->findOrFail($attachmentId);
            
            // Check permissions
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                if ($user->isManager()) {
                    $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                    if (!in_array($task->assigned_to_user_id, $teamMemberIds->toArray()) && 
                        $task->assigned_by_user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to delete this attachment.'
                        ], 403);
                    }
                } else {
                    if ($task->assigned_to_user_id !== $user->id && 
                        $task->assigned_by_user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to delete this attachment.'
                        ], 403);
                    }
                }
            }

            // Delete file from storage
            if (file_exists(storage_path('app/' . $attachment->file_path))) {
                unlink(storage_path('app/' . $attachment->file_path));
            }

            Log::createLog(auth()->id(), 'delete_task_attachment', "Deleted attachment from task: {$task->title}");

            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully.',
                'task' => $task->load('attachments.uploadedBy')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop recurring task
     */
    public function stopRecurringTask($taskId)
    {
        try {
            $task = Task::findOrFail($taskId);
            
            if (in_array($task->nature_of_task, ['daily', 'weekly', 'monthly', 'until_stop'])) {
                $recurringService = new RecurringTaskService();
                $recurringService->stopRecurringTask($task);
                
                Log::createLog(auth()->id(), 'stop_recurring_task', "Stopped recurring task: {$task->title}");
                
                return response()->json([
                    'success' => true,
                    'message' => 'Recurring task generation stopped successfully!',
                    'task' => $task->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'This task is not a recurring task.'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop recurring task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add comment to task
     */
    public function addComment(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $comment = TaskNoteComment::create([
                'task_id' => $taskId,
                'user_id' => auth()->id(),
                'comment' => $request->comment,
            ]);

            // Handle comment attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $path = $attachment->store('attachments');
                    
                    Attachment::create([
                        'task_id' => $taskId,
                        'comment_id' => $comment->id,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_size' => $attachment->getSize(),
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            $task = Task::findOrFail($taskId);
            Log::createLog(auth()->id(), 'add_task_comment', "Added comment to task: {$task->title}");
            
            $this->emailService->sendTaskNoteCommentNotification($task, $comment);

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully.',
                'comment' => $comment->load(['user', 'attachments.uploadedBy']),
                'task' => $task->load(['noteComments.user', 'noteComments.attachments.uploadedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete comment
     */
    public function deleteComment($taskId, $commentId)
    {
        try {
            $user = auth()->user();
            $comment = TaskNoteComment::findOrFail($commentId);
            
            // Check permissions
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                if ($comment->user_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only delete your own comments.'
                    ], 403);
                }
            }

            // Delete comment attachments
            foreach ($comment->attachments as $attachment) {
                if (file_exists(storage_path('app/' . $attachment->file_path))) {
                    unlink(storage_path('app/' . $attachment->file_path));
                }
            }

            $task = Task::findOrFail($taskId);
            Log::createLog(auth()->id(), 'delete_task_comment', "Deleted comment from task: {$task->title}");

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully.',
                'task' => $task->load('noteComments.user', 'noteComments.attachments')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve task
     */
    public function approveTask(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $task = Task::findOrFail($taskId);
            
            // Check permissions
            if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager() && $task->assigned_by_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators, managers, or task creators can review completed tasks.'
                ], 403);
            }
            
            // Check if task is completed
            if (!$task->status || $task->status->name !== 'Complete') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed tasks can be reviewed.'
                ], 400);
            }
            
            $task->update(['is_approved' => true]);
            
            Log::createLog(auth()->id(), 'task_approved', 
                "Approved completed task '{$task->title}'" . 
                ($request->comments ? " with comments: {$request->comments}" : ''));
            
            // Send email notification with review comments
            $adminName = auth()->user()->name;
            $this->emailService->sendTaskApprovedNotification($task, $request->comments, $adminName);
            
            return response()->json([
                'success' => true,
                'message' => 'Task has been approved and marked as completed. Email notification sent to assignees.',
                'task' => $task->fresh()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revisit task
     */
    public function revisitTask(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $task = Task::findOrFail($taskId);
            
            // Check permissions
            if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager() && $task->assigned_by_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators, managers, or task creators can review completed tasks.'
                ], 403);
            }
            
            // Check if task is completed
            if (!$task->status || $task->status->name !== 'Complete') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed tasks can be reviewed.'
                ], 400);
            }
            
            $needsRevisitStatus = TaskStatus::where('name', 'Needs Revisit')->first();
            
            if (!$needsRevisitStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Needs Revisit status not found. Please contact system administrator.'
                ], 404);
            }
            
            $task->update(['status_id' => $needsRevisitStatus->id]);
            
            Log::createLog(auth()->id(), 'task_revisit', 
                "Marked task '{$task->title}' for revisit" . 
                ($request->comments ? " with comments: {$request->comments}" : ''));
            
            $adminName = auth()->user()->name;
            $this->emailService->sendTaskRevisitNotification($task, $request->comments, $adminName);
            
            return response()->json([
                'success' => true,
                'message' => 'Task has been marked for revisit. Email notification sent to assignees.',
                'task' => $task->fresh()->load('status')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark task for revisit: ' . $e->getMessage()
            ], 500);
        }
    }
}


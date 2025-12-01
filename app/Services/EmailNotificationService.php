<?php

namespace App\Services;

use App\Mail\TaskAssigned;
use App\Mail\TaskAssignedToEmployee;
use App\Mail\TaskAssignedToManager;
use App\Mail\TaskAssignedToSuperAdmin;
use App\Mail\TaskCreated;
use App\Mail\TaskStatusChanged;
use App\Mail\TaskNoteCommentAdded;
use App\Mail\TaskRevisitNotification;
use App\Mail\TaskReminder;
use App\Mail\TaskUpdated;
use App\Mail\TaskApproved;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Send email notification when a task is created
     */
    public function sendTaskCreatedNotification(Task $task)
    {
        try {
            $recipients = $this->getTaskCreatedRecipients($task);
            
            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new TaskCreated($task, 'New Task Created'));
            }
            
            Log::info('Task created email sent', [
                'task_id' => $task->id,
                'recipients' => $recipients->pluck('email')->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send task created email', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification to specific assignees only (for task updates)
     */
    public function sendTaskAssignedNotificationToUsers(Task $task, array $userIds)
    {
        if (empty($userIds)) {
            return;
        }
        
        // Ensure assignees are loaded
        if (!$task->relationLoaded('assignees')) {
            $task->load('assignees');
        }
        
        // Get only the specified users
        $recipients = \App\Models\User::whereIn('id', $userIds)->get();
        $successfulRecipients = [];
        $failedRecipients = [];
        
        foreach ($recipients as $recipient) {
            try {
                // Validate email address before sending
                if (empty($recipient->email) || !filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('Invalid email address for task assigned notification', [
                        'task_id' => $task->id,
                        'recipient_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                        'recipient_name' => $recipient->name ?? 'Unknown'
                    ]);
                    $failedRecipients[] = [
                        'email' => $recipient->email,
                        'error' => 'Invalid email address format'
                    ];
                    continue;
                }
                
                // For new assignees, they are being assigned the task themselves
                // So they should receive the "Task Assigned to You" email regardless of their role
                // (Even if they're a manager or admin, if they're being assigned, they get the employee email)
                Mail::to($recipient->email)->send(new TaskAssignedToEmployee($task, 'New Task Assigned to You', $recipient));
                
                $successfulRecipients[] = $recipient->email;
            } catch (\Exception $e) {
                $failedRecipients[] = [
                    'email' => $recipient->email ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
                
                Log::error('Failed to send task assigned email to recipient', [
                    'task_id' => $task->id,
                    'recipient_id' => $recipient->id ?? 'Unknown',
                    'recipient_email' => $recipient->email ?? 'Unknown',
                    'recipient_name' => $recipient->name ?? 'Unknown',
                    'error' => $e->getMessage(),
                    'error_code' => method_exists($e, 'getCode') ? $e->getCode() : null
                ]);
            }
        }
        
        if (!empty($successfulRecipients)) {
            Log::info('Task assigned email sent to new assignees', [
                'task_id' => $task->id,
                'successful_recipients' => $successfulRecipients,
                'failed_count' => count($failedRecipients)
            ]);
        }
        
        if (!empty($failedRecipients)) {
            Log::error('Failed to send task assigned email to some new assignees', [
                'task_id' => $task->id,
                'failed_recipients' => $failedRecipients,
                'successful_count' => count($successfulRecipients)
            ]);
        }
    }

    /**
     * Send email notification when a task is assigned
     */
    public function sendTaskAssignedNotification(Task $task)
    {
        // Ensure assignees are loaded
        if (!$task->relationLoaded('assignees')) {
            $task->load('assignees');
        }
        
        $recipients = $this->getTaskAssignedRecipients($task);
        $successfulRecipients = [];
        $failedRecipients = [];
        
        foreach ($recipients as $recipient) {
            try {
                // Validate email address before sending
                if (empty($recipient->email) || !filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('Invalid email address for task assigned notification', [
                        'task_id' => $task->id,
                        'recipient_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                        'recipient_name' => $recipient->name ?? 'Unknown'
                    ]);
                    $failedRecipients[] = [
                        'email' => $recipient->email,
                        'error' => 'Invalid email address format'
                    ];
                    continue;
                }
                
                // Determine which employee was assigned (for manager notifications)
                $assignedEmployee = null;
                $assigneeIds = $task->assignees ? $task->assignees->pluck('id')->toArray() : [];
                
                if (!empty($assigneeIds)) {
                    // Check if recipient is one of the assignees
                    $assignedEmployee = $task->assignees->firstWhere('id', $recipient->id);
                    // If not, use the first assignee (for manager notifications)
                    if (!$assignedEmployee) {
                        $assignedEmployee = $task->assignees->first();
                    }
                } else {
                    // Fallback to primary assignee
                    $assignedEmployee = $task->assignedTo;
                }
                
                // Check if recipient is an assignee
                $isAssignee = in_array($recipient->id, $assigneeIds) || $recipient->id === $task->assigned_to_user_id;
                
                // Send different emails based on recipient role
                if ($recipient->role && $recipient->role->name === 'super_admin') {
                    // SuperAdmin gets manager-style notification about employee assignment
                    Mail::to($recipient->email)->send(new TaskAssignedToManager($task, 'Employee Assigned to Task', $assignedEmployee));
                } elseif ($recipient->role && $recipient->role->name === 'manager') {
                    // Manager gets notification about their employee's assignment
                    Mail::to($recipient->email)->send(new TaskAssignedToManager($task, 'Your Employee Has Been Assigned a Task', $assignedEmployee));
                } elseif ($isAssignee) {
                    // Assigned employee gets notification about their assignment
                    // Pass the recipient so the email shows the correct name
                    Mail::to($recipient->email)->send(new TaskAssignedToEmployee($task, 'New Task Assigned to You', $recipient));
                } else {
                    // Fallback for other roles (admin, etc.)
                    Mail::to($recipient->email)->send(new TaskAssigned($task, 'Task Assignment Notification'));
                }
                
                $successfulRecipients[] = $recipient->email;
            } catch (\Exception $e) {
                $failedRecipients[] = [
                    'email' => $recipient->email ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
                
                Log::error('Failed to send task assigned email to recipient', [
                    'task_id' => $task->id,
                    'recipient_id' => $recipient->id ?? 'Unknown',
                    'recipient_email' => $recipient->email ?? 'Unknown',
                    'recipient_name' => $recipient->name ?? 'Unknown',
                    'error' => $e->getMessage(),
                    'error_code' => method_exists($e, 'getCode') ? $e->getCode() : null
                ]);
            }
        }
        
        if (!empty($successfulRecipients)) {
            Log::info('Task assigned email sent to some recipients', [
                'task_id' => $task->id,
                'successful_recipients' => $successfulRecipients,
                'failed_count' => count($failedRecipients)
            ]);
        }
        
        if (!empty($failedRecipients)) {
            Log::error('Failed to send task assigned email to some recipients', [
                'task_id' => $task->id,
                'failed_recipients' => $failedRecipients,
                'successful_count' => count($successfulRecipients)
            ]);
        }
    }

    /**
     * Send email notification when task status changes
     */
    public function sendTaskStatusChangedNotification(Task $task, $oldStatus, $newStatus)
    {
        try {
            $recipients = $this->getStatusChangeRecipients($task, $newStatus);
            
            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new TaskStatusChanged($task, $oldStatus, $newStatus, 'Task Status Changed'));
            }
            
            Log::info('Task status changed email sent', [
                'task_id' => $task->id,
                'new_status' => $newStatus ? $newStatus->name : 'No Status',
                'recipients' => $recipients->pluck('email')->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send task status changed email', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification when a comment is added to task notes
     */
    public function sendTaskNoteCommentNotification($task, $comment)
    {
        try {
            $recipients = $this->getNoteCommentRecipients($task, $comment);
            
            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new TaskNoteCommentAdded($task, $comment, 'New Comment Added to Task'));
            }
            
            Log::info('Task note comment email sent', [
                'task_id' => $task->id,
                'comment_id' => $comment->id,
                'recipients' => $recipients->pluck('email')->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send task note comment email', [
                'task_id' => $task->id,
                'comment_id' => $comment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification when a task is updated
     */
    public function sendTaskUpdatedNotification(Task $task, string $changeType = 'Task Updated')
    {
        try {
            $recipients = $this->getTaskUpdatedRecipients($task);
            
            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new TaskUpdated($task, $changeType, 'Task Has Been Updated'));
            }
            
            Log::info('Task updated email sent', [
                'task_id' => $task->id,
                'change_type' => $changeType,
                'recipients' => $recipients->pluck('email')->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send task updated email', [
                'task_id' => $task->id,
                'change_type' => $changeType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get recipients for task created notifications
     */
    private function getTaskCreatedRecipients(Task $task)
    {
        $recipients = collect();
        
        // Always notify SuperAdmin
        $superAdmins = User::whereHas('role', function($query) {
            $query->where('name', 'super_admin');
        })->get();
        $recipients = $recipients->merge($superAdmins);
        
        // Notify all Admins
        $admins = User::whereHas('role', function($query) {
            $query->where('name', 'admin');
        })->get();
        $recipients = $recipients->merge($admins);
        
        // Notify assigned user if exists
        if ($task->assignedTo) {
            $recipients->push($task->assignedTo);
            
            // Notify assigned user's manager if exists
            if ($task->assignedTo->manager) {
                $recipients->push($task->assignedTo->manager);
            }
        }
        
        return $recipients->unique('id');
    }

    /**
     * Get recipients for task assigned notifications
     */
    private function getTaskAssignedRecipients(Task $task)
    {
        $recipients = collect();
        
        // Always notify SuperAdmin
        $superAdmins = User::whereHas('role', function($query) {
            $query->where('name', 'super_admin');
        })->get();
        $recipients = $recipients->merge($superAdmins);
        
        // Notify all Admins
        $admins = User::whereHas('role', function($query) {
            $query->where('name', 'admin');
        })->get();
        $recipients = $recipients->merge($admins);
        
        // Notify all assignees from the assignees relationship
        if ($task->assignees && $task->assignees->count() > 0) {
            foreach ($task->assignees as $assignee) {
                $recipients->push($assignee);
                
                // Notify assignee's manager if exists
                if ($assignee->manager) {
                    $recipients->push($assignee->manager);
                }
            }
        }
        
        // Also notify primary assigned user if different from assignees
        if ($task->assignedTo && !$recipients->contains('id', $task->assignedTo->id)) {
            $recipients->push($task->assignedTo);
            
            // Notify assigned user's manager if exists
            if ($task->assignedTo->manager) {
                $recipients->push($task->assignedTo->manager);
            }
        }
        
        return $recipients->unique('id');
    }

    /**
     * Get recipients for status change notifications
     */
    private function getStatusChangeRecipients(Task $task, $newStatus)
    {
        $recipients = collect();
        
        // Always notify SuperAdmin
        $superAdmins = User::whereHas('role', function($query) {
            $query->where('name', 'super_admin');
        })->get();
        $recipients = $recipients->merge($superAdmins);
        
        // Notify all Admins
        $admins = User::whereHas('role', function($query) {
            $query->where('name', 'admin');
        })->get();
        $recipients = $recipients->merge($admins);
        
        // Notify assigned user if exists
        if ($task->assignedTo) {
            $recipients->push($task->assignedTo);
            
            // Notify assigned user's manager if exists
            if ($task->assignedTo->manager) {
                $recipients->push($task->assignedTo->manager);
            }
        }
        
        return $recipients->unique('id');
    }

    /**
     * Get recipients for note comment notifications
     */
    private function getNoteCommentRecipients($task, $comment)
    {
        $recipients = collect();
        
        // Always notify SuperAdmin
        $superAdmins = User::whereHas('role', function($query) {
            $query->where('name', 'super_admin');
        })->get();
        $recipients = $recipients->merge($superAdmins);
        
        // Notify all Admins
        $admins = User::whereHas('role', function($query) {
            $query->where('name', 'admin');
        })->get();
        $recipients = $recipients->merge($admins);
        
        // Notify assigned user if exists
        if ($task->assignedTo) {
            $recipients->push($task->assignedTo);
            
            // Notify assigned user's manager if exists
            if ($task->assignedTo->manager) {
                $recipients->push($task->assignedTo->manager);
            }
        }
        
        // Don't notify the person who made the comment
        $recipients = $recipients->reject(function($user) use ($comment) {
            return $user->id === $comment->user_id;
        });
        
        return $recipients->unique('id');
    }

    /**
     * Get recipients for task updated notifications
     */
    private function getTaskUpdatedRecipients(Task $task)
    {
        $recipients = collect();
        
        // Always notify SuperAdmin
        $superAdmins = User::whereHas('role', function($query) {
            $query->where('name', 'super_admin');
        })->get();
        $recipients = $recipients->merge($superAdmins);
        
        // Notify all Admins
        $admins = User::whereHas('role', function($query) {
            $query->where('name', 'admin');
        })->get();
        $recipients = $recipients->merge($admins);
        
        // Notify assigned user if exists
        if ($task->assignedTo) {
            $recipients->push($task->assignedTo);
            
            // Notify assigned user's manager if exists
            if ($task->assignedTo->manager) {
                $recipients->push($task->assignedTo->manager);
            }
        }
        
        return $recipients->unique('id');
    }

    /**
     * Send email notification when a task is marked for revisit
     */
    public function sendTaskRevisitNotification(Task $task, $adminComments = null, $adminName = null)
    {
        try {
            $recipients = $this->getTaskRevisitRecipients($task);
            
            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new TaskRevisitNotification($task, $adminComments, $adminName));
            }
            
            Log::info('Task revisit email sent', [
                'task_id' => $task->id,
                'recipients' => $recipients->pluck('email')->toArray(),
                'admin_comments' => $adminComments
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send task revisit email', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get recipients for task revisit notifications
     */
    private function getTaskRevisitRecipients(Task $task)
    {
        $recipients = collect();
        
        // Notify all assignees of the task
        foreach ($task->assignees as $assignee) {
            $recipients->push($assignee);
        }
        
        // Also notify the legacy assigned user if different from assignees
        if ($task->assignedTo && !$recipients->contains('id', $task->assignedTo->id)) {
            $recipients->push($task->assignedTo);
        }
        
        return $recipients->unique('id');
    }

    /**
     * Send task reminder email to assignees
     */
    public function sendTaskReminderNotification(Task $task)
    {
        try {
            $recipients = $this->getTaskReminderRecipients($task);
            
            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new TaskReminder($task, 'Task Reminder: Complete by Due Date', 'Task Reminder'));
            }
            
            Log::info('Task reminder email sent', [
                'task_id' => $task->id,
                'recipients' => $recipients->pluck('email')->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send task reminder email', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get recipients for task reminder notifications
     */
    private function getTaskReminderRecipients(Task $task)
    {
        $recipients = collect();
        
        // Notify all assignees of the task
        foreach ($task->assignees as $assignee) {
            $recipients->push($assignee);
        }
        
        // Also notify the legacy assigned user if different from assignees
        if ($task->assignedTo && !$recipients->contains('id', $task->assignedTo->id)) {
            $recipients->push($task->assignedTo);
        }
        
        return $recipients->unique('id');
    }

    /**
     * Send email notification when a task is approved
     */
    public function sendTaskApprovedNotification(Task $task, $adminComments = null, $adminName = null)
    {
        try {
            $recipients = $this->getTaskApprovedRecipients($task);
            
            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new TaskApproved($task, $adminComments, $adminName));
            }
            
            Log::info('Task approved email sent', [
                'task_id' => $task->id,
                'recipients' => $recipients->pluck('email')->toArray(),
                'admin_comments' => $adminComments
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send task approved email', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get recipients for task approved notifications
     */
    private function getTaskApprovedRecipients(Task $task)
    {
        $recipients = collect();
        
        // Notify all assignees of the task
        foreach ($task->assignees as $assignee) {
            $recipients->push($assignee);
        }
        
        // Also notify the legacy assigned user if different from assignees
        if ($task->assignedTo && !$recipients->contains('id', $task->assignedTo->id)) {
            $recipients->push($task->assignedTo);
        }
        
        return $recipients->unique('id');
    }
}

<?php

namespace App\Services;

use App\Mail\TaskAssigned;
use App\Mail\TaskAssignedToEmployee;
use App\Mail\TaskAssignedToManager;
use App\Mail\TaskAssignedToSuperAdmin;
use App\Mail\TaskCreated;
use App\Mail\TaskStatusChanged;
use App\Mail\TaskNoteCommentAdded;
use App\Mail\TaskUpdated;
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
     * Send email notification when a task is assigned
     */
    public function sendTaskAssignedNotification(Task $task)
    {
        try {
            $recipients = $this->getTaskAssignedRecipients($task);
            
            foreach ($recipients as $recipient) {
                // Send different emails based on recipient role
                if ($recipient->role && $recipient->role->name === 'super_admin') {
                    // SuperAdmin gets manager-style notification about employee assignment
                    Mail::to($recipient->email)->send(new TaskAssignedToManager($task, 'Employee Assigned to Task'));
                } elseif ($recipient->role && $recipient->role->name === 'manager') {
                    // Manager gets notification about their employee's assignment
                    Mail::to($recipient->email)->send(new TaskAssignedToManager($task, 'Your Employee Has Been Assigned a Task'));
                } elseif ($recipient->id === $task->assigned_to_user_id) {
                    // Assigned employee gets notification about their assignment
                    Mail::to($recipient->email)->send(new TaskAssignedToEmployee($task, 'New Task Assigned to You'));
                } else {
                    // Fallback for other roles (admin, etc.)
                    Mail::to($recipient->email)->send(new TaskAssigned($task, 'Task Assignment Notification'));
                }
            }
            
            Log::info('Task assigned email sent', [
                'task_id' => $task->id,
                'recipients' => $recipients->pluck('email')->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send task assigned email', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
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
     * Configure mail settings dynamically
     */
    public function configureMailSettings()
    {
        // Laravel automatically reads from .env file, so we don't need to configure manually
        // The mail settings are already configured in .env file:
        // MAIL_MAILER=smtp
        // MAIL_HOST=smtp.ionos.co.uk
        // MAIL_PORT=587
        // MAIL_USERNAME=task@tms.adamsonstrading.co.uk
        // MAIL_PASSWORD=Adamsons@321
        // MAIL_FROM_ADDRESS=task@tms.adamsonstrading.co.uk
        // MAIL_FROM_NAME=Laravel
        
        // No additional configuration needed as Laravel handles this automatically
    }
}

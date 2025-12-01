<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedToManager extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $subject;
    public $employee;

    /**
     * Create a new message instance.
     */
    public function __construct(Task $task, string $subject = 'Your Employee Has Been Assigned a Task', $employee = null)
    {
        // Load relationships to ensure they're available in the email template
        $this->task = $task->load(['priority', 'status', 'project', 'assignedTo', 'assignedBy', 'assignees']);
        $this->subject = $subject;
        $this->employee = $employee;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Get fresh instance and load relationships to ensure they're available
        $task = $this->task->fresh(['priority', 'status', 'project', 'assignedTo', 'assignedBy', 'assignees']);
        
        // Use the employee if provided, otherwise fall back to primary assignee
        // For manager emails, we want to show which employee was assigned
        $assignedUser = $this->employee ?? $task->assignedTo;
        
        return new Content(
            view: 'emails.task-assigned-to-manager',
            with: [
                'task' => $task,
                'assignedUser' => $assignedUser,
                'assignedBy' => $task->assignedBy,
                'project' => $task->project,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

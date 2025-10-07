<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $oldStatus;
    public $newStatus;
    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct(Task $task, $oldStatus, $newStatus, string $subject = 'Task Status Changed')
    {
        $this->task = $task;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->subject = $subject;
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
        return new Content(
            view: 'emails.task-status-changed',
            with: [
                'task' => $this->task,
                'assignedUser' => $this->task->assignedTo,
                'assignedBy' => $this->task->assignedBy,
                'project' => $this->task->project,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
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

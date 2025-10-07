<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedToEmployee extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct(Task $task, string $subject = 'New Task Assigned to You')
    {
        $this->task = $task;
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
            view: 'emails.task-assigned-to-employee',
            with: [
                'task' => $this->task,
                'assignedUser' => $this->task->assignedTo,
                'assignedBy' => $this->task->assignedBy,
                'project' => $this->task->project,
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

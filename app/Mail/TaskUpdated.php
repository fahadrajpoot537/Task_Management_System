<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $subject;
    public $changeType;

    /**
     * Create a new message instance.
     */
    public function __construct(Task $task, string $changeType = 'Task Updated', string $subject = 'Task Has Been Updated')
    {
        $this->task = $task;
        $this->subject = $subject;
        $this->changeType = $changeType;
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
            view: 'emails.task-updated',
            with: [
                'task' => $this->task,
                'assignedUser' => $this->task->assignedTo,
                'assignedBy' => $this->task->assignedBy,
                'project' => $this->task->project,
                'changeType' => $this->changeType,
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

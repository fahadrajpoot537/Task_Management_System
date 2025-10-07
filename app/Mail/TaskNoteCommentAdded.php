<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\TaskNoteComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskNoteCommentAdded extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $comment;
    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct(Task $task, TaskNoteComment $comment, string $subject = 'New Comment Added to Task')
    {
        $this->task = $task;
        $this->comment = $comment->load('user'); // Ensure user relationship is loaded
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
            view: 'emails.task-note-comment',
            with: [
                'task' => $this->task,
                'comment' => $this->comment,
                'commenter' => $this->comment->user,
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

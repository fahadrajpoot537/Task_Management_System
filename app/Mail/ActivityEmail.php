<?php

namespace App\Mail;

use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ActivityEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $activity;
    public $subject;
    public $body;
    public $attachmentPaths; // Renamed to avoid conflict with Laravel's internal $attachments property
    public $fromEmail;
    public $fromName;
    public $replyToEmail;
    public $replyToName;
    public $messageId;

    /**
     * Create a new message instance.
     */
    public function __construct(Activity $activity, string $subject, string $body, array $attachmentPaths = [], string $fromEmail = null, string $fromName = null, string $replyToEmail = null, string $replyToName = null, string $messageId = null)
    {
        $this->activity = $activity;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachmentPaths = $attachmentPaths;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->replyToEmail = $replyToEmail;
        $this->replyToName = $replyToName;
        $this->messageId = $messageId;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: $this->subject,
        );
        
        // Set sender email if provided (must be authorized by SMTP server)
        if ($this->fromEmail) {
            $envelope->from($this->fromEmail, $this->fromName);
        }
        
        // Set reply-to email (actual sender's email)
        if ($this->replyToEmail) {
            $envelope->replyTo($this->replyToEmail, $this->replyToName);
        }
        
        return $envelope;
    }


    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.activity-email',
            with: [
                'activity' => $this->activity,
                'body' => $this->body,
                'lead' => $this->activity->lead,
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
        $mailAttachments = [];
        
        // Ensure attachmentPaths property is an array
        if (empty($this->attachmentPaths) || !is_array($this->attachmentPaths)) {
            return $mailAttachments;
        }
        
        foreach ($this->attachmentPaths as $index => $filePath) {
            // Skip if not a string
            if (!is_string($filePath)) {
                continue;
            }
            
            // Trim and validate file path
            $filePath = trim($filePath);
            if (empty($filePath)) {
                continue;
            }
            
            // Ensure file exists and is readable
            if (!file_exists($filePath) || !is_file($filePath) || !is_readable($filePath)) {
                Log::warning('Attachment file not accessible', [
                    'file_path' => $filePath,
                    'exists' => file_exists($filePath),
                    'is_file' => is_file($filePath),
                    'readable' => is_readable($filePath)
                ]);
                continue;
            }
            
            try {
                $attachment = Attachment::fromPath($filePath);
                if ($attachment instanceof Attachment) {
                    $mailAttachments[] = $attachment;
                }
            } catch (\Exception $e) {
                // Log error but continue with other attachments
                Log::warning('Failed to create attachment object', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        return $mailAttachments;
    }
}


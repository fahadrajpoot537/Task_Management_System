<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonthlySalarySummary extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Salary Summary - ' . ($this->data['month'] ?? ''))
            ->view('emails.monthly-salary-summary', ['data' => $this->data]);
    }
}



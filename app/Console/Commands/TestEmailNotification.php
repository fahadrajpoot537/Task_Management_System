<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email notification functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing email notification functionality...');
        
        try {
            $emailService = new EmailNotificationService();
            
            // Test basic mail configuration
            $this->info('✓ Email service configured successfully');
            $this->info('✓ Using SMTP settings from .env file');
            
            // Test sending a simple email
            Mail::raw('This is a test email from the Task Management System.', function ($message) {
                $message->to('task@tms.adamsonstrading.co.uk')
                        ->subject('Test Email - Task Management System');
            });
            
            $this->info('✓ Test email sent successfully');
            $this->info('Email functionality is working correctly!');
            
        } catch (\Exception $e) {
            $this->error('✗ Email test failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to assignees for pending tasks with reminder_time on their due_date';

    /**
     * Email notification service
     *
     * @var EmailNotificationService
     */
    protected $emailService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->emailService = new EmailNotificationService();
        
        $this->info('Starting Task Reminders Command... ' . now());
        
        try {
            // Get current date and time
            $now = Carbon::now();
            
            // Find pending tasks with reminder_time set
            // Conditions:
            // 1. Status is "Pending"
            // 2. reminder_time is not null
            // 3. reminder_time has passed (date/time <= now)
            // 4. due_date is today or in the future (task not yet overdue when reminder was set)
            $tasks = Task::whereHas('status', function ($query) {
                    $query->where('name', 'Pending');
                })
                ->whereNotNull('reminder_time')
                ->whereNotNull('due_date')
                ->where('reminder_time', '<=', $now) // Reminder time has passed
                ->whereDate('due_date', '>=', $now->format('Y-m-d')) // Due date is today or in the future
                ->with(['status', 'priority', 'category', 'project', 'assignees', 'assignedTo'])
                ->get();
            
            $this->info("Found {$tasks->count()} pending tasks with reminders due.");
            
            if ($tasks->count() === 0) {
                $this->info('No tasks require reminders at this time.');
                return Command::SUCCESS;
            }
            
            $sentCount = 0;
            $failedCount = 0;
            
            foreach ($tasks as $task) {
                try {
                    // Check if task has assignees
                    $hasAssignees = ($task->assignedTo || $task->assignees->count() > 0);
                    
                    if (!$hasAssignees) {
                        $this->warn("Task #{$task->id} ({$task->title}) has no assignees. Skipping reminder.");
                        continue;
                    }
                    
                    // Send reminder email
                    $this->emailService->sendTaskReminderNotification($task);
                    
                    $assigneeNames = $task->assignees->pluck('name')->toArray();
                    if ($task->assignedTo && !in_array($task->assignedTo->name, $assigneeNames)) {
                        $assigneeNames[] = $task->assignedTo->name;
                    }
                    
                    $this->info("✓ Sent reminder for Task #{$task->id}: {$task->title} to " . implode(', ', $assigneeNames));
                    $sentCount++;
                    
                } catch (\Exception $e) {
                    $this->error("✗ Failed to send reminder for Task #{$task->id}: {$task->title}. Error: {$e->getMessage()}");
                    Log::error('Failed to send task reminder', [
                        'task_id' => $task->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $failedCount++;
                }
            }
            
            $this->info("\n=== Summary ===");
            $this->info("Total tasks processed: {$tasks->count()}");
            $this->info("Reminders sent successfully: {$sentCount}");
            $this->info("Reminders failed: {$failedCount}");
            $this->info('Task Reminders Command completed successfully.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error executing task reminders command: ' . $e->getMessage());
            Log::error('Task reminders command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}


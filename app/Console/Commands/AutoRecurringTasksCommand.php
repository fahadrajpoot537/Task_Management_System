<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Log;
use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoRecurringTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:auto-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically handle recurring tasks based on their nature and schedule';

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
        // Initialize email service
        $this->emailService = new EmailNotificationService();
        
        $this->info('Starting Auto Recurring Tasks Command... '.now());
        
        // Get all active recurring tasks
        $activeRecurringTasks = Task::where('is_recurring_active', 1)
            ->whereIn('nature_of_task', ['until_stop', 'weekly', 'monthly'])
            ->get();

        $this->info("Found {$activeRecurringTasks->count()} active recurring tasks to process.");

        $processedCount = 0;
        $clonedCount = 0;
        $emailNotificationsSent = 0;

        foreach ($activeRecurringTasks as $task) {
            if ($this->shouldCloneTask($task)) {
                $this->cloneTask($task);
                $clonedCount++;
                // Each cloned task sends 1-2 emails (created + assigned if assigned)
                $emailNotificationsSent += ($task->assignedTo ? 2 : 1);
            }
            $processedCount++;
        }

        $this->info("Processed {$processedCount} tasks, cloned {$clonedCount} new tasks.");
        $this->info("Sent {$emailNotificationsSent} email notifications.");
        $this->info('Auto Recurring Tasks Command completed successfully.');
        
        return Command::SUCCESS;
    }

    /**
     * Determine if a task should be cloned based on its nature and timing
     */
    private function shouldCloneTask(Task $task): bool
    {
        $now = Carbon::now();
        $createdAt = Carbon::parse($task->created_at);
        
        switch ($task->nature_of_task) {
            case 'until_stop':
                // Check if 1 day has passed since creation
                return $now->diffInDays($createdAt) >= 1;
                
            case 'weekly':
                // Check if 1 week has passed since creation
                return $now->diffInWeeks($createdAt) >= 1;
                
            case 'monthly':
                // Check if 1 month has passed since creation
                return $now->diffInMonths($createdAt) >= 1;
                
            default:
                return false;
        }
    }

    /**
     * Clone a task and set up the parent-child relationship
     */
    private function cloneTask(Task $originalTask): void
    {
        DB::transaction(function () use ($originalTask) {
            // Deactivate the original task
            $originalTask->update(['is_recurring_active' => 0]);
            
            // Create the cloned task
            $clonedTask = Task::create([
                'project_id' => $originalTask->project_id,
                'title' => $originalTask->title,
                'description' => $originalTask->description,
                'priority_id' => $originalTask->priority_id,
                'category_id' => $originalTask->category_id,
                'status_id' => $originalTask->status_id,
                'duration' => $originalTask->duration,
                'estimated_hours' => $originalTask->estimated_hours,
                'actual_hours' => null, // Reset actual hours for new task
                'due_date' => $this->calculateNewDueDate($originalTask),
                'assigned_to_user_id' => $originalTask->assigned_to_user_id,
                'assigned_by_user_id' => $originalTask->assigned_by_user_id,
                'nature_of_task' => $originalTask->nature_of_task,
                'is_recurring' => true,
                'is_recurring_active' => 1,
                'parent_task_id' => $originalTask->id,
                'next_recurrence_date' => $this->calculateNextRecurrenceDate($originalTask),
                'notes' => $originalTask->notes,
                'started_at' => null, // Reset started_at for new task
                'completed_at' => null, // Reset completed_at for new task
            ]);

            // Copy attachments if any
            if ($originalTask->attachments->count() > 0) {
                foreach ($originalTask->attachments as $attachment) {
                    $clonedTask->attachments()->create([
                        'file_path' => $attachment->file_path,
                        'file_name' => $attachment->file_name,
                        'file_size' => $attachment->file_size,
                        'uploaded_by_user_id' => $attachment->uploaded_by_user_id,
                    ]);
                }
            }

            // Log the cloning action
            Log::createLog(
                $originalTask->assigned_by_user_id ?? 1, // Use assigned_by_user_id or default to admin
                'clone_recurring_task',
                "Cloned recurring task '{$originalTask->title}' (ID: {$originalTask->id}) -> New task ID: {$clonedTask->id}"
            );

            // Send email notifications for the cloned task
            try {
                $this->emailService->sendTaskCreatedNotification($clonedTask);
                if ($clonedTask->assignedTo) {
                    $this->emailService->sendTaskAssignedNotification($clonedTask);
                }
                
                // Log email notification
                $this->line("📧 Email notifications sent for cloned task: {$clonedTask->title}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to send email notifications for task: {$clonedTask->title}. Error: {$e->getMessage()}");
            }

            $this->line("✓ Cloned task: {$originalTask->title} (ID: {$originalTask->id} -> {$clonedTask->id})");
        });
    }

    /**
     * Calculate the new due date for the cloned task
     */
    private function calculateNewDueDate(Task $originalTask): ?Carbon
    {
        if (!$originalTask->due_date) {
            return null;
        }

        $originalDueDate = Carbon::parse($originalTask->due_date);
        
        switch ($originalTask->nature_of_task) {
            case 'until_stop':
                return $originalDueDate->addDay();
            case 'weekly':
                return $originalDueDate->addWeek();
            case 'monthly':
                return $originalDueDate->addMonth();
            default:
                return $originalDueDate;
        }
    }

    /**
     * Calculate the next recurrence date for the cloned task
     */
    private function calculateNextRecurrenceDate(Task $originalTask): Carbon
    {
        $now = Carbon::now();
        
        switch ($originalTask->nature_of_task) {
            case 'until_stop':
                return $now->addDay();
            case 'weekly':
                return $now->addWeek();
            case 'monthly':
                return $now->addMonth();
            default:
                return $now->addDay();
        }
    }
}
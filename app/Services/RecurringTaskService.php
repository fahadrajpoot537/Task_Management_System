<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\EmailNotificationService;
use Carbon\Carbon;

class RecurringTaskService
{
    protected $emailService;

    public function __construct()
    {
        $this->emailService = new EmailNotificationService();
    }

    /**
     * Process recurring tasks when a task is completed
     */
    public function processRecurringTask(Task $task)
    {
        // Only process if task is recurring and has been completed
        if (!$task->canGenerateNextOccurrence()) {
            return;
        }

        // Create next occurrence
        $this->createNextOccurrence($task);
    }

    /**
     * Create next occurrence of the task
     */
    private function createNextOccurrence(Task $originalTask): Task
    {
        // Get Pending status
        $pendingStatus = TaskStatus::where('name', 'Pending')->first();
        
        // Calculate next due date (next day for recurring tasks)
        $nextDueDate = Carbon::tomorrow();
        
        // Create new task using replicate method
        $newTask = $originalTask->replicate();
        $newTask->status_id = $pendingStatus ? $pendingStatus->id : null;
        $newTask->due_date = $nextDueDate;
        $newTask->parent_task_id = $originalTask->parent_task_id ?: $originalTask->id;
        $newTask->started_at = null;
        $newTask->completed_at = null;
        $newTask->actual_hours = null;
        $newTask->is_recurring_active = true; // Ensure new task can also recur
        $newTask->save();

        // Send email notifications for the new task
        $this->emailService->sendTaskCreatedNotification($newTask);
        if ($newTask->assignedTo) {
            $this->emailService->sendTaskAssignedNotification($newTask);
        }

        return $newTask;
    }

    /**
     * Stop recurring task generation
     */
    public function stopRecurringTask(Task $task): void
    {
        // Check if task is a recurring task (daily, weekly, monthly, until_stop)
        if (in_array($task->nature_of_task, ['daily', 'weekly', 'monthly', 'until_stop'])) {
            $task->stopRecurring();
            
            // Also stop all child tasks (clones) if they exist
            $childTasks = Task::where('parent_task_id', $task->id)
                ->where('is_recurring_active', 1)
                ->get();
            
            foreach ($childTasks as $childTask) {
                $childTask->update(['is_recurring_active' => false]);
            }
        }
    }

    /**
     * Check if task should generate next occurrence
     */
    public function shouldGenerateNextOccurrence(Task $task): bool
    {
        return $task->canGenerateNextOccurrence();
    }
}

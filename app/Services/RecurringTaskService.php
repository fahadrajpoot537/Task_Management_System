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
        $this->emailService->configureMailSettings();
    }

    /**
     * Process recurring tasks when a task is submitted for approval
     */
    public function processRecurringTask(Task $task)
    {
        // Only process if task is recurring and has been submitted for approval
        if (!$task->is_recurring || !$this->isSubmittedForApproval($task)) {
            return;
        }

        // Calculate next occurrence date
        $nextDate = $this->calculateNextOccurrence($task);
        
        if (!$nextDate) {
            return; // No more occurrences needed
        }

        // Create next occurrence
        $this->createNextOccurrence($task, $nextDate);
    }

    /**
     * Check if task status is "Submit for Approval"
     */
    private function isSubmittedForApproval(Task $task): bool
    {
        $submitForApprovalStatus = TaskStatus::where('name', 'Submit for Approval')->first();
        return $submitForApprovalStatus && $task->status_id === $submitForApprovalStatus->id;
    }

    /**
     * Calculate next occurrence date based on nature of task
     */
    private function calculateNextOccurrence(Task $task): ?Carbon
    {
        $today = Carbon::today();
        
        switch ($task->nature_of_task) {
            case 'daily':
                return $this->getNextWorkingDay($today);
                
            case 'weekly':
                return $this->getNextWeekday($today);
                
            case 'monthly':
                return $this->getNextMonthDay($today);
                
            default:
                return null;
        }
    }

    /**
     * Get next working day (Monday to Friday)
     */
    private function getNextWorkingDay(Carbon $date): Carbon
    {
        $nextDay = $date->copy()->addDay();
        
        // Skip weekends
        while ($nextDay->isWeekend()) {
            $nextDay->addDay();
        }
        
        return $nextDay;
    }

    /**
     * Get next weekday (Monday to Friday)
     */
    private function getNextWeekday(Carbon $date): Carbon
    {
        $nextWeek = $date->copy()->addWeek();
        
        // Ensure it's a weekday
        while ($nextWeek->isWeekend()) {
            $nextWeek->addDay();
        }
        
        return $nextWeek;
    }

    /**
     * Get next month day (same day next month, skip weekends)
     */
    private function getNextMonthDay(Carbon $date): Carbon
    {
        $nextMonth = $date->copy()->addMonth();
        
        // Skip weekends
        while ($nextMonth->isWeekend()) {
            $nextMonth->addDay();
        }
        
        return $nextMonth;
    }

    /**
     * Create next occurrence of the task
     */
    private function createNextOccurrence(Task $originalTask, Carbon $nextDate): Task
    {
        // Get default status
        $defaultStatus = TaskStatus::where('is_default', true)->first();
        
        // Create new task
        $newTask = Task::create([
            'title' => $originalTask->title,
            'description' => $originalTask->description,
            'project_id' => $originalTask->project_id,
            'assigned_to_user_id' => $originalTask->assigned_to_user_id,
            'priority_id' => $originalTask->priority_id,
            'category_id' => $originalTask->category_id,
            'status_id' => $defaultStatus ? $defaultStatus->id : null,
            'due_date' => $nextDate->format('Y-m-d'),
            'estimated_hours' => $originalTask->estimated_hours,
            'notes' => $originalTask->notes,
            'nature_of_task' => $originalTask->nature_of_task,
            'is_recurring' => $originalTask->is_recurring,
            'parent_task_id' => $originalTask->parent_task_id ?: $originalTask->id,
            'next_recurrence_date' => $this->calculateNextOccurrence($originalTask),
            'assigned_by_user_id' => $originalTask->assigned_by_user_id,
        ]);

        // Send email notifications for the new task
        $this->emailService->sendTaskCreatedNotification($newTask);
        if ($newTask->assignedTo) {
            $this->emailService->sendTaskAssignedNotification($newTask);
        }

        return $newTask;
    }

    /**
     * Process all pending recurring tasks (for scheduled command)
     */
    public function processPendingRecurringTasks()
    {
        $today = Carbon::today();
        
        $pendingTasks = Task::where('is_recurring', true)
            ->where('next_recurrence_date', '<=', $today)
            ->whereNotNull('next_recurrence_date')
            ->get();

        foreach ($pendingTasks as $task) {
            $this->createNextOccurrence($task, $task->next_recurrence_date);
            
            // Update the original task's next recurrence date
            $nextDate = $this->calculateNextOccurrence($task);
            $task->update(['next_recurrence_date' => $nextDate]);
        }
    }
}

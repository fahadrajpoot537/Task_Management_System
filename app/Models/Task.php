<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'priority_id',
        'category_id',
        'status_id',
        'duration',
        'estimated_hours',
        'actual_hours',
        'due_date',
        'reminder_time',
        'assigned_to_user_id',
        'nature_of_task',
        'is_recurring',
        'is_recurring_active',
        'parent_task_id',
        'next_recurrence_date',
        'assigned_by_user_id',
        'notes',
        'started_at',
        'completed_at',
        'is_approved',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'reminder_time' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_recurring' => 'boolean',
        'is_recurring_active' => 'boolean',
        'next_recurrence_date' => 'datetime',
        'is_approved' => 'boolean',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that the task is assigned to (legacy single assignee).
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get all users assigned to this task.
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignments', 'task_id', 'user_id')
                    ->withPivot(['assigned_by_user_id', 'assigned_at'])
                    ->withTimestamps();
    }

    /**
     * Get the user that assigned the task.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * Get the status that owns the task.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class);
    }

    /**
     * Get the priority that owns the task.
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(TaskPriority::class);
    }

    /**
     * Get the category that owns the task.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class);
    }

    /**
     * Get the attachments for the task.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Get the note comments for the task.
     */
    public function noteComments(): HasMany
    {
        return $this->hasMany(TaskNoteComment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if task is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && (!$this->status || $this->status->name !== 'Complete');
    }


    /**
     * Get time tracking status.
     */
    public function getTimeTrackingStatusAttribute(): string
    {
        if ($this->status && $this->status->name === 'Complete' && $this->completed_at) {
            return 'completed';
        } elseif ($this->status && $this->status->name === 'In Progress' && $this->started_at) {
            return 'in_progress';
        } elseif ($this->status && $this->status->name === 'In Progress' && !$this->started_at) {
            return 'ready_to_start';
        } else {
            return 'not_started';
        }
    }

    /**
     * Get estimated vs actual hours comparison.
     */
    public function getHoursComparisonAttribute(): array
    {
        $estimated = $this->estimated_hours ?? 0;
        $actual = $this->actual_hours ?? 0;
        
        return [
            'estimated' => $estimated,
            'actual' => $actual,
            'difference' => $actual - $estimated,
            'percentage' => $estimated > 0 ? round(($actual / $estimated) * 100, 1) : 0,
        ];
    }

    /**
     * Get task delay information.
     */
    public function getDelayInfoAttribute(): array
    {
        if (!$this->due_date || !$this->completed_at) {
            return [
                'is_delayed' => false,
                'is_early' => false,
                'delay_days' => 0,
                'early_days' => 0,
                'status' => 'no_completion_date'
            ];
        }

        $dueDate = $this->due_date;
        $completedDate = $this->completed_at->toDateString();
        $dueDateStr = $dueDate->toDateString();

        if ($completedDate > $dueDateStr) {
            // Task was delayed
            $delayDays = $dueDate->diffInDays($this->completed_at, false);
            return [
                'is_delayed' => true,
                'is_early' => false,
                'delay_days' => $delayDays,
                'early_days' => 0,
                'status' => 'delayed'
            ];
        } elseif ($completedDate < $dueDateStr) {
            // Task was completed early
            $earlyDays = $this->completed_at->diffInDays($dueDate, false);
            return [
                'is_delayed' => false,
                'is_early' => true,
                'delay_days' => 0,
                'early_days' => $earlyDays,
                'status' => 'early'
            ];
        } else {
            // Task completed on time
            return [
                'is_delayed' => false,
                'is_early' => false,
                'delay_days' => 0,
                'early_days' => 0,
                'status' => 'on_time'
            ];
        }
    }

    /**
     * Get delay badge class.
     */
    public function getDelayBadgeClassAttribute(): string
    {
        $delayInfo = $this->delay_info;
        
        return match($delayInfo['status']) {
            'delayed' => 'bg-danger',
            'early' => 'bg-success',
            'on_time' => 'bg-primary',
            default => 'bg-secondary'
        };
    }

    /**
     * Get delay badge text.
     */
    public function getDelayBadgeTextAttribute(): string
    {
        $delayInfo = $this->delay_info;
        
        return match($delayInfo['status']) {
            'delayed' => "Delayed by {$delayInfo['delay_days']} days",
            'early' => "Early by {$delayInfo['early_days']} days",
            'on_time' => 'On Time',
            default => 'No Due Date'
        };
    }

    /**
     * Get the parent task for recurring tasks.
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Get the child tasks for recurring tasks.
     */
    public function childTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Check if task is recurring.
     */
    public function isRecurring(): bool
    {
        return in_array($this->nature_of_task, ['weekly', 'monthly', 'until_stop']) && $this->is_recurring_active;
    }

    /**
     * Get nature of task display name.
     */
    public function getNatureOfTaskDisplayAttribute(): string
    {
        return match($this->nature_of_task) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'until_stop' => 'Until Stopped',
            default => 'Daily'
        };
    }

    /**
     * Check if task can generate next occurrence.
     */
    public function canGenerateNextOccurrence(): bool
    {
        return in_array($this->nature_of_task, ['weekly', 'monthly', 'until_stop']) && 
               $this->is_recurring_active && 
               $this->status && 
               $this->status->name === 'Complete';
    }

    /**
     * Stop recurring task generation.
     */
    public function stopRecurring(): void
    {
        $this->update(['is_recurring_active' => false]);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status ? "bg-{$this->status->color}" : 'bg-secondary';
    }

    /**
     * Get priority badge class.
     */
    public function getPriorityBadgeClassAttribute(): string
    {
        return $this->priority ? "bg-{$this->priority->color}" : 'bg-secondary';
    }

    /**
     * Check if a user is assigned to this task.
     */
    public function isAssignedTo(User $user): bool
    {
        return $this->assignees()->where('user_id', $user->id)->exists();
    }

    /**
     * Get assignee names as a comma-separated string.
     */
    public function getAssigneeNamesAttribute(): string
    {
        return $this->assignees->pluck('name')->join(', ');
    }

    /**
     * Get assignee count.
     */
    public function getAssigneeCountAttribute(): int
    {
        return $this->assignees()->count();
    }

    /**
     * Sync task assignees.
     */
    public function syncAssignees(array $userIds, int $assignedByUserId): void
    {
        $assignments = [];
        foreach ($userIds as $userId) {
            $assignments[$userId] = [
                'assigned_by_user_id' => $assignedByUserId,
                'assigned_at' => now(),
            ];
        }
        
        $this->assignees()->sync($assignments);
    }
}

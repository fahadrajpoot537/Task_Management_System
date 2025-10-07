<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'created_by_user_id',
    ];

    /**
     * Get the user that created the project.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the completed tasks count.
     */
    public function getCompletedTasksCountAttribute(): int
    {
        return $this->tasks()->whereHas('status', function ($query) {
            $query->where('name', 'Complete');
        })->count();
    }

    /**
     * Get the total tasks count.
     */
    public function getTotalTasksCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        $total = $this->total_tasks_count;
        if ($total === 0) {
            return 0;
        }
        
        return round(($this->completed_tasks_count / $total) * 100, 2);
    }
}

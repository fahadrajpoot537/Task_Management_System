<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'comment_id',
        'file_path',
        'file_name',
        'file_size',
        'uploaded_by_user_id',
    ];

    /**
     * Get the task that owns the attachment.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the comment that owns the attachment.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(TaskNoteComment::class, 'comment_id');
    }

    /**
     * Get the user that uploaded the attachment.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Get file size in human readable format.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $fullPath = storage_path('app/private/' . $this->file_path);
        
        if (!file_exists($fullPath)) {
            return 'Unknown';
        }
        
        $bytes = filesize($fullPath);
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

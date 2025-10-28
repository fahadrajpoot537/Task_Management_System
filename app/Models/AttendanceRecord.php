<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'late_minutes',
        'early_minutes',
        'hours_worked',
        'status',
        'device_uid',
        'notes',
        'bonus',
        'incentive',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'bonus' => 'decimal:2',
        'incentive' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

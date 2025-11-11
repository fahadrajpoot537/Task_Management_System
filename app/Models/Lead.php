<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'flg_reference',
        'sub_reference',
        'project_id',
        'title',
        'first_name',
        'last_name',
        'received_date',
        'company',
        'phone',
        'alternative_phone',
        'email',
        'address',
        'city',
        'date_of_birth',
        'postcode',
        'note',
        'status_id',
        'added_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'received_date' => 'date',
        'date_of_birth' => 'date',
    ];

    /**
     * Get the project that owns the lead.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that added the lead.
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get the activities for the lead.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the status for the lead.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }
}

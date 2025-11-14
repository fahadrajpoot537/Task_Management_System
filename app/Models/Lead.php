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
        'lead_type_id',
        // Extended fields
        'lead_type',
        'progress',
        'permission_to_call',
        'permission_to_text',
        'permission_to_email',
        'permission_to_mail',
        'permission_to_fax',
        'site_id',
        'site_name',
        'user_id',
        'user_name',
        'buyer_id',
        'buyer_name',
        'buyer_reference',
        'introducer_id',
        'introducer_name',
        'introducer_reference',
        'cost',
        'value',
        'ip_address',
        'marketing_source',
        'marketing_medium',
        'marketing_term',
        'transfer_date_time',
        'transfer_successful',
        'xml_post',
        'xml_response',
        'xml_date_time',
        'xml_fails',
        'xml_result',
        'xml_reference',
        'appointment_date_time',
        'appointment_notes',
        'return_status',
        'return_date_time',
        'return_reason',
        'return_decision_date_time',
        'return_decision_user',
        'return_decision_information',
        'last_note_date_time',
        'task_exists',
        'workflow_exists',
        'full_name',
        'job_title',
        'fax',
        'address2',
        'address3',
        'contact_time',
    ];
    
    // Add Data1-50 and Type1-50 to fillable dynamically
    public function getFillable()
    {
        $fillable = parent::getFillable();
        for ($i = 1; $i <= 50; $i++) {
            $fillable[] = "data{$i}";
            $fillable[] = "type{$i}";
        }
        return $fillable;
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'received_date' => 'date',
        'date_of_birth' => 'date',
        'permission_to_call' => 'boolean',
        'permission_to_text' => 'boolean',
        'permission_to_email' => 'boolean',
        'permission_to_mail' => 'boolean',
        'permission_to_fax' => 'boolean',
        'transfer_successful' => 'boolean',
        'task_exists' => 'boolean',
        'workflow_exists' => 'boolean',
        'transfer_date_time' => 'datetime',
        'xml_date_time' => 'datetime',
        'appointment_date_time' => 'datetime',
        'return_date_time' => 'datetime',
        'return_decision_date_time' => 'datetime',
        'last_note_date_time' => 'datetime',
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

    /**
     * Get the lead type for the lead.
     */
    public function leadType(): BelongsTo
    {
        return $this->belongsTo(LeadType::class, 'lead_type_id');
    }
}

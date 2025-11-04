<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
    ];
    
    /**
     * Get the display name for the permission
     * If display_name is not set, generate it from the name
     */
    public function getDisplayNameAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }
        // Fallback: generate display name from permission name
        return ucfirst(str_replace('_', ' ', $this->attributes['name'] ?? $this->name));
    }

    /**
     * The roles that belong to the permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

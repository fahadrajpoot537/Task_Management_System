<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'hierarchy_level',
        'is_system_role',
        'color',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
        'hierarchy_level' => 'integer',
    ];

    /**
     * Get the users for the role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The permissions that belong to the role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if this role is higher in hierarchy than another role.
     */
    public function isHigherThan(Role $role): bool
    {
        return $this->hierarchy_level < $role->hierarchy_level;
    }

    /**
     * Check if this role can manage another role.
     */
    public function canManageRole(Role $role): bool
    {
        // System roles can only be managed by super admin
        if ($role->is_system_role && !$this->isSuperAdmin()) {
            return false;
        }
        
        // Can only manage roles lower in hierarchy
        return $this->isHigherThan($role);
    }

    /**
     * Check if this is a super admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->name === 'super_admin';
    }

    /**
     * Check if this is an admin role.
     */
    public function isAdmin(): bool
    {
        return $this->name === 'admin';
    }

    /**
     * Check if this is a manager role.
     */
    public function isManager(): bool
    {
        return $this->name === 'manager';
    }

    /**
     * Check if this is an employee role.
     */
    public function isEmployee(): bool
    {
        return $this->name === 'employee';
    }
}

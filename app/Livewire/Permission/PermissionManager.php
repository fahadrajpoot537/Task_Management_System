<?php

namespace App\Livewire\Permission;

use App\Models\Log;
use App\Models\Permission;
use App\Models\Role;
use Livewire\Component;

class PermissionManager extends Component
{
    public $roles;
    public $permissions;
    public $rolePermissions = [];

    public function mount()
    {
        $this->roles = Role::with('permissions')->get();
        $this->permissions = Permission::all();
        
        // Initialize role permissions array
        foreach ($this->roles as $role) {
            $this->rolePermissions[$role->id] = $role->permissions->pluck('id')->toArray();
        }
    }

    public function togglePermission($roleId, $permissionId)
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        if (in_array($permissionId, $this->rolePermissions[$roleId])) {
            // Remove permission
            $role->permissions()->detach($permissionId);
            $this->rolePermissions[$roleId] = array_diff($this->rolePermissions[$roleId], [$permissionId]);
            
            // Log the change
            Log::createLog(auth()->id(), 'remove_role_permission', 
                "Removed permission '{$permission->name}' from role '{$role->name}'");
        } else {
            // Add permission
            $role->permissions()->attach($permissionId);
            $this->rolePermissions[$roleId][] = $permissionId;
            
            // Log the change
            Log::createLog(auth()->id(), 'add_role_permission', 
                "Added permission '{$permission->name}' to role '{$role->name}'");
        }
    }

    public function hasPermission($roleId, $permissionId)
    {
        return in_array($permissionId, $this->rolePermissions[$roleId] ?? []);
    }

    public function render()
    {
        return view('livewire.permission.permission-manager')
            ->layout('layouts.app');
    }
}

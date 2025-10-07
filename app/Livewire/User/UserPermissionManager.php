<?php

namespace App\Livewire\User;

use App\Models\Log;
use App\Models\Permission;
use App\Models\User;
use Livewire\Component;

class UserPermissionManager extends Component
{
    public $user;
    public $userPermissions = [];
    public $allPermissions = [];
    public $search = '';

    public function mount($userId)
    {
        $this->user = User::with(['role.permissions', 'permissions'])->findOrFail($userId);
        $this->loadPermissions();
    }

    public function loadPermissions()
    {
        // Get all available permissions
        $this->allPermissions = Permission::when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
        })->orderBy('name')->get();

        // Get user's current permissions (both from role and custom)
        $this->userPermissions = $this->user->permissions->pluck('id')->toArray();
    }

    public function updatingSearch()
    {
        $this->loadPermissions();
    }

    public function togglePermission($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        
        if (in_array($permissionId, $this->userPermissions)) {
            // Remove permission
            $this->user->permissions()->detach($permissionId);
            $this->userPermissions = array_diff($this->userPermissions, [$permissionId]);
            
            // Log the action
            Log::createLog(auth()->id(), 'remove_user_permission', 
                "Removed permission '{$permission->name}' from user: {$this->user->name}");
                
            session()->flash('success', "Permission '{$permission->name}' removed from user.");
        } else {
            // Add permission
            $this->user->permissions()->attach($permissionId);
            $this->userPermissions[] = $permissionId;
            
            // Log the action
            Log::createLog(auth()->id(), 'add_user_permission', 
                "Added permission '{$permission->name}' to user: {$this->user->name}");
                
            session()->flash('success', "Permission '{$permission->name}' added to user.");
        }
    }

    public function resetToRolePermissions()
    {
        // Remove all custom permissions
        $this->user->permissions()->detach();
        
        // Add all role permissions
        $rolePermissions = $this->user->role->permissions->pluck('id')->toArray();
        $this->user->permissions()->attach($rolePermissions);
        
        $this->userPermissions = $rolePermissions;
        
        // Log the action
        Log::createLog(auth()->id(), 'reset_user_permissions', 
            "Reset permissions to role defaults for user: {$this->user->name}");
            
        session()->flash('success', 'User permissions reset to role defaults.');
    }

    public function clearAllCustomPermissions()
    {
        // Remove all custom permissions (keep only role permissions)
        $this->user->permissions()->detach();
        $this->userPermissions = [];
        
        // Log the action
        Log::createLog(auth()->id(), 'clear_user_permissions', 
            "Cleared all custom permissions for user: {$this->user->name}");
            
        session()->flash('success', 'All custom permissions cleared.');
    }

    public function getUserRolePermissionsProperty()
    {
        return $this->user->role->permissions;
    }

    public function getUserCustomPermissionsProperty()
    {
        return $this->user->permissions;
    }

    public function render()
    {
        return view('livewire.user.user-permission-manager')
            ->layout('layouts.app');
    }
}

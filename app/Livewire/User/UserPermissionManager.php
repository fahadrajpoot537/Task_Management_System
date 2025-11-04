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
        // Check if user has permission to manage user permissions
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && !$currentUser->hasPermission('manage_users')) {
            abort(403, 'You do not have permission to manage user permissions.');
        }
        
        $this->user = User::with(['role', 'permissions'])->findOrFail($userId);
        $this->loadPermissions();
    }

    public function loadPermissions()
    {
        // Get all available permissions
        $this->allPermissions = Permission::when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
        })->orderBy('name')->get();

        // Get user's current permissions (user-based only)
        $this->userPermissions = $this->user->permissions->pluck('id')->toArray();
    }

    public function updatingSearch()
    {
        $this->loadPermissions();
    }

    public function togglePermission($permissionId)
    {
        // Super admin cannot have permissions removed
        if ($this->user->isSuperAdmin() && in_array($permissionId, $this->userPermissions)) {
            session()->flash('info', 'Super admin has all permissions automatically. Cannot remove permissions.');
            return;
        }
        
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

    public function assignAllPermissions()
    {
        if ($this->user->isSuperAdmin()) {
            session()->flash('info', 'Super admin already has all permissions automatically.');
            return;
        }
        
        // Assign all permissions to user
        $allPermissionIds = $this->allPermissions->pluck('id')->toArray();
        $this->user->permissions()->sync($allPermissionIds);
        $this->userPermissions = $allPermissionIds;
        
        // Log the action
        Log::createLog(auth()->id(), 'assign_all_permissions', 
            "Assigned all permissions to user: {$this->user->name}");
            
        session()->flash('success', 'All permissions assigned to user.');
    }

    public function clearAllPermissions()
    {
        // Super admin cannot have permissions cleared
        if ($this->user->isSuperAdmin()) {
            session()->flash('error', 'Cannot clear permissions from super admin.');
            return;
        }
        
        // Remove all permissions
        $this->user->permissions()->detach();
        $this->userPermissions = [];
        
        // Log the action
        Log::createLog(auth()->id(), 'clear_user_permissions', 
            "Cleared all permissions from user: {$this->user->name}");
            
        session()->flash('success', 'All permissions cleared from user.');
    }

    public function render()
    {
        return view('livewire.user.user-permission-manager')
            ->layout('layouts.app');
    }
}

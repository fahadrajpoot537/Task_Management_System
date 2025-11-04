<?php

namespace App\Livewire\Permission;

use App\Models\Log;
use App\Models\Permission;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class PermissionManager extends Component
{
    use WithPagination;

    public $users;
    public $permissions;
    public $userPermissions = [];
    public $search = '';
    public $roleFilter = '';

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        // Get all users with their permissions
        $query = User::with(['role', 'permissions']);
        
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
        }
        
        if ($this->roleFilter) {
            $query->whereHas('role', function($q) {
                $q->where('name', $this->roleFilter);
            });
        }
        
        $this->users = $query->get();
        $this->permissions = Permission::orderBy('name')->get();
        
        // Initialize user permissions array
        foreach ($this->users as $user) {
            $this->userPermissions[$user->id] = $user->permissions->pluck('id')->toArray();
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->loadData();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
        $this->loadData();
    }

    public function togglePermission($userId, $permissionId)
    {
        $user = User::findOrFail($userId);
        $permission = Permission::findOrFail($permissionId);

        // Super admin cannot have permissions removed
        if ($user->isSuperAdmin() && in_array($permissionId, $this->userPermissions[$userId] ?? [])) {
            session()->flash('error', 'Cannot remove permissions from super admin. Super admin has all permissions automatically.');
            return;
        }

        if (in_array($permissionId, $this->userPermissions[$userId] ?? [])) {
            // Remove permission
            $user->permissions()->detach($permissionId);
            $this->userPermissions[$userId] = array_diff($this->userPermissions[$userId] ?? [], [$permissionId]);
            
            // Log the change
            Log::createLog(auth()->id(), 'remove_user_permission', 
                "Removed permission '{$permission->name}' from user '{$user->name}'");
                
            session()->flash('success', "Permission '{$permission->name}' removed from {$user->name}.");
        } else {
            // Add permission
            $user->permissions()->attach($permissionId);
            $this->userPermissions[$userId][] = $permissionId;
            
            // Log the change
            Log::createLog(auth()->id(), 'add_user_permission', 
                "Added permission '{$permission->name}' to user '{$user->name}'");
                
            session()->flash('success', "Permission '{$permission->name}' added to {$user->name}.");
        }
    }

    public function hasPermission($userId, $permissionId)
    {
        $user = User::find($userId);
        
        // Super admin has all permissions
        if ($user && $user->isSuperAdmin()) {
            return true;
        }
        
        return in_array($permissionId, $this->userPermissions[$userId] ?? []);
    }

    public function assignAllPermissions($userId)
    {
        $user = User::findOrFail($userId);
        
        if ($user->isSuperAdmin()) {
            session()->flash('info', 'Super admin already has all permissions automatically.');
            return;
        }
        
        $allPermissionIds = $this->permissions->pluck('id')->toArray();
        $user->permissions()->sync($allPermissionIds);
        $this->userPermissions[$userId] = $allPermissionIds;
        
        Log::createLog(auth()->id(), 'assign_all_permissions', 
            "Assigned all permissions to user '{$user->name}'");
            
        session()->flash('success', "All permissions assigned to {$user->name}.");
    }

    public function clearAllPermissions($userId)
    {
        $user = User::findOrFail($userId);
        
        if ($user->isSuperAdmin()) {
            session()->flash('error', 'Cannot clear permissions from super admin.');
            return;
        }
        
        $user->permissions()->detach();
        $this->userPermissions[$userId] = [];
        
        Log::createLog(auth()->id(), 'clear_all_permissions', 
            "Cleared all permissions from user '{$user->name}'");
            
        session()->flash('success', "All permissions cleared from {$user->name}.");
    }

    public function render()
    {
        return view('livewire.permission.permission-manager')
            ->layout('layouts.app');
    }
}

<?php

namespace App\Livewire;

use App\Models\Log;
use App\Models\Permission;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;

class RoleManager extends Component
{
    use WithPagination;

    public $showForm = false;
    public $editingRole = null;
    public $name = '';
    public $description = '';
    public $color = 'secondary';
    public $permissions = [];
    public $selectedPermissions = [];

    protected $rules = [
        'name' => 'required|string|max:255|unique:roles,name',
        'description' => 'required|string|min:10',
        'color' => 'required|string|in:primary,secondary,success,danger,warning,info,light,dark',
    ];

    protected $messages = [
        'name.required' => 'Role name is required.',
        'name.unique' => 'This role name already exists.',
        'description.required' => 'Role description is required.',
        'description.min' => 'Description must be at least 10 characters.',
        'color.required' => 'Please select a color for the role.',
    ];

    public function mount()
    {
        $this->permissions = Permission::orderBy('name')->get();
    }

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($roleId)
    {
        $role = Role::findOrFail($roleId);
        
        // Check if user can edit this role
        if (!auth()->user()->role->canManageRole($role)) {
            session()->flash('error', 'You do not have permission to edit this role.');
            return;
        }

        $this->editingRole = $role;
        $this->name = $role->name;
        $this->description = $role->description;
        $this->color = $role->color;
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->showForm = true;
    }

    public function save()
    {
        $user = auth()->user();
        
        // Check if user can create/edit roles
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            session()->flash('error', 'You do not have permission to manage roles.');
            return;
        }

        if ($this->editingRole) {
            $this->rules['name'] = 'required|string|max:255|unique:roles,name,' . $this->editingRole->id;
        }

        $this->validate();

        // Determine hierarchy level
        $hierarchyLevel = 5; // Default for custom roles (after employee)
        if ($user->isSuperAdmin()) {
            $hierarchyLevel = 5; // Super admin can create roles at any level
        } elseif ($user->isAdmin()) {
            $hierarchyLevel = 5; // Admin can only create roles after manager level
        }

        $roleData = [
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'hierarchy_level' => $hierarchyLevel,
            'is_system_role' => false,
        ];

        if ($this->editingRole) {
            $this->editingRole->update($roleData);
            $role = $this->editingRole;
            $action = 'update_role';
            $message = "Updated role: {$role->name}";
        } else {
            $role = Role::create($roleData);
            $action = 'create_role';
            $message = "Created role: {$role->name}";
        }

        // Sync permissions
        $role->permissions()->sync($this->selectedPermissions);

        // Log the action
        Log::createLog(auth()->id(), $action, $message);

        session()->flash('success', $this->editingRole ? 'Role updated successfully!' : 'Role created successfully!');
        
        $this->resetForm();
    }

    public function delete($roleId)
    {
        $role = Role::findOrFail($roleId);
        
        // Check if user can delete this role
        if (!auth()->user()->role->canManageRole($role)) {
            session()->flash('error', 'You do not have permission to delete this role.');
            return;
        }

        // Check if role is in use
        if ($role->users()->count() > 0) {
            session()->flash('error', 'Cannot delete role. It is currently assigned to users.');
            return;
        }

        // Log the deletion
        Log::createLog(auth()->id(), 'delete_role', "Deleted role: {$role->name}");

        $role->delete();
        
        session()->flash('success', 'Role deleted successfully!');
    }

    public function resetForm()
    {
        $this->editingRole = null;
        $this->name = '';
        $this->description = '';
        $this->color = 'secondary';
        $this->selectedPermissions = [];
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->resetForm();
        }
    }

    public function getRolesProperty()
    {
        $user = auth()->user();
        
        $query = Role::with('permissions', 'users');
        
        // Filter roles based on user permissions
        if ($user->isSuperAdmin()) {
            // Super admin can see all roles
        } elseif ($user->isAdmin()) {
            // Admin can see all roles but cannot manage system roles
            $query->where('hierarchy_level', '>=', 3); // Manager level and below
        } else {
            // Others cannot manage roles
            return collect();
        }
        
        return $query->orderBy('hierarchy_level')->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.role-manager')
            ->layout('layouts.app');
    }
}

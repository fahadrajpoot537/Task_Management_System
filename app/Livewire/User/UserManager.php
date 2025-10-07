<?php

namespace App\Livewire\User;

use App\Mail\UserInvitation;
use App\Models\Log;
use App\Models\Role;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserManager extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $showCreateForm = false;
    
    // Create user form fields
    public $name = '';
    public $email = '';
    public $role_id = '';
    public $manager_id = '';
    public $sendInvitation = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'role_id' => 'required|exists:roles,id',
        'manager_id' => 'nullable|exists:users,id',
        'sendInvitation' => 'boolean',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function openForm()
    {
        $this->showCreateForm = true;
        $this->resetForm();
    }

    public function hideCreateForm()
    {
        $this->showCreateForm = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->role_id = '';
        $this->manager_id = '';
        $this->sendInvitation = true;
        $this->resetErrorBag();
    }

    public function createUser()
    {
        // Get employee role first
        $employeeRole = Role::where('name', 'employee')->first();
        
        if (!$employeeRole) {
            session()->flash('error', 'Employee role not found.');
            return;
        }

        // Set the role_id for validation
        $this->role_id = $employeeRole->id;

        $this->validate();

        // Generate a temporary password
        $tempPassword = 'temp' . rand(1000, 9999);
        
        $newUser = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($tempPassword),
            'role_id' => $employeeRole->id,
            'manager_id' => $this->manager_id,
        ]);

        // Log the creation
        Log::createLog(auth()->id(), 'create_employee', "Created employee: {$newUser->name} ({$newUser->email})");

        // Send invitation email if requested
        if ($this->sendInvitation) {
            Mail::to($newUser->email)->send(new UserInvitation($newUser, $tempPassword));
        }

        session()->flash('success', 'Employee created successfully' . ($this->sendInvitation ? ' and invitation sent!' : '!'));

        $this->hideCreateForm();
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        
        // Check if user can delete this user
        if (!auth()->user()->isSuperAdmin()) {
            session()->flash('error', 'Only Super Admin can delete users.');
            return;
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        // Log the deletion
        Log::createLog(auth()->id(), 'delete_user', "Deleted user: {$user->name} ({$user->email})");

        $user->delete();
        
        session()->flash('success', 'User deleted successfully.');
    }

    public function resendInvitation($userId)
    {
        $user = User::findOrFail($userId);
        
        // Generate new temporary password
        $tempPassword = 'temp' . rand(1000, 9999);
        $user->update(['password' => Hash::make($tempPassword)]);
        
        // Send invitation
        Mail::to($user->email)->send(new UserInvitation($user, $tempPassword));
        
        // Log the action
        Log::createLog(auth()->id(), 'resend_invitation', "Resent invitation to: {$user->name} ({$user->email})");
        
        session()->flash('success', 'Invitation resent successfully!');
    }

    public function getUsersProperty()
    {
        $user = auth()->user();
        
        $query = User::with(['role', 'manager', 'teamMembers'])
            ->whereHas('role', function ($q) {
                $q->where('name', 'employee');
            })
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('role', function ($q) {
                    $q->where('name', $this->roleFilter);
                });
            });

        if ($user->isSuperAdmin()) {
            // Super admin can see all employees
        } elseif ($user->isManager()) {
            // Managers can see their team members
            $teamMemberIds = $user->teamMembers->pluck('id');
            $query->whereIn('id', $teamMemberIds);
        } else {
            // Employees can only see themselves
            $query->where('id', $user->id);
        }

        return $query->orderBy('name')->paginate(10);
    }

    public function getRolesProperty()
    {
        // Only allow creating employees in this interface
        return Role::where('name', 'employee')->get();
    }

    public function getManagersProperty()
    {
        return User::whereHas('role', function ($query) {
            $query->where('name', 'manager');
        })->get();
    }

    public function render()
    {
        return view('livewire.user.user-manager')
            ->layout('layouts.app');
    }
}

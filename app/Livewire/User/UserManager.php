<?php

namespace App\Livewire\User;

use App\Mail\UserInvitation;
use App\Models\Log;
use App\Models\Role;
use App\Models\User;
use App\Services\PasswordGeneratorService;
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
	// Edit modal state
	public $editingUserId = null;
	public $edit_monthly_salary = '';
	public $edit_device_user_id = '';
	public $edit_check_in_time = '';
	public $edit_check_out_time = '';
	public $edit_employment_status = '';
    
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

        // Generate a secure temporary password
        $tempPassword = PasswordGeneratorService::generateTempPassword();
        
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

	public function openEdit($userId)
	{
		$user = User::findOrFail($userId);
		$this->editingUserId = $user->id;
		$this->edit_monthly_salary = $user->monthly_salary;
		$this->edit_device_user_id = $user->device_user_id;
		$this->edit_check_in_time = $user->check_in_time ? substr($user->check_in_time, 0, 5) : '';
		$this->edit_check_out_time = $user->check_out_time ? substr($user->check_out_time, 0, 5) : '';
		$this->edit_employment_status = $user->employment_status;

		$this->dispatch('open-edit-modal');
	}

	public function cancelEdit()
	{
		$this->editingUserId = null;
		$this->edit_monthly_salary = '';
		$this->edit_device_user_id = '';
		$this->edit_check_in_time = '';
		$this->edit_check_out_time = '';
		$this->edit_employment_status = '';
		$this->resetErrorBag();
		$this->dispatch('close-edit-modal');
	}

	public function saveEdit()
	{
		$this->validate([
			'edit_monthly_salary' => 'nullable|numeric|min:0',
			'edit_device_user_id' => 'nullable|max:255',
			'edit_check_in_time' => ['nullable','regex:/^\d{2}:\d{2}(?::\d{2})?$/'],
			'edit_check_out_time' => ['nullable','regex:/^\d{2}:\d{2}(?::\d{2})?$/'],
			'edit_employment_status' => 'nullable|in:probation,permanent,terminated',
		]);

		$user = User::findOrFail($this->editingUserId);

		$user->update([
			'monthly_salary' => $this->edit_monthly_salary !== '' ? $this->edit_monthly_salary : null,
			'device_user_id' => $this->edit_device_user_id !== '' ? (string)$this->edit_device_user_id : null,
			'check_in_time' => $this->edit_check_in_time !== '' ? substr($this->edit_check_in_time, 0, 5) : null,
			'check_out_time' => $this->edit_check_out_time !== '' ? substr($this->edit_check_out_time, 0, 5) : null,
			'employment_status' => $this->edit_employment_status !== '' ? strtolower($this->edit_employment_status) : null,
		]);

		session()->flash('success', 'User updated successfully.');
		$this->dispatch('close-edit-modal');
		$this->cancelEdit();
	}

	public function makePermanent($userId)
	{
		$user = User::findOrFail($userId);
		$user->update(['employment_status' => 'permanent']);
		session()->flash('success', $user->name . ' is now permanent.');
	}

	public function terminate($userId)
	{
		$user = User::findOrFail($userId);
		$user->update(['employment_status' => 'terminated']);
		session()->flash('success', $user->name . ' has been terminated.');
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

	/**
	 * @return mixed
	 */
	public function render()
    {
        return view('livewire.user.user-manager')
            ->layout('layouts.app');
    }
}

<?php

namespace App\Livewire\Manager;

use App\Mail\UserInvitation;
use App\Models\Log;
use App\Models\Role;
use App\Models\User;
use App\Services\PasswordGeneratorService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ManagerManager extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreateForm = false;
    
    // Create manager form fields
    public $name = '';
    public $email = '';
    public $sendInvitation = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'sendInvitation' => 'boolean',
    ];

    public function updatingSearch()
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
        $this->sendInvitation = true;
        $this->resetErrorBag();
    }

    public function createManager()
    {
        $this->validate();

        // Get manager role
        $managerRole = Role::where('name', 'manager')->first();
        
        if (!$managerRole) {
            session()->flash('error', 'Manager role not found.');
            return;
        }

        // Generate a secure temporary password
        $tempPassword = PasswordGeneratorService::generateTempPassword();
        
        $newManager = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($tempPassword),
            'role_id' => $managerRole->id,
            'manager_id' => null, // Managers don't have managers
        ]);

        // Log the creation
        Log::createLog(auth()->id(), 'create_manager', "Created manager: {$newManager->name} ({$newManager->email})");

        // Send invitation email if requested
        if ($this->sendInvitation) {
            Mail::to($newManager->email)->send(new UserInvitation($newManager, $tempPassword));
        }

        session()->flash('success', 'Manager created successfully' . ($this->sendInvitation ? ' and invitation sent!' : '!'));

        $this->hideCreateForm();
    }

    public function deleteManager($managerId)
    {
        $manager = User::findOrFail($managerId);
        
        // Check if user can delete managers
        if (!auth()->user()->isSuperAdmin()) {
            session()->flash('error', 'Only Super Admin can delete managers.');
            return;
        }

        // Prevent self-deletion
        if ($manager->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        // Check if manager has team members
        if ($manager->teamMembers->count() > 0) {
            session()->flash('error', 'Cannot delete manager with team members. Please reassign team members first.');
            return;
        }

        // Log the deletion
        Log::createLog(auth()->id(), 'delete_manager', "Deleted manager: {$manager->name} ({$manager->email})");

        $manager->delete();
        
        session()->flash('success', 'Manager deleted successfully.');
    }

    public function resendInvitation($managerId)
    {
        $manager = User::findOrFail($managerId);
        
        // Generate new temporary password
        $tempPassword = 'temp' . rand(1000, 9999);
        $manager->update(['password' => Hash::make($tempPassword)]);
        
        // Send invitation
        Mail::to($manager->email)->send(new UserInvitation($manager, $tempPassword));
        
        // Log the action
        Log::createLog(auth()->id(), 'resend_invitation', "Resent invitation to manager: {$manager->name} ({$manager->email})");
        
        session()->flash('success', 'Invitation resent successfully!');
    }

    public function getManagersProperty()
    {
        return User::with(['role', 'teamMembers'])
            ->whereHas('role', function ($query) {
                $query->where('name', 'manager');
            })
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.manager.manager-manager')
            ->layout('layouts.app');
    }
}

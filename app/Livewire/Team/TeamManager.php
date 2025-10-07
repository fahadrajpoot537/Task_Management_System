<?php

namespace App\Livewire\Team;

use App\Models\Log;
use App\Models\Role;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class TeamManager extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $managerFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'managerFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingManagerFilter()
    {
        $this->resetPage();
    }

    public function assignManager($userId, $managerId)
    {
        $user = User::findOrFail($userId);
        $manager = $managerId ? User::findOrFail($managerId) : null;

        $oldManager = $user->manager;
        $user->update(['manager_id' => $managerId]);

        // Log the change
        $managerName = $manager ? $manager->name : 'None';
        $oldManagerName = $oldManager ? $oldManager->name : 'None';
        
        Log::createLog(auth()->id(), 'assign_manager', 
            "Changed manager for '{$user->name}' from '{$oldManagerName}' to '{$managerName}'");

        session()->flash('success', 'Manager assigned successfully.');
    }

    public function getUsersProperty()
    {
        $query = User::with(['role', 'manager', 'teamMembers'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('role', function ($q) {
                    $q->where('name', $this->roleFilter);
                });
            })
            ->when($this->managerFilter, function ($query) {
                if ($this->managerFilter === 'unassigned') {
                    $query->whereNull('manager_id');
                } else {
                    $query->where('manager_id', $this->managerFilter);
                }
            });

        return $query->orderBy('name')->paginate(10);
    }

    public function getRolesProperty()
    {
        return Role::all();
    }

    public function getManagersProperty()
    {
        return User::whereHas('role', function ($query) {
            $query->where('name', 'manager');
        })->get();
    }

    public function render()
    {
        return view('livewire.team.team-manager')
            ->layout('layouts.app');
    }
}

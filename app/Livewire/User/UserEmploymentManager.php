<?php

namespace App\Livewire\User;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserEmploymentManager extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $editingUserId = null;
    public $monthlySalary = '';
    public $deviceUserId = '';
    public $checkInTime = '';
    public $checkOutTime = '';
    public $employmentStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updated($property)
    {
        if (in_array($property, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function editUser($userId)
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->monthlySalary = $user->monthly_salary;
        $this->deviceUserId = $user->device_user_id;
        $this->checkInTime = $user->check_in_time;
        $this->checkOutTime = $user->check_out_time;
        $this->employmentStatus = $user->employment_status;
    }

    public function cancelEdit()
    {
        $this->editingUserId = null;
        $this->monthlySalary = '';
        $this->deviceUserId = '';
        $this->checkInTime = '';
        $this->checkOutTime = '';
        $this->employmentStatus = '';
        $this->resetErrorBag();
    }

    public function saveUser()
    {
        $this->validate([
            'monthlySalary' => 'nullable|numeric|min:0',
            'deviceUserId' => 'nullable|string|max:255',
            'checkInTime' => 'nullable|date_format:H:i',
            'checkOutTime' => 'nullable|date_format:H:i',
            'employmentStatus' => 'nullable|in:probation,permanent,terminated',
        ]);

        try {
            $user = User::findOrFail($this->editingUserId);

            $updateData = [
                'monthly_salary' => $this->monthlySalary !== '' ? $this->monthlySalary : null,
                'device_user_id' => $this->deviceUserId !== '' ? $this->deviceUserId : null,
                'check_in_time' => $this->checkInTime !== '' ? $this->checkInTime : null,
                'check_out_time' => $this->checkOutTime !== '' ? $this->checkOutTime : null,
                'employment_status' => $this->employmentStatus !== '' ? $this->employmentStatus : null,
            ];

            $user->update($updateData);

            session()->flash('success', 'User details updated successfully for ' . $user->name);
            $this->cancelEdit();
        } catch (\Exception $e) {
            Log::error('Error updating user employment details', [
                'user_id' => $this->editingUserId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Error updating user: ' . $e->getMessage());
        }
    }

    public function terminate($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->update(['employment_status' => 'terminated']);
            session()->flash('success', $user->name . ' has been terminated.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to terminate user: ' . $e->getMessage());
        }
    }

    public function makePermanent($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->update(['employment_status' => 'permanent']);
            session()->flash('success', $user->name . ' is now permanent.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to convert to permanent: ' . $e->getMessage());
        }
    }

    public function getUsersProperty()
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('device_user_id', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.user.user-employment-manager', [
            'users' => $this->users,
        ]);
    }
}



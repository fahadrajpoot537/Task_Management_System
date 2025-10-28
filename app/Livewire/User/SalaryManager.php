<?php

namespace App\Livewire\User;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SalaryManager extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'name';
    public $sortDirection = 'asc';
    
    // Salary editing properties
    public $editingUserId = null;
    public $monthlySalary = '';
    public $bonus = '';
    public $incentive = '';
    public $joiningDate = '';
    public $employmentStatus = '';
    public $shiftStart = '';
    public $shiftEnd = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount()
    {
        // Set default values
    }

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

    public function editSalary($userId)
    {
        Log::info('editSalary method called', ['userId' => $userId]);
        
        $user = User::findOrFail($userId);
        
        $this->editingUserId = $userId;
        $this->monthlySalary = $user->monthly_salary ?? '';
        $this->bonus = $user->bonus ?? '';
        $this->incentive = $user->incentive ?? '';
        $this->joiningDate = $user->joining_date ? (is_string($user->joining_date) ? $user->joining_date : $user->joining_date->format('Y-m-d')) : '';
        $this->employmentStatus = $user->employment_status ?? '';
        $this->shiftStart = $user->shift_start ?? '';
        $this->shiftEnd = $user->shift_end ?? '';
        
        Log::info('editSalary values set', [
            'editingUserId' => $this->editingUserId,
            'monthlySalary' => $this->monthlySalary,
            'bonus' => $this->bonus,
            'incentive' => $this->incentive,
        ]);
    }

    public function saveSalary()
    {
        if (!$this->editingUserId) {
            session()->flash('error', 'No user selected for editing.');
            return;
        }

        Log::info('saveSalary method called', [
            'editingUserId' => $this->editingUserId,
            'monthlySalary' => $this->monthlySalary,
            'bonus' => $this->bonus,
            'incentive' => $this->incentive,
        ]);

        // Simplified validation - just check if values are numeric
        if ($this->monthlySalary && !is_numeric($this->monthlySalary)) {
            session()->flash('error', 'Monthly salary must be a number');
            return;
        }
        if ($this->bonus && !is_numeric($this->bonus)) {
            session()->flash('error', 'Bonus must be a number');
            return;
        }
        if ($this->incentive && !is_numeric($this->incentive)) {
            session()->flash('error', 'Incentive must be a number');
            return;
        }
        
        Log::info('Validation passed successfully');

        try {
            $user = User::findOrFail($this->editingUserId);
            
            $updateData = [
                'monthly_salary' => $this->monthlySalary ?: null,
                'bonus' => $this->bonus ?: null,
                'incentive' => $this->incentive ?: null,
                'joining_date' => $this->joiningDate ?: null,
                'employment_status' => $this->employmentStatus ?: null,
                'shift_start' => $this->shiftStart ?: null,
                'shift_end' => $this->shiftEnd ?: null,
            ];

            Log::info('Updating user with data', [
                'user_id' => $user->id,
                'update_data' => $updateData,
            ]);
            
            $result = $user->update($updateData);
            
            Log::info('Update result', ['result' => $result]);

            session()->flash('success', 'Salary and employment details updated successfully for ' . $user->name);
            
            Log::info('Salary updated for user', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'monthly_salary' => $this->monthlySalary,
                'bonus' => $this->bonus,
                'incentive' => $this->incentive,
            ]);

            $this->cancelEdit();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating salary: ' . $e->getMessage());
            Log::error('Error updating salary', [
                'user_id' => $this->editingUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function cancelEdit()
    {
        $this->editingUserId = null;
        $this->monthlySalary = '';
        $this->bonus = '';
        $this->incentive = '';
        $this->joiningDate = '';
        $this->employmentStatus = '';
        $this->shiftStart = '';
        $this->shiftEnd = '';
    }

    public function testSave()
    {
        Log::info('testSave method called');
        
        try {
            $user = User::first();
            if ($user) {
                $result = $user->update(['monthly_salary' => 99999]);
                Log::info('Direct update test result', ['result' => $result, 'user_id' => $user->id]);
                session()->flash('success', 'Test save successful! User: ' . $user->name);
            } else {
                session()->flash('error', 'No user found for test');
            }
        } catch (\Exception $e) {
            Log::error('Test save failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Test save failed: ' . $e->getMessage());
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

    public function getSummaryStatsProperty()
    {
        $totalUsers = User::count();
        $usersWithSalary = User::whereNotNull('monthly_salary')->count();
        $totalSalary = User::whereNotNull('monthly_salary')->sum('monthly_salary');
        $avgSalary = $usersWithSalary > 0 ? $totalSalary / $usersWithSalary : 0;

        return [
            'total_users' => $totalUsers,
            'users_with_salary' => $usersWithSalary,
            'total_salary' => $totalSalary,
            'avg_salary' => $avgSalary,
        ];
    }

    public function render()
    {
        return view('livewire.user.salary-manager', [
            'users' => $this->users,
            'summaryStats' => $this->summaryStats,
        ]);
    }
}
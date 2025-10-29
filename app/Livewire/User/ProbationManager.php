<?php

namespace App\Livewire\User;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class ProbationManager extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'joining_date';
    public $sortDirection = 'asc';
    public $showEligibleOnly = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'showEligibleOnly' => ['except' => false],
    ];

    public function mount()
    {
        // Initialize component
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'perPage', 'showEligibleOnly'])) {
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

    public function convertToPermanent()
    {
        try {
            // Run the artisan command
            Artisan::call('users:convert-probation');
            $output = Artisan::output();
            
            session()->flash('success', 'Probation conversion completed successfully!');
            
            Log::info('Manual probation conversion triggered', [
                'triggered_by' => auth()->user()->name ?? 'System',
                'output' => $output,
            ]);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error converting probation users: ' . $e->getMessage());
            
            Log::error('Error in manual probation conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function dryRunConversion()
    {
        try {
            // Run the artisan command in dry-run mode
            Artisan::call('users:convert-probation', ['--dry-run' => true]);
            $output = Artisan::output();
            
            session()->flash('info', 'Dry run completed. Check logs for details.');
            
            Log::info('Dry run probation conversion', [
                'triggered_by' => auth()->user()->name ?? 'System',
                'output' => $output,
            ]);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error in dry run: ' . $e->getMessage());
        }
    }

    public function getProbationUsersProperty()
    {
        $query = User::where('employment_status', 'probation')
            ->whereNotNull('joining_date');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('device_user_id', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->showEligibleOnly) {
            $threeMonthsAgo = Carbon::now()->subMonths(3);
            $query->where('joining_date', '<=', $threeMonthsAgo);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
                    ->paginate($this->perPage);
    }

    public function getSummaryStatsProperty()
    {
        $totalProbation = User::where('employment_status', 'probation')->count();
        $eligibleForConversion = User::where('employment_status', 'probation')
            ->whereNotNull('joining_date')
            ->where('joining_date', '<=', Carbon::now()->subMonths(3))
            ->count();
        $totalActive = User::where('employment_status', 'active')->count();
        $totalUsers = User::count();

        return [
            'total_probation' => $totalProbation,
            'eligible_for_conversion' => $eligibleForConversion,
            'total_active' => $totalActive,
            'total_users' => $totalUsers,
        ];
    }

    public function render()
    {
        return view('livewire.user.probation-manager', [
            'probationUsers' => $this->probationUsers,
            'summaryStats' => $this->summaryStats,
        ]);
    }
}
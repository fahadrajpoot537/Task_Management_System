<?php

namespace App\Livewire\Task;

use App\Models\Log;
use App\Models\Task;
use App\Models\TaskStatus;
use Livewire\Component;
use Livewire\WithPagination;

class TaskIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $priorityFilter = '';
    public $userFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
        'userFilter' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter()
    {
        $this->resetPage();
    }

    public function updatingUserFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    public function updateTaskStatus($taskId, $statusId)
    {
        $user = auth()->user();
        $task = Task::findOrFail($taskId);
        
        // Check if user can update this task
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            if ($user->isManager()) {
                // Managers can update tasks assigned to their team members and themselves
                $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                if (!in_array($task->assigned_to_user_id, $teamMemberIds->toArray()) && 
                    $task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            } else {
                // Employees can only update tasks assigned to them
                if ($task->assigned_to_user_id !== $user->id && 
                    $task->assigned_by_user_id !== $user->id) {
                    session()->flash('error', 'You do not have permission to update this task.');
                    return;
                }
            }
        }

        // Check if employee is trying to set Complete status
        if ($user->isEmployee()) {
            $status = TaskStatus::findOrFail($statusId);
            if ($status->name === 'Complete') {
                session()->flash('error', 'Only managers, admins, and super admins can mark tasks as complete.');
                return;
            }
        }

        $oldStatus = $task->status ? $task->status->name : 'No Status';
        $task->update(['status_id' => $statusId]);
        $task->load('status');
        
        $newStatus = $task->status->name;

        // Log the status change
        Log::createLog(auth()->id(), 'update_task_status', 
            "Changed task '{$task->title}' status from {$oldStatus} to {$newStatus}");

        // Process recurring task if status is "Complete"
        if ($newStatus === 'Complete') {
            $recurringService = new \App\Services\RecurringTaskService();
            $recurringService->processRecurringTask($task);
        }

        session()->flash('success', 'Task status updated successfully.');
    }

    public function deleteTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        
        // Check if user can delete this task
        if (!auth()->user()->isSuperAdmin() && 
            $task->assigned_by_user_id !== auth()->id()) {
            session()->flash('error', 'You do not have permission to delete this task.');
            return;
        }

        // Log the deletion
        Log::createLog(auth()->id(), 'delete_task', "Deleted task: {$task->title}");

        $task->delete();
        
        session()->flash('success', 'Task deleted successfully.');
    }

    public function getTasksProperty()
    {
        $user = auth()->user();
        
        $query = Task::with(['project', 'assignedTo', 'assignedBy'])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->priorityFilter, function ($query) {
                $query->where('priority', $this->priorityFilter);
            })
            ->when($this->userFilter, function ($query) {
                $query->where('assigned_to_user_id', $this->userFilter);
            });

        if ($user->isSuperAdmin()) {
            // Super admin can see all tasks
        } elseif ($user->isAdmin()) {
            // Admin can see all tasks
        } elseif ($user->isManager()) {
            // Managers can see tasks assigned to their team members and themselves
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            $query->whereIn('assigned_to_user_id', $teamMemberIds);
        } else {
            // Employees can only see tasks assigned to them
            $query->where('assigned_to_user_id', $user->id);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
                    ->paginate(10);
    }

    public function getAvailableUsersProperty()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            return \App\Models\User::all();
        } elseif ($user->isAdmin()) {
            return \App\Models\User::all();
        } elseif ($user->isManager()) {
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            return \App\Models\User::whereIn('id', $teamMemberIds)->get();
        } else {
            return collect([$user]);
        }
    }

    public function getStatusesProperty()
    {
        return TaskStatus::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.task.task-index')
            ->layout('layouts.app');
    }
}

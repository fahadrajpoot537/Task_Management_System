<?php

namespace App\Http\Controllers\Ajax\Task;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\RecurringTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskIndexController extends Controller
{
    /**
     * Display the task index page
     */
    public function index()
    {
        return view('ajax.task.task-index');
    }

    /**
     * Get tasks with filters and pagination
     */
    public function getTasks(Request $request)
    {
        $user = auth()->user();
        
        $query = Task::with(['project', 'assignedTo', 'assignedBy'])
            ->when($request->search, function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->when($request->statusFilter, function ($query) use ($request) {
                $query->where('status', $request->statusFilter);
            })
            ->when($request->priorityFilter, function ($query) use ($request) {
                $query->where('priority', $request->priorityFilter);
            })
            ->when($request->userFilter, function ($query) use ($request) {
                $query->where('assigned_to_user_id', $request->userFilter);
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

        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortDirection', 'desc');
        $perPage = $request->get('per_page', 10);
        
        $tasks = $query->orderBy($sortField, $sortDirection)->paginate($perPage);

        return response()->json([
            'success' => true,
            'tasks' => $tasks->items(),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'from' => $tasks->firstItem(),
                'to' => $tasks->lastItem(),
                'has_more_pages' => $tasks->hasMorePages(),
            ]
        ]);
    }

    /**
     * Get dropdown data
     */
    public function getDropdownData()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            $users = \App\Models\User::all();
        } elseif ($user->isAdmin()) {
            $users = \App\Models\User::all();
        } elseif ($user->isManager()) {
            $users = \App\Models\User::orderBy('name')->get();
        } else {
            $users = collect([$user]);
        }
        
        $statuses = TaskStatus::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'users' => $users,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'status_id' => 'required|exists:task_statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $task = Task::findOrFail($taskId);
            
            // Check permissions
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                if ($user->isManager()) {
                    $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
                    if (!in_array($task->assigned_to_user_id, $teamMemberIds->toArray()) && 
                        $task->assigned_by_user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to update this task.'
                        ], 403);
                    }
                } else {
                    if ($task->assigned_to_user_id !== $user->id && 
                        $task->assigned_by_user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to update this task.'
                        ], 403);
                    }
                }
            }

            $oldStatus = $task->status ? $task->status->name : 'No Status';
            $task->update(['status_id' => $request->status_id]);
            $task->load('status');
            
            $newStatus = $task->status->name;

            Log::createLog(auth()->id(), 'update_task_status', 
                "Changed task '{$task->title}' status from {$oldStatus} to {$newStatus}");

            // Process recurring task if status is "Complete"
            if ($newStatus === 'Complete') {
                $recurringService = new RecurringTaskService();
                $recurringService->processRecurringTask($task);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully.',
                'task' => $task->load(['status'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete task
     */
    public function deleteTask($taskId)
    {
        try {
            $task = Task::findOrFail($taskId);
            
            // Check permissions
            if (!auth()->user()->isSuperAdmin() && 
                $task->assigned_by_user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete this task.'
                ], 403);
            }

            Log::createLog(auth()->id(), 'delete_task', "Deleted task: {$task->title}");
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task: ' . $e->getMessage()
            ], 500);
        }
    }
}


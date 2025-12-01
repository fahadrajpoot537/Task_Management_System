<?php

namespace App\Http\Controllers\Ajax\Task;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Log;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\TaskCategory;
use App\Models\TaskNoteComment;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\RecurringTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Support\Facades\Validator;

class TaskTableController extends Controller
{
    protected $emailService;

    public function __construct()
    {
        $this->emailService = new EmailNotificationService();
    }

    /**
     * Display the task table page
     */
    public function index()
    {
        return view('ajax.task.task-table');
    }

    /**
     * Get tasks with filters and pagination
     */
    public function getTasks(Request $request)
    {
        $user = auth()->user();
        
        $query = Task::with(['project:id,title', 'assignedTo:id,name,email', 'assignedTo.role:id,name', 'assignedBy:id,name', 'assignees:id,name,email', 'assignees.role:id,name', 'status:id,name,color', 'priority:id,name,color', 'category:id,name,icon,color'])
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->projectFilter, function ($query) use ($request) {
                $query->where('project_id', $request->projectFilter);
            })
            ->when($request->statusFilter, function ($query) use ($request) {
                $query->where('status_id', $request->statusFilter);
            })
            ->when($request->categoryFilter, function ($query) use ($request) {
                $query->where('category_id', $request->categoryFilter);
            })
            ->when($request->assigneeFilter, function ($query) use ($request) {
                $query->where('assigned_to_user_id', $request->assigneeFilter);
            });

        if ($user->isSuperAdmin()) {
            // Super admin can see all tasks
        } elseif ($user->isAdmin()) {
            // Admin can see all tasks
        } elseif ($user->isManager()) {
            // Managers can see tasks assigned to their team members and themselves
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            $query->where(function ($q) use ($teamMemberIds, $user) {
                $q->whereIn('assigned_to_user_id', $teamMemberIds)
                  ->orWhere('assigned_by_user_id', $user->id);
            });
        } else {
            // Employees can only see tasks assigned to them
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to_user_id', $user->id)
                  ->orWhere('assigned_by_user_id', $user->id)
                  ->orWhereHas('assignees', function ($subQ) use ($user) {
                      $subQ->where('user_id', $user->id);
                  });
            });
        }

        $perPage = $request->get('per_page', 15);
        $tasks = $query->orderBy('created_at', 'desc')->paginate($perPage);

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
     * Get dropdown data (projects, users, statuses, priorities, categories)
     */
    public function getDropdownData()
    {
        $user = auth()->user();
        
        $projects = Project::select('id', 'title')->orderBy('title')->get();
        
        if ($user->isSuperAdmin() || $user->isAdmin() || $user->isManager()) {
            $users = User::with('role')->select('id', 'name', 'email')->orderBy('name')->get();
        } else {
            $users = collect([$user->load('role')]);
        }
        
        $statuses = TaskStatus::select('id', 'name', 'color')->orderBy('name')->get();
        $priorities = TaskPriority::select('id', 'name', 'color')->orderBy('name')->get();
        $categories = TaskCategory::select('id', 'name', 'icon', 'color')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'projects' => $projects,
            'users' => $users,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'categories' => $categories,
            'user' => [
                'id' => $user->id,
                'is_super_admin' => $user->isSuperAdmin(),
                'is_admin' => $user->isAdmin(),
                'is_manager' => $user->isManager(),
            ],
        ]);
    }

    /**
     * Create a new task
     */
    public function createTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'assignee_ids' => 'required|array|min:1',
            'assignee_ids.*' => 'exists:users,id',
            'priority_id' => 'required|exists:task_priorities,id',
            'category_id' => 'required|exists:task_categories,id',
            'due_date' => 'nullable|date|after_or_equal:today',
            'estimated_hours' => 'nullable|integer|min:0',
            'reminder_time' => 'nullable',
            'notes' => 'nullable|string',
            'nature' => 'required|in:one_time,recurring',
            'recurrence_frequency' => 'required_if:nature,recurring|in:daily,weekly,monthly',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $isRecurring = $request->nature === 'recurring';
            $isRecurringActive = $isRecurring ? 1 : 0;
            $natureOfTask = $request->nature === 'recurring' ? $request->recurrence_frequency : 'one_time';
            $primaryAssigneeId = !empty($request->assignee_ids) ? $request->assignee_ids[0] : null;
            
            if (!$primaryAssigneeId) {
                throw new \Exception('No assignee selected');
            }
            
            $reminderTime = null;
            if ($request->reminder_time && $request->reminder_time != '') {
                try {
                    $reminderTime = date('Y-m-d H:i:s', strtotime($request->reminder_time));
                } catch (\Exception $e) {
                    LogFacade::error('Failed to parse reminder time: ' . $e->getMessage());
                    $reminderTime = null;
                }
            }
            
            $task = Task::create([
                'project_id' => $request->project_id,
                'title' => $request->title,
                'description' => $request->description,
                'assigned_to_user_id' => $primaryAssigneeId,
                'assigned_by_user_id' => auth()->id(),
                'priority_id' => $request->priority_id,
                'category_id' => $request->category_id,
                'status_id' => TaskStatus::where('name', 'Pending')->first()->id,
                'due_date' => $request->due_date,
                'estimated_hours' => $request->estimated_hours,
                'reminder_time' => $reminderTime,
                'notes' => $request->notes,
                'nature_of_task' => $natureOfTask,
                'is_recurring' => $isRecurring,
                'is_recurring_active' => $isRecurringActive,
            ]);

            // Attach all assignees
            if (!empty($request->assignee_ids)) {
                $assignments = [];
                foreach ($request->assignee_ids as $userId) {
                    $assignments[$userId] = [
                        'assigned_by_user_id' => auth()->id(),
                        'assigned_at' => now(),
                    ];
                }
                $task->assignees()->attach($assignments);
            }

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $path = $attachment->store('attachments');
                    
                    Attachment::create([
                        'task_id' => $task->id,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_size' => $attachment->getSize(),
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            // Send email notifications
            $this->emailService->sendTaskAssignedNotification($task);
            if (count($request->assignee_ids) > 1) {
                foreach ($request->assignee_ids as $assigneeId) {
                    if ($assigneeId != $task->assigned_to_user_id) {
                        $assignee = User::find($assigneeId);
                        if ($assignee) {
                            $this->emailService->sendTaskAssignedNotification($task, $assignee);
                        }
                    }
                }
            }

            Log::createLog(auth()->id(), 'create_task', "Created task: {$task->title}");

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully!',
                'task' => $task->load(['project', 'assignedTo', 'status', 'priority', 'category'])
            ]);

        } catch (\Exception $e) {
            LogFacade::error('Task creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a task
     */
    public function updateTask(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'assignee_ids' => 'required|array|min:1',
            'assignee_ids.*' => 'exists:users,id',
            'priority_id' => 'required|exists:task_priorities,id',
            'category_id' => 'required|exists:task_categories,id',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|integer|min:0',
            'nature' => 'required|in:one_time,recurring',
            'recurrence_frequency' => 'required_if:nature,recurring|in:daily,weekly,monthly',
            'reminder_time' => 'nullable',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($taskId);
            
            // Check permissions
            $user = auth()->user();
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
                    if (!$task->isAssignedTo($user) && $task->assigned_by_user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to update this task.'
                        ], 403);
                    }
                }
            }

            // Get old assignees before updating
            $oldAssigneeIds = $task->assignees->pluck('id')->toArray();
            $oldPrimaryAssigneeId = $task->assigned_to_user_id;

            $isRecurring = $request->nature === 'recurring';
            $isRecurringActive = $isRecurring ? 1 : 0;
            $natureOfTask = $request->nature === 'recurring' ? $request->recurrence_frequency : 'one_time';
            $primaryAssigneeId = !empty($request->assignee_ids) ? $request->assignee_ids[0] : null;
            
            if (!$primaryAssigneeId) {
                throw new \Exception('No assignee selected');
            }
            
            $reminderTime = null;
            if ($request->reminder_time && $request->reminder_time != '') {
                try {
                    $reminderTime = date('Y-m-d H:i:s', strtotime($request->reminder_time));
                } catch (\Exception $e) {
                    LogFacade::error('Failed to parse reminder time: ' . $e->getMessage());
                    $reminderTime = null;
                }
            }
            
            $task->update([
                'project_id' => $request->project_id ? $request->project_id : null,
                'title' => $request->title,
                'description' => $request->description,
                'assigned_to_user_id' => $primaryAssigneeId,
                'priority_id' => $request->priority_id,
                'category_id' => $request->category_id,
                'due_date' => $request->due_date ? $request->due_date : null,
                'estimated_hours' => $request->estimated_hours ? $request->estimated_hours : null,
                'reminder_time' => $reminderTime,
                'nature_of_task' => $natureOfTask,
                'is_recurring' => $isRecurring,
                'is_recurring_active' => $isRecurringActive,
            ]);

            // Update multiple assignees
            $task->syncAssignees($request->assignee_ids, auth()->id());
            
            // Reload task with relationships
            $task->load(['assignees', 'assignedTo', 'assignedBy']);

            // Check if assignees have changed and send notifications to new assignees
            $newAssigneeIds = $request->assignee_ids;
            $newlyAssignedIds = array_diff($newAssigneeIds, $oldAssigneeIds);
            
            // Also check if primary assignee changed
            if ($oldPrimaryAssigneeId != $primaryAssigneeId && !in_array($primaryAssigneeId, $oldAssigneeIds)) {
                if (!in_array($primaryAssigneeId, $newlyAssignedIds)) {
                    $newlyAssignedIds[] = $primaryAssigneeId;
                }
            }
            
            // Remove duplicates
            $newlyAssignedIds = array_unique($newlyAssignedIds);
            
            // Send assignment notifications only to newly assigned users
            if (!empty($newlyAssignedIds)) {
                // Reload task with all relationships for email
                $task->refresh();
                $task->load(['priority', 'status', 'project', 'assignedTo', 'assignedBy', 'assignees']);
                
                // Send notification only to new assignees
                $this->emailService->sendTaskAssignedNotificationToUsers($task, $newlyAssignedIds);
            }

            // Handle new attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $path = $attachment->store('attachments');
                    
                    Attachment::create([
                        'task_id' => $task->id,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_size' => $attachment->getSize(),
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            Log::createLog(auth()->id(), 'update_task', "Updated task: {$task->title}");
            $this->emailService->sendTaskUpdatedNotification($task, 'Task Details Updated');

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully!',
                'task' => $task->load(['project', 'assignedTo', 'status', 'priority', 'category', 'assignees'])
            ]);

        } catch (\Exception $e) {
            LogFacade::error('Task update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a task
     */
    public function deleteTask($taskId)
    {
        try {
            $task = Task::findOrFail($taskId);
            
            // Check permissions
            if (!auth()->user()->isSuperAdmin() && $task->assigned_by_user_id !== auth()->id()) {
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
            
            // Check if task is already approved
            if ($task->is_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change status of an approved task.'
                ], 400);
            }
            
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
                    if (!$task->isAssignedTo($user) && $task->assigned_by_user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to update this task.'
                        ], 403);
                    }
                }
            }

            $oldStatus = $task->status;
            $task->update(['status_id' => $request->status_id]);
            $task->load('status');
            
            $newStatus = $task->status;

            Log::createLog(auth()->id(), 'update_task_status', 
                "Changed task '{$task->title}' status from " . ($oldStatus ? $oldStatus->name : 'No Status') . " to {$newStatus->name}");

            $this->emailService->sendTaskStatusChangedNotification($task, $oldStatus, $newStatus);

            // Process recurring task if status is "Complete"
            if ($newStatus->name === 'Complete') {
                $recurringService = new RecurringTaskService();
                $recurringService->processRecurringTask($task);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully!',
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
     * Update task priority
     */
    public function updateTaskPriority(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'priority_id' => 'required|exists:task_priorities,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($taskId);
            $task->update(['priority_id' => $request->priority_id]);

            Log::createLog(auth()->id(), 'update_task_priority', "Changed task '{$task->title}' priority");
            $this->emailService->sendTaskUpdatedNotification($task, 'Task Priority Updated');

            return response()->json([
                'success' => true,
                'message' => 'Task priority updated successfully!',
                'task' => $task->load(['priority'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task priority: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update task category
     */
    public function updateTaskCategory(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:task_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($taskId);
            $task->update(['category_id' => $request->category_id]);

            Log::createLog(auth()->id(), 'update_task_category', "Changed task '{$task->title}' category");
            $this->emailService->sendTaskUpdatedNotification($task, 'Task Category Updated');

            return response()->json([
                'success' => true,
                'message' => 'Task category updated successfully!',
                'task' => $task->load(['category'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop recurring task
     */
    public function stopRecurringTask($taskId)
    {
        try {
            $task = Task::findOrFail($taskId);
            
            if (in_array($task->nature_of_task, ['daily', 'weekly', 'monthly', 'until_stop'])) {
                DB::transaction(function () use ($task) {
                    $task->update(['is_recurring_active' => 0]);
                    
                    $childTasks = Task::where('parent_task_id', $task->id)
                        ->where('is_recurring_active', 1)
                        ->get();
                    
                    foreach ($childTasks as $childTask) {
                        $childTask->update(['is_recurring_active' => 0]);
                    }
                    
                    Log::createLog(auth()->id(), 'stop_recurring_task', 
                        "Stopped recurring task: {$task->title} and {$childTasks->count()} child tasks");
                });
                
                return response()->json([
                    'success' => true,
                    'message' => 'Recurring task generation stopped successfully! All related tasks have been deactivated.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'This task is not a recurring task.'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop recurring task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clone a task
     */
    public function cloneTask(Request $request, $taskId)
    {
        try {
            $originalTask = Task::with('assignees')->findOrFail($taskId);
            
            // Validate due date
            $validator = Validator::make($request->all(), [
                'due_date' => [
                    'required',
                    'date',
                    'after:today',
                    function ($attribute, $value, $fail) use ($originalTask) {
                        // Check if due date is different from original task
                        if ($originalTask->due_date) {
                            $originalDueDate = \Carbon\Carbon::parse($originalTask->due_date)->format('Y-m-d');
                            $newDueDate = \Carbon\Carbon::parse($value)->format('Y-m-d');
                            
                            if ($originalDueDate === $newDueDate) {
                                $fail('The due date must be different from the original task due date.');
                            }
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $clonedTask = Task::create([
                'project_id' => $originalTask->project_id,
                'title' => $originalTask->title . ' (Copy)',
                'description' => $originalTask->description,
                'assigned_to_user_id' => $originalTask->assigned_to_user_id,
                'assigned_by_user_id' => auth()->id(),
                'priority_id' => $originalTask->priority_id,
                'category_id' => $originalTask->category_id,
                'status_id' => TaskStatus::where('name', 'Pending')->first()->id,
                'due_date' => $request->due_date,
                'estimated_hours' => $originalTask->estimated_hours,
                'reminder_time' => $originalTask->reminder_time,
                'notes' => $originalTask->notes,
                'nature_of_task' => $originalTask->nature_of_task,
                'is_recurring' => $originalTask->is_recurring,
                'is_recurring_active' => $originalTask->is_recurring_active,
            ]);

            // Clone assignees
            $assigneeIds = [];
            if ($originalTask->assignees && $originalTask->assignees->count() > 0) {
                $assigneeIds = $originalTask->assignees->pluck('id')->toArray();
                $clonedTask->syncAssignees($assigneeIds, auth()->id());
            } elseif ($originalTask->assigned_to_user_id) {
                // If no assignees relationship but has primary assignee
                $assigneeIds = [$originalTask->assigned_to_user_id];
                $clonedTask->syncAssignees($assigneeIds, auth()->id());
            }

            // Clone attachments
            if ($originalTask->attachments && $originalTask->attachments->count() > 0) {
                foreach ($originalTask->attachments as $attachment) {
                    Attachment::create([
                        'task_id' => $clonedTask->id,
                        'file_path' => $attachment->file_path,
                        'file_name' => $attachment->file_name,
                        'file_size' => $attachment->file_size,
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            // Log the clone
            Log::createLog(auth()->id(), 'clone_task', "Cloned task: {$originalTask->title} to {$clonedTask->title}");

            // Send email notifications to all assignees
            if (!empty($assigneeIds)) {
                $clonedTask->load(['priority', 'status', 'project', 'assignedTo', 'assignedBy', 'assignees']);
                $this->emailService->sendTaskAssignedNotificationToUsers($clonedTask, $assigneeIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task cloned successfully!',
                'task' => $clonedTask->load(['project', 'assignedTo', 'status', 'priority', 'category', 'assignees'])
            ]);

        } catch (\Exception $e) {
            LogFacade::error('Task clone failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clone task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task for editing
     */
    public function getTask($taskId)
    {
        try {
            $task = Task::with(['project', 'assignees', 'status', 'priority', 'category'])->findOrFail($taskId);
            
            return response()->json([
                'success' => true,
                'task' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
            'status_id' => 'required|exists:task_statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tasks = Task::whereIn('id', $request->task_ids)->get();
            $updatedCount = 0;
            $skippedCount = 0;
            
            foreach ($tasks as $task) {
                if ($task->is_approved) {
                    $skippedCount++;
                    continue;
                }
                
                $oldStatusId = $task->status_id;
                $task->status_id = $request->status_id;
                $task->save();
                $updatedCount++;
                
                Log::createLog(auth()->id(), 'bulk_update_status', 
                    "Bulk updated task '{$task->title}' status from {$oldStatusId} to {$request->status_id}");
            }
            
            $message = "Successfully updated {$updatedCount} task(s) status.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} task(s) were skipped (already approved).";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating task status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update priority
     */
    public function bulkUpdatePriority(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
            'priority_id' => 'required|exists:task_priorities,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tasks = Task::whereIn('id', $request->task_ids)->get();
            $updatedCount = 0;
            
            foreach ($tasks as $task) {
                $oldPriorityId = $task->priority_id;
                $task->priority_id = $request->priority_id;
                $task->save();
                $updatedCount++;
                
                Log::createLog(auth()->id(), 'bulk_update_priority', 
                    "Bulk updated task '{$task->title}' priority from {$oldPriorityId} to {$request->priority_id}");
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} task(s) priority."
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating task priority: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update assignee
     */
    public function bulkUpdateAssignee(Request $request)
    {
        // Support both single user_id (backward compatibility) and multiple assignee_ids
        $validator = Validator::make($request->all(), [
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
            'user_id' => 'sometimes|exists:users,id',
            'assignee_ids' => 'sometimes|array',
            'assignee_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Determine assignee IDs - prefer assignee_ids array, fallback to user_id
        $assigneeIds = [];
        if ($request->has('assignee_ids') && is_array($request->assignee_ids) && count($request->assignee_ids) > 0) {
            $assigneeIds = $request->assignee_ids;
        } elseif ($request->has('user_id')) {
            $assigneeIds = [$request->user_id];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Please provide either user_id or assignee_ids.'
            ], 422);
        }

        if (empty($assigneeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least one assignee.'
            ], 422);
        }

        try {
            $tasks = Task::whereIn('id', $request->task_ids)->get();
            $updatedCount = 0;
            $primaryAssigneeId = $assigneeIds[0]; // First assignee is primary
            
            foreach ($tasks as $task) {
                $oldAssigneeId = $task->assigned_to_user_id;
                $oldAssigneeIds = $task->assignees->pluck('id')->toArray();
                
                // Update primary assignee
                $task->assigned_to_user_id = $primaryAssigneeId;
                $task->save();
                
                // Sync all assignees (multiple)
                $task->syncAssignees($assigneeIds, auth()->id());
                $task->load(['assignedTo', 'assignedBy', 'assignees', 'priority', 'status', 'project']);
                
                // Check if assignees changed and send notification to new assignees
                $newAssigneeIds = array_diff($assigneeIds, $oldAssigneeIds);
                if (!empty($newAssigneeIds)) {
                    $this->emailService->sendTaskAssignedNotificationToUsers($task, array_values($newAssigneeIds));
                }
                
                $updatedCount++;
                
                $assigneeNames = implode(', ', $assigneeIds);
                Log::createLog(auth()->id(), 'bulk_update_assignee', 
                    "Bulk updated task '{$task->title}' assignees to: {$assigneeNames}");
            }
            
            $assigneeText = count($assigneeIds) > 1 ? 'assignees' : 'assignee';
            return response()->json([
                'success' => true,
                'message' => "Successfully assigned {$updatedCount} task(s) to {$assigneeText}."
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating task assignee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete tasks
     */
    public function bulkDeleteTasks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tasks = Task::whereIn('id', $request->task_ids)->get();
            $deletedCount = 0;
            
            foreach ($tasks as $task) {
                $taskTitle = $task->title;
                Log::createLog(auth()->id(), 'bulk_delete_task', "Bulk deleted task '{$taskTitle}'");
                $task->delete();
                $deletedCount++;
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} task(s)."
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve task
     */
    public function approveTask(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($taskId);
            
            // Check permissions
            $user = auth()->user();
            if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager() && $task->assigned_by_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to approve this task.'
                ], 403);
            }
            
            // Check if task is completed
            if (!$task->status || $task->status->name !== 'Complete') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed tasks can be approved.'
                ], 400);
            }
            
            $task->update(['is_approved' => true]);
            
            Log::createLog(auth()->id(), 'task_approved', 
                "Approved completed task '{$task->title}'" . 
                ($request->comments ? " with comments: {$request->comments}" : ''));
            
            return response()->json([
                'success' => true,
                'message' => 'Task has been approved and marked as completed.',
                'task' => $task->load(['status'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revisit task
     */
    public function revisitTask(Request $request, $taskId)
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::findOrFail($taskId);
            
            // Check permissions
            $user = auth()->user();
            if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager() && $task->assigned_by_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to mark this task for revisit.'
                ], 403);
            }
            
            // Check if task is completed
            if (!$task->status || $task->status->name !== 'Complete') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed tasks can be marked for revisit.'
                ], 400);
            }
            
            $needsRevisitStatus = TaskStatus::where('name', 'Needs Revisit')->first();
            
            if (!$needsRevisitStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Needs Revisit status not found. Please contact system administrator.'
                ], 404);
            }
            
            $task->update(['status_id' => $needsRevisitStatus->id]);
            
            Log::createLog(auth()->id(), 'task_revisit', 
                "Marked task '{$task->title}' for revisit" . 
                ($request->comments ? " with comments: {$request->comments}" : ''));
            
            $adminName = auth()->user()->name;
            $this->emailService->sendTaskRevisitNotification($task, $request->comments, $adminName);
            
            return response()->json([
                'success' => true,
                'message' => 'Task has been marked for revisit. Email notification sent to assignees.',
                'task' => $task->load(['status'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark task for revisit: ' . $e->getMessage()
            ], 500);
        }
    }
}


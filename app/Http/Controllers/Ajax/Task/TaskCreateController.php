<?php

namespace App\Http\Controllers\Ajax\Task;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\TaskCategory;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskCreateController extends Controller
{
    protected $emailService;

    public function __construct()
    {
        $this->emailService = new EmailNotificationService();
    }

    /**
     * Display the task create page
     */
    public function index()
    {
        return view('ajax.task.task-create');
    }

    /**
     * Get dropdown data for create form
     */
    public function getDropdownData()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            $projects = Project::all();
        } elseif ($user->isManager()) {
            $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
            $projects = Project::whereIn('created_by_user_id', $teamMemberIds)->get();
        } else {
            $projects = Project::where('created_by_user_id', $user->id)->get();
        }
        
        $priorities = TaskPriority::orderBy('name')->get();
        $categories = TaskCategory::orderBy('name')->get();
        $statuses = TaskStatus::orderBy('name')->get();
        
        if ($user->isSuperAdmin()) {
            $users = User::orderBy('name')->get();
        } elseif ($user->isAdmin()) {
            $users = User::orderBy('name')->get();
        } elseif ($user->isManager()) {
            $users = User::orderBy('name')->get();
        } else {
            $users = collect([$user]);
        }

        return response()->json([
            'success' => true,
            'projects' => $projects,
            'priorities' => $priorities,
            'categories' => $categories,
            'statuses' => $statuses,
            'users' => $users,
        ]);
    }

    /**
     * Create a new task
     */
    public function createTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority_id' => 'required|exists:task_priorities,id',
            'category_id' => 'required|exists:task_categories,id',
            'status_id' => 'nullable|exists:task_statuses,id',
            'duration' => 'nullable|integer|min:1',
            'due_date' => 'nullable|date|after:today',
            'assigned_to_user_id' => 'required|exists:users,id',
            'nature_of_task' => 'required|in:daily,weekly,monthly,until_stop',
            'notes' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $isRecurring = in_array($request->nature_of_task, ['weekly', 'monthly', 'until_stop']);
            $isRecurringActive = $isRecurring ? 1 : 0;

            $task = Task::create([
                'project_id' => $request->project_id,
                'title' => $request->title,
                'description' => $request->description,
                'priority_id' => $request->priority_id,
                'category_id' => $request->category_id,
                'status_id' => $request->status_id ?: $this->getDefaultStatusId(),
                'duration' => $request->duration,
                'due_date' => $request->due_date,
                'assigned_to_user_id' => $request->assigned_to_user_id,
                'assigned_by_user_id' => auth()->id(),
                'nature_of_task' => $request->nature_of_task,
                'is_recurring' => $isRecurring,
                'is_recurring_active' => $isRecurringActive,
                'notes' => $request->notes,
            ]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $path = $attachment->store('attachments');
                    
                    $task->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                        'uploaded_by_user_id' => auth()->id(),
                    ]);
                }
            }

            Log::createLog(auth()->id(), 'create_task', "Created task: {$task->title}");

            $this->emailService->sendTaskCreatedNotification($task);
            if ($task->assignedTo) {
                $this->emailService->sendTaskAssignedNotification($task);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully.',
                'task' => $task->load(['project', 'assignedTo', 'status', 'priority', 'category'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new priority
     */
    public function addNewPriority(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:task_priorities,name',
            'color' => 'required|string|in:primary,secondary,success,danger,warning,info,dark',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $priority = TaskPriority::create([
                'name' => $request->name,
                'color' => $request->color,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Priority added successfully!',
                'priority' => $priority
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add priority: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new category
     */
    public function addNewCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:task_categories,name',
            'icon' => 'required|string|max:255',
            'color' => 'required|string|in:primary,secondary,success,danger,warning,info,dark',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = TaskCategory::create([
                'name' => $request->name,
                'icon' => $request->icon,
                'color' => $request->color,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category added successfully!',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new status
     */
    public function addNewStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:task_statuses,name',
            'color' => 'required|string|in:primary,secondary,success,danger,warning,info,dark',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->is_default) {
                TaskStatus::where('is_default', true)->update(['is_default' => false]);
            }

            $status = TaskStatus::create([
                'name' => $request->name,
                'color' => $request->color,
                'is_default' => $request->is_default ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status added successfully!',
                'status' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add status: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getDefaultStatusId()
    {
        $pendingStatus = TaskStatus::where('name', 'Pending')->first();
        return $pendingStatus ? $pendingStatus->id : null;
    }
}


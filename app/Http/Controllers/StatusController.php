<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Project;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Check if user has permission to view statuses
        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('view_all_statuses');
        $canViewOwn = $user->isSuperAdmin() || $user->hasPermission('view_own_statuses');
        
        if (!$canViewAll && !$canViewOwn) {
            abort(403, 'You do not have permission to view statuses.');
        }

        $query = Status::with(['project'])
            ->withCount('leads');
        
        // If user can only view own statuses, filter by projects they created
        if (!$canViewAll && $canViewOwn) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('created_by_user_id', $user->id);
            });
        }

        // Filter by project
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort_field', 'order');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $statuses = $query->paginate($perPage);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'statuses' => $statuses->items(),
                'pagination' => [
                    'current_page' => $statuses->currentPage(),
                    'last_page' => $statuses->lastPage(),
                    'per_page' => $statuses->perPage(),
                    'total' => $statuses->total(),
                ]
            ]);
        }

        $projects = Project::orderBy('title')->get();
        return view('statuses.index', compact('projects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        // Check permission
        if (!$user->isSuperAdmin() && !$user->hasPermission('create_status')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create statuses.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['project_id', 'name', 'color', 'order']);
        $data['color'] = $data['color'] ?? 'secondary';
        $data['order'] = isset($data['order']) && $data['order'] !== '' ? (int)$data['order'] : 0;
        // Handle checkbox - can be 1, 0, true, false, or not present
        $isDefault = $request->input('is_default');
        $data['is_default'] = ($isDefault === '1' || $isDefault === 1 || $isDefault === true || $isDefault === 'true');

        // If this is set as default, unset other defaults for this project
        if ($data['is_default']) {
            Status::where('project_id', $data['project_id'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $status = Status::create($data);

        // Log the action
        Log::createLog(auth()->id(), 'create_status', "Created status: {$status->name} for project #{$status->project_id}");

        $status->load(['project']);

        return response()->json([
            'success' => true,
            'message' => 'Status created successfully.',
            'status' => $status
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = auth()->user();
        
        // Check if user has permission to view statuses
        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('view_all_statuses');
        $canViewOwn = $user->isSuperAdmin() || $user->hasPermission('view_own_statuses');
        
        if (!$canViewAll && !$canViewOwn) {
            abort(403, 'You do not have permission to view statuses.');
        }

        $status = Status::with(['project', 'leads'])->findOrFail($id);
        
        // If user can only view own statuses, check if they created the project
        if (!$canViewAll && $canViewOwn) {
            if ($status->project->created_by_user_id !== $user->id) {
                abort(403, 'You do not have permission to view this status. You can only view statuses for projects you created.');
            }
        }

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        
        // Check permission
        if (!$user->isSuperAdmin() && !$user->hasPermission('edit_status')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit statuses.'
            ], 403);
        }

        $status = Status::with('project')->findOrFail($id);
        
        // If user can only view own statuses, check if they created the project
        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('view_all_statuses');
        if (!$canViewAll) {
            if ($status->project->created_by_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only edit statuses for projects you created.'
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['project_id', 'name', 'color', 'order']);
        // Handle checkbox - can be 1, 0, true, false, or not present
        $isDefault = $request->input('is_default');
        $data['is_default'] = ($isDefault === '1' || $isDefault === 1 || $isDefault === true || $isDefault === 'true');

        // If this is set as default, unset other defaults for this project
        if ($data['is_default']) {
            Status::where('project_id', $data['project_id'])
                ->where('id', '!=', $id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $status->update($data);

        // Log the action
        Log::createLog(auth()->id(), 'update_status', "Updated status: {$status->name} for project #{$status->project_id}");

        $status->load(['project']);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'status' => $status
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        
        // Check permission
        if (!$user->isSuperAdmin() && !$user->hasPermission('delete_status')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete statuses.'
            ], 403);
        }

        $status = Status::with('project')->findOrFail($id);
        
        // If user can only view own statuses, check if they created the project
        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('view_all_statuses');
        if (!$canViewAll) {
            if ($status->project->created_by_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete statuses for projects you created.'
                ], 403);
            }
        }
        $statusName = $status->name;
        $projectId = $status->project_id;

        // Log the action before deletion
        Log::createLog(auth()->id(), 'delete_status', "Deleted status: {$statusName} from project #{$projectId}");

        $status->delete();

        return response()->json([
            'success' => true,
            'message' => 'Status deleted successfully.'
        ]);
    }

    /**
     * Get status data for editing.
     */
    public function edit($id)
    {
        $user = auth()->user();
        
        // Check permission
        if (!$user->isSuperAdmin() && !$user->hasPermission('edit_status')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit statuses.'
            ], 403);
        }

        $status = Status::with(['project'])->findOrFail($id);
        
        // If user can only view own statuses, check if they created the project
        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('view_all_statuses');
        if (!$canViewAll) {
            if ($status->project->created_by_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only edit statuses for projects you created.'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }
}

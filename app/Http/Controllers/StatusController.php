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
        $query = Status::with(['project'])
            ->withCount('leads');

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
        $status = Status::with(['project', 'leads'])->findOrFail($id);

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
        $status = Status::findOrFail($id);

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
        $status = Status::findOrFail($id);
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
        $status = Status::with(['project'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }
}

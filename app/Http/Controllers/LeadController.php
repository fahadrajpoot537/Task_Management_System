<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Lead::with(['project', 'addedBy', 'status']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Filter by project
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $leads = $query->paginate($perPage);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'leads' => $leads->items(),
                'pagination' => [
                    'current_page' => $leads->currentPage(),
                    'last_page' => $leads->lastPage(),
                    'per_page' => $leads->perPage(),
                    'total' => $leads->total(),
                ]
            ]);
        }

        $projects = Project::orderBy('title')->get();
        return view('leads.index', compact('projects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'first_name' => 'required|string|max:255',
            'flg_reference' => 'nullable|string|max:255',
            'sub_reference' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'alternative_phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'postcode' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'status_id' => 'nullable|exists:statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['added_by'] = auth()->id();

        $lead = Lead::create($data);

        // Log the action
        Log::createLog(auth()->id(), 'create_lead', "Created lead: {$lead->first_name} {$lead->last_name}");

        $lead->load(['project', 'addedBy', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'lead' => $lead
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $lead = Lead::with(['project', 'addedBy', 'status', 'activities.createdBy', 'activities.assignedTo'])->findOrFail($id);

        if (request()->ajax() || request()->has('ajax')) {
            return response()->json([
                'success' => true,
                'lead' => $lead,
                'activities' => $lead->activities
            ]);
        }

        // Get users for assignment dropdown
        $users = User::orderBy('name')->get();
        
        return view('leads.show', compact('lead', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'first_name' => 'required|string|max:255',
            'flg_reference' => 'nullable|string|max:255',
            'sub_reference' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'alternative_phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'postcode' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'status_id' => 'nullable|exists:statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $lead->update($request->all());

        // Log the action
        Log::createLog(auth()->id(), 'update_lead', "Updated lead: {$lead->first_name} {$lead->last_name}");

        $lead->load(['project', 'addedBy', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully.',
            'lead' => $lead
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);

        // Log the action
        Log::createLog(auth()->id(), 'delete_lead', "Deleted lead: {$lead->first_name} {$lead->last_name}");

        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully.'
        ]);
    }

    /**
     * Get lead data for editing.
     */
    public function edit($id)
    {
        $lead = Lead::with(['project', 'addedBy', 'status'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'lead' => $lead
        ]);
    }

    /**
     * Get statuses for a specific project.
     */
    public function getStatusesByProject($projectId)
    {
        $statuses = Status::where('project_id', $projectId)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'statuses' => $statuses
        ]);
    }
}

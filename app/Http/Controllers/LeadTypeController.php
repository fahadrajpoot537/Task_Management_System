<?php

namespace App\Http\Controllers;

use App\Models\LeadType;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('view_lead_types')) {
            abort(403, 'You do not have permission to view lead types.');
        }

        $query = LeadType::with('createdBy')
            ->withCount('leads');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort_field', 'order');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);
        $query->orderBy('name', 'asc'); // Secondary sort

        // Pagination
        $perPage = $request->get('per_page', 10);
        $leadTypes = $query->paginate($perPage);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'lead_types' => $leadTypes->items(),
                'pagination' => [
                    'current_page' => $leadTypes->currentPage(),
                    'last_page' => $leadTypes->lastPage(),
                    'per_page' => $leadTypes->perPage(),
                    'total' => $leadTypes->total(),
                ]
            ]);
        }

        return view('lead-types.index', compact('leadTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('create_lead_type')) {
            abort(403, 'You do not have permission to create lead types.');
        }

        return view('lead-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('create_lead_type')) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create lead types.'
                ], 403);
            }
            return back()->with('error', 'You do not have permission to create lead types.');
        }

        // Convert is_active to proper boolean before validation
        // Handle various input formats: true/false, "true"/"false", "1"/"0", 1/0, "on"/"off"
        $isActive = $request->input('is_active');
        if ($isActive === null || $isActive === '') {
            $isActive = true; // Default to true
        } else {
            // Try to convert to boolean using filter_var
            $converted = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($converted === null) {
                // If filter_var returns null, try direct boolean cast
                $isActive = (bool)$isActive;
            } else {
                $isActive = $converted;
            }
        }
        $request->merge(['is_active' => $isActive]);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:lead_types,name',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $leadType = LeadType::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?? '#007bff',
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        // Log the action
        Log::createLog(auth()->id(), 'create_lead_type', "Created lead type: {$leadType->name}");

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead type created successfully.',
                'lead_type' => $leadType
            ]);
        }

        return redirect()->route('lead-types.index')
            ->with('success', 'Lead type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('view_lead_types')) {
            abort(403, 'You do not have permission to view lead types.');
        }

        $leadType = LeadType::with(['createdBy', 'leads'])->findOrFail($id);
        
        return view('lead-types.show', compact('leadType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('edit_lead_type')) {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit lead types.'
                ], 403);
            }
            abort(403, 'You do not have permission to edit lead types.');
        }

        $leadType = LeadType::findOrFail($id);
        
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'lead_type' => $leadType
            ]);
        }
        
        return view('lead-types.edit', compact('leadType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('edit_lead_type')) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit lead types.'
                ], 403);
            }
            return back()->with('error', 'You do not have permission to edit lead types.');
        }

        $leadType = LeadType::findOrFail($id);

        // Convert is_active to proper boolean before validation
        // Handle various input formats: true/false, "true"/"false", "1"/"0", 1/0, "on"/"off"
        $isActive = $request->input('is_active');
        if ($isActive === null || $isActive === '') {
            $isActive = $leadType->is_active; // Keep existing value
        } else {
            // Try to convert to boolean using filter_var
            $converted = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($converted === null) {
                // If filter_var returns null, try direct boolean cast
                $isActive = (bool)$isActive;
            } else {
                $isActive = $converted;
            }
        }
        $request->merge(['is_active' => $isActive]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:lead_types,name,' . $id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $leadType->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?? $leadType->color,
            'order' => $request->order ?? $leadType->order,
            'is_active' => $request->boolean('is_active', $leadType->is_active),
        ]);

        // Log the action
        Log::createLog(auth()->id(), 'update_lead_type', "Updated lead type: {$leadType->name}");

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead type updated successfully.',
                'lead_type' => $leadType
            ]);
        }

        return redirect()->route('lead-types.index')
            ->with('success', 'Lead type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('delete_lead_type')) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete lead types.'
                ], 403);
            }
            return back()->with('error', 'You do not have permission to delete lead types.');
        }

        $leadType = LeadType::findOrFail($id);

        // Check if lead type is being used by any leads
        if ($leadType->leads()->count() > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete lead type. It is being used by ' . $leadType->leads()->count() . ' lead(s).'
                ], 422);
            }
            return back()->with('error', 'Cannot delete lead type. It is being used by ' . $leadType->leads()->count() . ' lead(s).');
        }

        // Log the action
        Log::createLog(auth()->id(), 'delete_lead_type', "Deleted lead type: {$leadType->name}");

        $leadType->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead type deleted successfully.'
            ]);
        }

        return redirect()->route('lead-types.index')
            ->with('success', 'Lead type deleted successfully.');
    }
}

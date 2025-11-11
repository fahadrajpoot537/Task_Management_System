<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'lead_id' => 'required|exists:leads,id',
            'type' => 'required|string|max:255',
            'date' => 'required|date',
            'priority' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'field_1' => 'nullable|string',
            'field_2' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'bcc' => 'nullable|string',
            'cc' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'actioned' => 'nullable|boolean',
            'end_date' => 'nullable|date',
        ];
        
        // Add file validation - handle both file uploads and string values
        if ($request->hasFile('file')) {
            // Files are being uploaded
            $rules['file'] = 'nullable|array';
            $rules['file.*'] = 'nullable|file|max:10240'; // Max 10MB per file
        } else {
            // No files uploaded, allow string (for existing file names)
            $rules['file'] = 'nullable|string|max:255';
        }
        
        // Add document_files validation for Document type
        if ($request->hasFile('document_files')) {
            $rules['document_files'] = 'nullable|array';
            $rules['document_files.*'] = 'nullable|file|max:10240'; // Max 10MB per file
        }
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['description', 'file', 'document_files']); // Remove description and file fields as they need special handling
        $data['created_by'] = auth()->id();

        // Handle file upload for Email type
        if ($request->hasFile('file')) {
            $files = $request->file('file');
            // Check if it's an array or single file
            if (is_array($files)) {
                $fileNames = [];
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = time() . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/activities'), $fileName);
                        $filePath = 'uploads/activities/' . $fileName;
                        $fileNames[] = $filePath;
                    }
                }
                $data['file'] = implode(',', $fileNames); // Store multiple file names as comma-separated string
            } else {
                // Single file
                if ($files->isValid()) {
                    $fileName = time() . '_' . $files->getClientOriginalName();
                    $files->move(public_path('uploads/activities'), $fileName);
                    $filePath = 'uploads/activities/' . $fileName;
                    $data['file'] = $filePath;
                }
            }
        }
        
        // Handle file upload for Document type
        // Check all files to see if document_files exist
        $allFiles = $request->allFiles();
        $documentFiles = null;
        
        // Check if document_files exists in all files
        if (isset($allFiles['document_files'])) {
            $documentFiles = $allFiles['document_files'];
        } elseif ($request->hasFile('document_files')) {
            $documentFiles = $request->file('document_files');
        }
        
        if ($documentFiles) {
            $fileNames = [];
            
            // Handle both array and single file
            if (is_array($documentFiles)) {
                foreach ($documentFiles as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = time() . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/activities'), $fileName);
                        $filePath = 'uploads/activities/' . $fileName;
                        $fileNames[] = $filePath;
                    }
                }
            } else {
                // Single file
                if ($documentFiles && $documentFiles->isValid()) {
                    $fileName = time() . '_' . $documentFiles->getClientOriginalName();
                    $documentFiles->move(public_path('uploads/activities'), $fileName);
                    $filePath = 'uploads/activities/' . $fileName;
                    $fileNames[] = $filePath;
                }
            }
            
            if (!empty($fileNames)) {
                $data['file'] = implode(',', $fileNames); // Store multiple file names as comma-separated string
            }
        }

        $activity = Activity::create($data);

        // Log the action
        Log::createLog(auth()->id(), 'create_activity', "Created activity: {$activity->type} for lead #{$activity->lead_id}");

        $activity->load(['lead', 'createdBy']);

        return response()->json([
            'success' => true,
            'message' => 'Activity created successfully.',
            'activity' => $activity
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $activity = Activity::with(['lead', 'createdBy', 'assignedTo', 'lead.project', 'lead.status', 'lead.addedBy'])->findOrFail($id);

        // If it's an AJAX request, return JSON
        if (request()->ajax() || request()->has('ajax')) {
            return response()->json([
                'success' => true,
                'activity' => $activity
            ]);
        }

        // For Email type, return the view page
        if ($activity->type === 'Email') {
            return view('activities.view-email', compact('activity'));
        }

        // For other types, return JSON
        return response()->json([
            'success' => true,
            'activity' => $activity
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);

        $rules = [
            'lead_id' => 'required|exists:leads,id',
            'type' => 'required|string|max:255',
            'date' => 'required|date',
            'priority' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'field_1' => 'nullable|string',
            'field_2' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'bcc' => 'nullable|string',
            'cc' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'actioned' => 'nullable|boolean',
            'end_date' => 'nullable|date',
        ];
        
        // Add file validation - handle both file uploads and string values
        if ($request->hasFile('file')) {
            // Files are being uploaded
            $rules['file'] = 'nullable|array';
            $rules['file.*'] = 'nullable|file|max:10240'; // Max 10MB per file
        } else {
            // No files uploaded, allow string (for existing file names)
            $rules['file'] = 'nullable|string|max:255';
        }
        
        // Add document_files validation for Document type
        if ($request->hasFile('document_files')) {
            $rules['document_files'] = 'nullable|array';
            $rules['document_files.*'] = 'nullable|file|max:10240'; // Max 10MB per file
        }
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['description', 'file', 'document_files']); // Remove description and file fields as they need special handling
        
        // Handle file upload for Email type
        if ($request->hasFile('file')) {
            // Delete old files if they exist
            if ($activity->file) {
                $oldFiles = explode(',', $activity->file);
                foreach ($oldFiles as $oldFile) {
                    $oldFile = trim($oldFile);
                    // Handle both full paths and relative paths
                    if (strpos($oldFile, 'uploads/activities/') === 0) {
                        $filePath = public_path($oldFile);
                    } else {
                        $filePath = public_path('uploads/activities/' . $oldFile);
                    }
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
            
            $files = $request->file('file');
            // Check if it's an array or single file
            if (is_array($files)) {
                $fileNames = [];
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = time() . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/activities'), $fileName);
                        $fileNames[] = $fileName;
                    }
                }
                $data['file'] = implode(',', $fileNames); // Store multiple file names as comma-separated string
            } else {
                // Single file
                if ($files->isValid()) {
                    $fileName = time() . '_' . $files->getClientOriginalName();
                    $files->move(public_path('uploads/activities'), $fileName);
                    $data['file'] = $fileName;
                }
            }
        }
        
        // Handle file upload for Document type
        // Check all files to see if document_files exist
        $allFiles = $request->allFiles();
        $documentFiles = null;
        
        // Check if document_files exists in all files
        if (isset($allFiles['document_files'])) {
            $documentFiles = $allFiles['document_files'];
        } elseif ($request->hasFile('document_files')) {
            $documentFiles = $request->file('document_files');
        }
        
        if ($documentFiles) {
            // Delete old files if they exist
            if ($activity->file) {
                $oldFiles = explode(',', $activity->file);
                foreach ($oldFiles as $oldFile) {
                    $oldFile = trim($oldFile);
                    // Handle both full paths and relative paths
                    if (strpos($oldFile, 'uploads/activities/') === 0) {
                        $filePath = public_path($oldFile);
                    } else {
                        $filePath = public_path('uploads/activities/' . $oldFile);
                    }
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
            
            $fileNames = [];
            
            // Handle both array and single file
            if (is_array($documentFiles)) {
                foreach ($documentFiles as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = time() . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/activities'), $fileName);
                        $filePath = 'uploads/activities/' . $fileName;
                        $fileNames[] = $filePath;
                    }
                }
            } else {
                // Single file
                if ($documentFiles && $documentFiles->isValid()) {
                    $fileName = time() . '_' . $documentFiles->getClientOriginalName();
                    $documentFiles->move(public_path('uploads/activities'), $fileName);
                    $filePath = 'uploads/activities/' . $fileName;
                    $fileNames[] = $filePath;
                }
            }
            
            if (!empty($fileNames)) {
                $data['file'] = implode(',', $fileNames); // Store multiple file names as comma-separated string
            }
        }
        
        $activity->update($data);

        // Log the action
        Log::createLog(auth()->id(), 'update_activity', "Updated activity: {$activity->type} for lead #{$activity->lead_id}");

        $activity->load(['lead', 'createdBy']);

        return response()->json([
            'success' => true,
            'message' => 'Activity updated successfully.',
            'activity' => $activity
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);

        // Log the action
        Log::createLog(auth()->id(), 'delete_activity', "Deleted activity: {$activity->type} for lead #{$activity->lead_id}");

        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity deleted successfully.'
        ]);
    }

    /**
     * Get activity data for editing.
     */
    public function edit($id)
    {
        $activity = Activity::with(['lead', 'createdBy', 'assignedTo'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'activity' => $activity
        ]);
    }
}

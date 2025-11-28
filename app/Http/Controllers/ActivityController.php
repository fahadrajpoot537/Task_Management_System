<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use App\Models\Log;
use App\Mail\ActivityEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $activities = Activity::with(['lead', 'createdBy', 'assignedTo', 'lead.project'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('activities.index', compact('activities'));
    }

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
            'to' => 'nullable|string',
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

        // Get field_2 BEFORE using except() to ensure it's captured
        $field2Value = $request->input('field_2');
        if (is_null($field2Value)) {
            $field2Value = '';
        }
        
        $data = $request->except(['description', 'file', 'document_files']); // Remove description and file fields as they need special handling
        $data['created_by'] = auth()->id();
        
        // CRITICAL: Always set field_2 explicitly (even if empty) to ensure it's included
        $data['field_2'] = $field2Value;

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

        // For Email type: Send email first, then store in DB only if successful
        if ($data['type'] === 'Email') {
            // Validate email body is not empty
            $emailBody = $data['field_2'] ?? '';
            if (empty($emailBody) || trim($emailBody) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Email body cannot be empty. Please enter a message.',
                    'errors' => ['field_2' => 'Email body is required.']
                ], 422);
            }
            
            try {
                // Generate message ID for email threading and replies
                $messageId = $this->generateMessageId();
                
                // Ensure field_2 is in data before sending email
                $data['field_2'] = $emailBody;
                
                // Send email first
                $emailSent = $this->sendActivityEmailBeforeStore($data, $messageId);
                
                if ($emailSent) {
                    // Email sent successfully, now store in database with message_id
                    $data['message_id'] = $messageId;
                    
                    // Ensure field_2 is still in the data array before creating (double check)
                    $data['field_2'] = $emailBody;
                    
                    $activity = Activity::create($data);
                    
                    // Log the action
                    Log::createLog(auth()->id(), 'create_activity', "Created and sent email activity: {$activity->type} for lead #{$activity->lead_id}");
                    
                    $activity->load(['lead', 'createdBy']);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Email sent and activity created successfully.',
                        'activity' => $activity
                    ]);
                } else {
                    // Email failed to send
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to send email. Activity was not created.',
                        'errors' => ['email' => 'Email could not be sent. Please check your email configuration and try again.']
                    ], 422);
                }
            } catch (\Exception $e) {
                LogFacade::error('Error sending email activity', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email: ' . $e->getMessage(),
                    'errors' => ['email' => $e->getMessage()]
                ], 422);
            }
        } else {
            // For non-email activities, create normally
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

        // For Email, Dropbox Reply Email, or Dropbox Email type, return the view page
        if ($activity->type === 'Email' || $activity->type === 'Dropbox Reply Email' || $activity->type === 'Dropbox Email') {
            return view('activities.view-email', compact('activity'));
        }

        // For other types, return JSON
        return response()->json([
            'success' => true,
            'activity' => $activity
        ]);
    }

    /**
     * Reply to an email activity
     */
    public function reply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activity_id' => 'required|exists:activities,id',
            'lead_id' => 'required|exists:leads,id',
            'message_id' => 'required|string',
            'to' => 'required|string',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240', // Max 10MB per file
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get the original activity
            $originalActivity = Activity::findOrFail($request->activity_id);
            
            // Get authenticated user
            $user = auth()->user();
            
            // Generate a new unique message_id for the reply
            $newMessageId = $this->generateMessageId();
            
            // Prepare data for the reply activity
            $data = [
                'lead_id' => $request->lead_id,
                'type' => 'Email',
                'date' => now()->format('Y-m-d'),
                'field_1' => $request->subject, // Subject
                'field_2' => $request->body, // Body
                'to' => $request->to,
                'cc' => $request->cc,
                'bcc' => $request->bcc,
                'email' => $user->email ?? config('mail.from.address'), // Sender email
                'message_id' => $newMessageId, // New unique message_id for this reply
                'in_reply_to' => $request->message_id, // Reference to original message for threading
                'created_by' => $user->id,
                'actioned' => 1, // Mark as sent
            ];

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
                $fileNames = [];
                
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $fileName = time() . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/activities'), $fileName);
                        $filePath = 'uploads/activities/' . $fileName;
                        $fileNames[] = $filePath;
                    }
                }
                
                if (!empty($fileNames)) {
                    $data['file'] = implode(',', $fileNames);
                }
            }

            // Send email first with the new message_id and in_reply_to for threading
            $inReplyTo = $data['in_reply_to'] ?? null;
            $emailSent = $this->sendActivityEmailBeforeStore($data, $newMessageId, $inReplyTo);
            
            if ($emailSent) {
                // Email sent successfully, now store in database
                $replyActivity = Activity::create($data);
                
                // Log the action
                Log::createLog(auth()->id(), 'reply_activity', "Replied to email activity #{$originalActivity->id} for lead #{$replyActivity->lead_id}");
                
                return response()->json([
                    'success' => true,
                    'message' => 'Reply sent successfully.',
                    'activity' => $replyActivity
                ]);
            } else {
                // Email failed to send
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send reply email. Please check your email configuration and try again.'
                ], 422);
            }
        } catch (\Exception $e) {
            LogFacade::error('Error replying to email activity', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reply: ' . $e->getMessage()
            ], 500);
        }
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
            'to' => 'nullable|string',
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

    /**
     * Import activities from CSV/Excel
     * Activities file should have Reference column to match with lead's flg_reference
     */
    public function importActivities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        
        // Handle Excel files
        if (in_array($extension, ['xlsx', 'xls'])) {
            // For Excel files, we'll use a simple CSV conversion approach
            // You may want to use PhpSpreadsheet for better Excel support
            return response()->json([
                'success' => false,
                'message' => 'Excel files are not yet supported. Please convert to CSV format.'
            ], 422);
        }
        
        // Read CSV file - use PHP's built-in CSV parser which handles quoted fields
        $data = [];
        $fileHandle = fopen($file->getRealPath(), 'r');
        
        // Read CSV with proper handling of quoted multi-line fields
        while (($row = fgetcsv($fileHandle, 0, ',', '"', '\\')) !== false) {
            if ($row !== null) {
                $data[] = $row;
            }
        }
        fclose($fileHandle);
        
        // Remove BOM if present
        if (!empty($data[0][0])) {
            $data[0][0] = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $data[0][0]);
        }
        
        $headers = array_shift($data); // Remove header row
        $headers = array_map('trim', $headers);
        $imported = 0;
        $errors = [];
        $processedRows = []; // Track processed rows to avoid duplicates in same import
        
        // Log headers for debugging (first import only)
        LogFacade::info('Activity Import - CSV Headers:', $headers);

        foreach ($data as $index => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Pad row to match headers length if needed
            while (count($row) < count($headers)) {
                $row[] = '';
            }
            
            // Trim row values
            $row = array_map('trim', $row);
            
            $rowData = array_combine($headers, $row);
            
            // Debug: Log first few rows for troubleshooting
            if ($index < 3) {
                LogFacade::info("Activity Import - Row " . ($index + 2) . " data:", $rowData);
            }
            
            // Create a unique key for this row to detect duplicates in the same CSV
            $reference = !empty($rowData['Reference']) ? trim($rowData['Reference']) : '';
            $activityType = !empty($rowData['ActivityType']) ? trim($rowData['ActivityType']) : '';
            $activityDateTime = !empty($rowData['ActivityDateTime']) ? trim($rowData['ActivityDateTime']) : '';
            $rowKey = md5($reference . '|' . $activityType . '|' . $activityDateTime);
            
            // Check if we've already processed this exact row in this import
            if (isset($processedRows[$rowKey])) {
                $errors[] = "Row " . ($index + 2) . ": Duplicate row in CSV file (same Reference, Type, and DateTime as row " . ($processedRows[$rowKey] + 2) . ") - skipping";
                continue;
            }
            $processedRows[$rowKey] = $index;
            
            // Check if this looks like a continuation row (no Reference but has other data)
            $hasReference = !empty($rowData['Reference']) && trim($rowData['Reference']) !== '';
            $hasActivityType = !empty($rowData['ActivityType']) && trim($rowData['ActivityType']) !== '';
            
            // If no Reference and no ActivityType, this is likely a continuation row - skip it
            if (!$hasReference && !$hasActivityType) {
                // Check if it has any meaningful data
                $hasAnyData = false;
                foreach ($rowData as $key => $value) {
                    if (!empty(trim($value)) && strlen(trim($value)) > 3) {
                        $hasAnyData = true;
                        break;
                    }
                }
                if (!$hasAnyData) {
                    continue; // Skip completely empty rows
                }
                // If it has data but no Reference/Type, it's likely a continuation - skip
                $errors[] = "Row " . ($index + 2) . ": Appears to be a continuation row (no Reference or ActivityType) - skipping";
                continue;
            }
            
            // Find or create lead by Reference (flg_reference)
            $lead = null;
            $reference = !empty($rowData['Reference']) ? trim($rowData['Reference']) : null;
            
            // Skip if Reference is empty or looks like invalid data
            if (empty($reference) || 
                strlen($reference) > 100 || 
                strpos($reference, "\n") !== false ||
                strpos($reference, "\r") !== false ||
                // Skip if it looks like it's picking up text from other columns
                stripos($reference, 'Telephone:') !== false ||
                stripos($reference, 'Email:') !== false ||
                stripos($reference, 'Dear ') !== false ||
                stripos($reference, 'Yours ') !== false ||
                stripos($reference, 'This is a') !== false ||
                stripos($reference, 'You can') !== false ||
                stripos($reference, 'Add templates') !== false ||
                stripos($reference, 'Letters can') !== false ||
                stripos($reference, 'This feature') !== false ||
                // Skip if it's just whitespace or common text
                trim($reference) === '' ||
                $reference === 'Test Test' ||
                preg_match('/^\d{1,2}[a-z]{2}\s+Nov\s+\d{4}$/i', $reference) // Matches "7th Nov 2025"
            ) {
                $errors[] = "Row " . ($index + 2) . ": Invalid or empty Reference value: '" . substr($reference, 0, 50) . "'";
                continue;
            }
            
            if ($reference) {
                // First try to find by flg_reference
                $lead = Lead::where('flg_reference', $reference)->first();
                
                // If not found by flg_reference, try by ID
                if (!$lead && is_numeric($reference)) {
                    $lead = Lead::find($reference);
                }
                
                // If still not found, create a new lead
                if (!$lead) {
                    // Try to get lead information from the row if available
                    $leadName = !empty($rowData['Name']) ? trim($rowData['Name']) : '';
                    $leadCompany = !empty($rowData['Company']) ? trim($rowData['Company']) : null;
                    
                    // Parse name into first_name and last_name
                    $firstName = 'Unknown';
                    $lastName = null;
                    if (!empty($leadName)) {
                        $nameParts = explode(' ', $leadName, 2);
                        $firstName = $nameParts[0];
                        $lastName = isset($nameParts[1]) ? $nameParts[1] : null;
                    }
                    
                    // Get project - try LeadGroupID or LeadGroup from row, or use a default
                    $projectId = null;
                    if (!empty($rowData['LeadGroupID'])) {
                        $project = \App\Models\Project::where('flg_group_id', $rowData['LeadGroupID'])->first();
                        if ($project) {
                            $projectId = $project->id;
                        } else {
                            // Create project if it doesn't exist
                            $projectTitle = !empty($rowData['LeadGroup']) ? $rowData['LeadGroup'] : ('Project ' . $rowData['LeadGroupID']);
                            $project = \App\Models\Project::create([
                                'flg_group_id' => $rowData['LeadGroupID'],
                                'title' => $projectTitle,
                                'description' => null,
                                'created_by_user_id' => auth()->id(),
                            ]);
                            $projectId = $project->id;
                        }
                    } elseif (!empty($rowData['LeadGroup'])) {
                        $project = \App\Models\Project::where('title', $rowData['LeadGroup'])->first();
                        if ($project) {
                            $projectId = $project->id;
                        } else {
                            // Create project if it doesn't exist
                            $project = \App\Models\Project::create([
                                'flg_group_id' => null,
                                'title' => $rowData['LeadGroup'],
                                'description' => null,
                                'created_by_user_id' => auth()->id(),
                            ]);
                            $projectId = $project->id;
                        }
                    } else {
                        // Try to get project from first existing lead or create a default
                        $defaultProject = \App\Models\Project::first();
                        if (!$defaultProject) {
                            $defaultProject = \App\Models\Project::create([
                                'flg_group_id' => null,
                                'title' => 'Default Project',
                                'description' => 'Auto-created for imported leads',
                                'created_by_user_id' => auth()->id(),
                            ]);
                        }
                        $projectId = $defaultProject->id;
                    }
                    
                    // Handle received date
                    $receivedDate = null;
                    if (!empty($rowData['ReceivedDateTime'])) {
                        try {
                            $receivedDateObj = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $rowData['ReceivedDateTime']);
                            $receivedDate = $receivedDateObj->format('Y-m-d');
                        } catch (\Exception $e) {
                            try {
                                $receivedDateObj = \Carbon\Carbon::createFromFormat('d/m/Y', $rowData['ReceivedDateTime']);
                                $receivedDate = $receivedDateObj->format('Y-m-d');
                            } catch (\Exception $e2) {
                                // Use current date as fallback
                                $receivedDate = now()->format('Y-m-d');
                            }
                        }
                    } else {
                        $receivedDate = now()->format('Y-m-d');
                    }
                    
                    // Create the lead
                    try {
                        $lead = Lead::create([
                            'flg_reference' => $reference,
                            'sub_reference' => !empty($rowData['SubReference']) ? trim($rowData['SubReference']) : null,
                            'project_id' => $projectId,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'company' => $leadCompany,
                            'received_date' => $receivedDate,
                            'added_by' => auth()->id(),
                        ]);
                    } catch (\Exception $e) {
                        $errors[] = "Row " . ($index + 2) . ": Failed to create lead - " . $e->getMessage();
                        continue;
                    }
                }
            } else {
                $errors[] = "Row " . ($index + 2) . ": Reference is required";
                continue;
            }
            
            // Map CSV columns to activity fields - try multiple column name variations
            $activityType = null;
            // Try different possible column names for ActivityType (case-insensitive search)
            $typeColumns = ['ActivityType', 'activity_type', 'Activity_Type', 'Type', 'type', 'ActivityTypeName', 'activityType'];
            
            // First try exact match
            foreach ($typeColumns as $colName) {
                if (isset($rowData[$colName]) && !empty(trim($rowData[$colName]))) {
                    $activityType = trim($rowData[$colName]);
                    break;
                }
            }
            
            // If not found, try case-insensitive search through all columns
            if (empty($activityType)) {
                foreach ($rowData as $colName => $colValue) {
                    $colNameLower = strtolower(trim($colName));
                    if (in_array($colNameLower, ['activitytype', 'activity_type', 'type']) && !empty(trim($colValue))) {
                        $activityType = trim($colValue);
                        break;
                    }
                }
            }
            
            $activityData = [
                'lead_id' => $lead->id,
                'type' => $activityType,
                'field_1' => !empty($rowData['ActivityField1']) ? trim($rowData['ActivityField1']) : (!empty($rowData['field_1']) ? trim($rowData['field_1']) : null),
                'field_2' => !empty($rowData['ActivityField2']) ? trim($rowData['ActivityField2']) : (!empty($rowData['field_2']) ? trim($rowData['field_2']) : null),
                'email' => !empty($rowData['ActivityEmail']) ? trim($rowData['ActivityEmail']) : (!empty($rowData['email']) ? trim($rowData['email']) : null),
                'bcc' => !empty($rowData['ActivityBcc']) ? trim($rowData['ActivityBcc']) : (!empty($rowData['bcc']) ? trim($rowData['bcc']) : null),
                'cc' => !empty($rowData['ActivityCc']) ? trim($rowData['ActivityCc']) : (!empty($rowData['cc']) ? trim($rowData['cc']) : null),
                'phone' => !empty($rowData['ActivityPhone']) ? trim($rowData['ActivityPhone']) : (!empty($rowData['phone']) ? trim($rowData['phone']) : null),
                'created_by' => auth()->id(),
            ];
            
            // Handle date (ActivityDateTime) - try multiple column name variations
            $activityDateValue = null;
            $dateColumns = ['ActivityDateTime', 'activity_date_time', 'Activity_DateTime', 'Date', 'date', 'ActivityDate'];
            foreach ($dateColumns as $colName) {
                if (!empty($rowData[$colName]) && trim($rowData[$colName]) !== '' && trim($rowData[$colName]) !== '0000-00-00 00:00:00') {
                    $activityDateValue = trim($rowData[$colName]);
                    break;
                }
            }
            
            $activityData['date'] = null;
            if ($activityDateValue) {
                try {
                    // Try d/m/Y H:i format first
                    $activityDate = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $activityDateValue);
                    $activityData['date'] = $activityDate->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        // Try d/m/Y format
                        $activityDate = \Carbon\Carbon::createFromFormat('d/m/Y', $activityDateValue);
                        $activityData['date'] = $activityDate->format('Y-m-d');
                    } catch (\Exception $e2) {
                        try {
                            // Try Y-m-d H:i:s format
                            $activityDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $activityDateValue);
                            $activityData['date'] = $activityDate->format('Y-m-d');
                        } catch (\Exception $e3) {
                            try {
                                // Try Y-m-d format
                                $activityDate = \Carbon\Carbon::createFromFormat('Y-m-d', $activityDateValue);
                                $activityData['date'] = $activityDate->format('Y-m-d');
                            } catch (\Exception $e4) {
                                try {
                                    // Try Carbon parse as last resort
                                    $activityDate = \Carbon\Carbon::parse($activityDateValue);
                                    if ($activityDate->year > 1900 && $activityDate->year < 2100) {
                                        $activityData['date'] = $activityDate->format('Y-m-d');
                                    }
                                } catch (\Exception $e5) {
                                    // Invalid date, will use default
                                }
                            }
                        }
                    }
                }
            }
            
            // Use current date as fallback if date parsing failed
            if (empty($activityData['date'])) {
                $activityData['date'] = now()->format('Y-m-d');
            }
            
            // Handle due date (ActivityDueDateTime)
            if (!empty($rowData['ActivityDueDateTime']) && $rowData['ActivityDueDateTime'] !== '0000-00-00 00:00:00') {
                try {
                    $dueDate = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $rowData['ActivityDueDateTime']);
                    $activityData['due_date'] = $dueDate->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        $dueDate = \Carbon\Carbon::createFromFormat('d/m/Y', $rowData['ActivityDueDateTime']);
                        $activityData['due_date'] = $dueDate->format('Y-m-d');
                    } catch (\Exception $e2) {
                        try {
                            $dueDate = \Carbon\Carbon::parse($rowData['ActivityDueDateTime']);
                            $activityData['due_date'] = $dueDate->format('Y-m-d');
                        } catch (\Exception $e3) {
                            // Invalid date, skip
                        }
                    }
                }
            }
            
            // Handle end date (ActivityEndDateTime)
            if (!empty($rowData['ActivityEndDateTime']) && $rowData['ActivityEndDateTime'] !== '0000-00-00 00:00:00') {
                try {
                    $endDate = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $rowData['ActivityEndDateTime']);
                    $activityData['end_date'] = $endDate->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $rowData['ActivityEndDateTime']);
                        $activityData['end_date'] = $endDate->format('Y-m-d');
                    } catch (\Exception $e2) {
                        try {
                            $endDate = \Carbon\Carbon::parse($rowData['ActivityEndDateTime']);
                            $activityData['end_date'] = $endDate->format('Y-m-d');
                        } catch (\Exception $e3) {
                            // Invalid date, skip
                        }
                    }
                }
            }
            
            // Handle actioned (ActivityActioned)
            if (!empty($rowData['ActivityActioned'])) {
                $actionedValue = strtolower(trim($rowData['ActivityActioned']));
                $activityData['actioned'] = in_array($actionedValue, ['yes', '1', 'true', 'y']);
            }
            
            // Handle priority (ActivityPriority)
            if (!empty($rowData['ActivityPriority'])) {
                $priorityValue = strtolower(trim($rowData['ActivityPriority']));
                if (!in_array($priorityValue, ['yes', 'no', '1', '0', 'true', 'false', ''])) {
                    $activityData['priority'] = $rowData['ActivityPriority'];
                }
            }
            
            // Handle ActivityCreatedUser
            if (!empty($rowData['ActivityCreatedUser'])) {
                $createdUser = User::where('name', $rowData['ActivityCreatedUser'])->first();
                if ($createdUser) {
                    $activityData['created_by'] = $createdUser->id;
                }
            }
            
            // Handle ActivityAssignedUser
            if (!empty($rowData['ActivityAssignedUser'])) {
                $assignedUser = User::where('name', $rowData['ActivityAssignedUser'])->first();
                if ($assignedUser) {
                    $activityData['assigned_to'] = $assignedUser->id;
                }
            }
            
            // Validate required fields
            if (empty($activityData['type']) || trim($activityData['type']) === '') {
                // Skip rows without ActivityType - they're likely data rows, not activity rows
                $errors[] = "Row " . ($index + 2) . ": Activity Type is required (skipping row)";
                continue;
            }
            
            // Ensure date is valid (not negative year)
            if (empty($activityData['date']) || strpos($activityData['date'], '-0001') !== false) {
                $activityData['date'] = now()->format('Y-m-d');
            }
            
            // Check for duplicate activities
            $isDuplicate = false;
            
            // First check by ActivityID if provided
            if (!empty($rowData['ActivityID'])) {
                $existingActivity = Activity::find($rowData['ActivityID']);
                if ($existingActivity) {
                    $errors[] = "Row " . ($index + 2) . ": Activity with ID '{$rowData['ActivityID']}' already exists";
                    continue;
                }
            }
            
            // Check for duplicate based on lead_id + type + date + key fields
            // This prevents importing the same activity multiple times
            $duplicateCheck = Activity::where('lead_id', $activityData['lead_id'])
                ->where('type', $activityData['type'])
                ->where('date', $activityData['date']);
            
            // Add field_1 to duplicate check if it exists and is meaningful
            if (!empty($activityData['field_1']) && strlen(trim($activityData['field_1'])) > 3) {
                $duplicateCheck->where('field_1', $activityData['field_1']);
            }
            
            // Add field_2 to duplicate check if it exists and is meaningful
            if (!empty($activityData['field_2']) && strlen(trim($activityData['field_2'])) > 3) {
                $duplicateCheck->where('field_2', $activityData['field_2']);
            }
            
            // If we have field_1 or field_2, check for exact match
            // Otherwise, check for activities created in last 10 minutes (to catch same import run)
            if (empty($activityData['field_1']) && empty($activityData['field_2'])) {
                $duplicateCheck->where('created_at', '>=', now()->subMinutes(10));
            }
            
            $existingActivity = $duplicateCheck->first();
            
            if ($existingActivity) {
                $errors[] = "Row " . ($index + 2) . ": Duplicate activity detected (same lead, type '{$activityData['type']}', date '{$activityData['date']}') - Activity ID: {$existingActivity->id}";
                continue;
            }

            try {
                Activity::create($activityData);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        // Log the action
        if ($imported > 0) {
            Log::createLog(auth()->id(), 'import_activities', "Imported {$imported} activities from CSV");
        }

        // If there are errors or no activities imported, return error response
        if (count($errors) > 0 || $imported === 0) {
            $errorMessage = "Import failed. ";
            if ($imported === 0) {
                $errorMessage .= "No activities were imported. ";
            } else {
                $errorMessage .= "Only {$imported} activity(ies) imported. ";
            }
            if (count($errors) > 0) {
                $errorMessage .= count($errors) . " error(s) occurred.";
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'imported' => $imported,
                'errors' => $errors
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully imported {$imported} activity(ies).",
            'imported' => $imported,
            'errors' => $errors
        ]);
    }

    /**
     * Generate a unique message ID for email
     */
    private function generateMessageId(): string
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return sprintf('<%s.%s.%s@%s>', $timestamp, $random, auth()->id(), $domain);
    }

    /**
     * Send email before storing activity (for new emails)
     * Returns true if at least one email was sent successfully
     */
    private function sendActivityEmailBeforeStore(array $data, string $messageId, string $inReplyTo = null): bool
    {
        try {
            // Get authenticated user's email for reply-to
            $user = auth()->user();
            $senderEmail = $user->email ?? config('mail.from.address');
            $senderName = $user->name ?? config('mail.from.name');
            
            // Use email credentials from .env file
            $fromEmail = config('mail.from.address');
            $fromName = config('mail.from.name');
            
            // Get subject and body
            $subject = $data['field_1'] ?? 'No Subject';
            $body = $data['field_2'] ?? '';
            
            // Body should already be validated before calling this function
            // But add a safety check - if empty, use a default message
            if (empty($body) || trim($body) === '') {
                $body = '<p>This is an email from ' . config('app.name') . '</p>';
            }
            
            // Prepare recipients
            $toRecipients = [];
            $ccRecipients = [];
            $bccRecipients = [];
            
            // Parse 'to' field (comma-separated emails)
            if (!empty($data['to'])) {
                $toEmails = array_map('trim', explode(',', $data['to']));
                foreach ($toEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $toRecipients[] = $email;
                    }
                }
            }
            
            // Parse 'cc' field (comma-separated emails)
            if (!empty($data['cc'])) {
                $ccEmails = array_map('trim', explode(',', $data['cc']));
                foreach ($ccEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $ccRecipients[] = $email;
                    }
                }
            }
            
            // Parse 'bcc' field (comma-separated emails)
            if (!empty($data['bcc'])) {
                $bccEmails = array_map('trim', explode(',', $data['bcc']));
                foreach ($bccEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $bccRecipients[] = $email;
                    }
                }
            }
            
            // If no recipients found, try to use lead's email
            if (empty($toRecipients) && empty($ccRecipients) && empty($bccRecipients)) {
                $lead = Lead::find($data['lead_id']);
                if ($lead && $lead->email) {
                    $toRecipients[] = $lead->email;
                } else {
                    LogFacade::warning('No recipients found for email activity', [
                        'lead_id' => $data['lead_id']
                    ]);
                    return false;
                }
            }
            
            // Prepare attachments
            $attachmentPaths = [];
            if (!empty($data['file'])) {
                $fileString = is_string($data['file']) ? $data['file'] : (string)$data['file'];
                $files = explode(',', $fileString);
                
                foreach ($files as $file) {
                    $file = trim($file);
                    if (empty($file)) {
                        continue;
                    }
                    
                    if (strpos($file, 'uploads/activities/') === 0) {
                        $filePath = public_path($file);
                    } else {
                        $filePath = public_path('uploads/activities/' . $file);
                    }
                    
                    $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
                    
                    if (file_exists($filePath) && is_file($filePath) && is_readable($filePath)) {
                        $attachmentPaths[] = $filePath;
                    }
                }
            }
            
            if (!is_array($attachmentPaths)) {
                $attachmentPaths = [];
            }
            
            // Create a temporary activity object for the email (won't be saved)
            $tempActivity = new Activity($data);
            $tempActivity->id = 0; // Temporary ID
            $tempActivity->setRelation('lead', Lead::find($data['lead_id']));
            
            // Track if at least one email was sent successfully
            $atLeastOneSent = false;
            $successfulRecipients = [];
            $failedRecipients = [];
            
            // Register event listener to set Message-ID header
            // addIdHeader() expects the value WITHOUT angle brackets and adds them automatically
            $messageIdValue = trim($messageId, '<>');
            $inReplyToValue = $inReplyTo ? trim($inReplyTo, '<>') : null;
            $listener = Event::listen(MessageSending::class, function ($event) use ($messageIdValue, $inReplyToValue) {
                if ($messageIdValue) {
                    $headers = $event->message->getHeaders();
                    // Remove existing Message-ID header if present
                    if ($headers->has('Message-ID')) {
                        $headers->remove('Message-ID');
                    }
                    // Add the Message-ID header using addIdHeader (it will add angle brackets automatically)
                    $headers->addIdHeader('Message-ID', $messageIdValue);
                    
                    // Add In-Reply-To header for email threading
                    if ($inReplyToValue) {
                        // Remove existing In-Reply-To header if present
                        if ($headers->has('In-Reply-To')) {
                            $headers->remove('In-Reply-To');
                        }
                        // Set the In-Reply-To header
                        $headers->addIdHeader('In-Reply-To', $inReplyToValue);
                        
                        // Also add References header for better threading
                        if ($headers->has('References')) {
                            $existingReferences = $headers->get('References');
                            $referencesValue = $existingReferences ? $existingReferences->getValue() . ' ' . $inReplyToValue : $inReplyToValue;
                            $headers->remove('References');
                            $headers->addTextHeader('References', $referencesValue);
                        } else {
                            $headers->addTextHeader('References', $inReplyToValue);
                        }
                    }
                }
            });
            
            try {
                // Send to 'to' recipients
                foreach ($toRecipients as $recipient) {
                    try {
                        $mail = new ActivityEmail($tempActivity, $subject, $body, $attachmentPaths, $fromEmail, $fromName, $senderEmail, $senderName, $messageId);
                        Mail::to($recipient)->send($mail);
                        $successfulRecipients[] = $recipient;
                        $atLeastOneSent = true;
                    } catch (\Exception $e) {
                        $failedRecipients[] = [
                            'email' => $recipient,
                            'error' => $e->getMessage()
                        ];
                        LogFacade::error('Failed to send activity email to recipient', [
                            'recipient' => $recipient,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Send to 'cc' recipients
                foreach ($ccRecipients as $recipient) {
                    try {
                        $mail = new ActivityEmail($tempActivity, $subject, $body, $attachmentPaths, $fromEmail, $fromName, $senderEmail, $senderName, $messageId);
                        Mail::to($recipient)->send($mail);
                        $successfulRecipients[] = $recipient . ' (CC)';
                        $atLeastOneSent = true;
                    } catch (\Exception $e) {
                        $failedRecipients[] = [
                            'email' => $recipient . ' (CC)',
                            'error' => $e->getMessage()
                        ];
                        LogFacade::error('Failed to send activity email to CC recipient', [
                            'recipient' => $recipient,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Send to 'bcc' recipients
                foreach ($bccRecipients as $recipient) {
                    try {
                        $mail = new ActivityEmail($tempActivity, $subject, $body, $attachmentPaths, $fromEmail, $fromName, $senderEmail, $senderName, $messageId);
                        Mail::to($recipient)->send($mail);
                        $successfulRecipients[] = $recipient . ' (BCC)';
                        $atLeastOneSent = true;
                    } catch (\Exception $e) {
                        $failedRecipients[] = [
                            'email' => $recipient . ' (BCC)',
                            'error' => $e->getMessage()
                        ];
                        LogFacade::error('Failed to send activity email to BCC recipient', [
                            'recipient' => $recipient,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } finally {
                // Remove the event listener after sending
                Event::forget(MessageSending::class);
            }
            
            // Log results
            if ($atLeastOneSent) {
                LogFacade::info('Activity email sent successfully before storing', [
                    'message_id' => $messageId,
                    'successful_recipients' => $successfulRecipients,
                    'failed_count' => count($failedRecipients)
                ]);
            }
            
            if (!empty($failedRecipients)) {
                LogFacade::warning('Some recipients failed when sending activity email', [
                    'message_id' => $messageId,
                    'failed_recipients' => $failedRecipients,
                    'successful_count' => count($successfulRecipients)
                ]);
            }
            
            return $atLeastOneSent;
            
        } catch (\Exception $e) {
            LogFacade::error('Error sending activity email before store', [
                'message_id' => $messageId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send email for Email type activity (for existing activities)
     */
    private function sendActivityEmail(Activity $activity)
    {
        try {
            // Get authenticated user's email for reply-to
            $user = auth()->user();
            $senderEmail = $user->email ?? config('mail.from.address');
            $senderName = $user->name ?? config('mail.from.name');
            
            // Use email credentials from .env file (MAIL_FROM_ADDRESS and MAIL_FROM_NAME)
            $fromEmail = config('mail.from.address');
            $fromName = config('mail.from.name');
            
            // Get subject and body
            $subject = $activity->field_1 ?? 'No Subject';
            $body = $activity->field_2 ?? '';
            
            // If body is empty, use a default message
            if (empty($body)) {
                $body = '<p>This is an email from ' . config('app.name') . '</p>';
            }
            
            // Prepare recipients
            $toRecipients = [];
            $ccRecipients = [];
            $bccRecipients = [];
            
            // Parse 'to' field (comma-separated emails)
            if (!empty($activity->to)) {
                $toEmails = array_map('trim', explode(',', $activity->to));
                foreach ($toEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $toRecipients[] = $email;
                    }
                }
            }
            
            // Parse 'cc' field (comma-separated emails)
            if (!empty($activity->cc)) {
                $ccEmails = array_map('trim', explode(',', $activity->cc));
                foreach ($ccEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $ccRecipients[] = $email;
                    }
                }
            }
            
            // Parse 'bcc' field (comma-separated emails)
            if (!empty($activity->bcc)) {
                $bccEmails = array_map('trim', explode(',', $activity->bcc));
                foreach ($bccEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $bccRecipients[] = $email;
                    }
                }
            }
            
            // If no recipients found, try to use lead's email
            if (empty($toRecipients) && empty($ccRecipients) && empty($bccRecipients)) {
                if ($activity->lead && $activity->lead->email) {
                    $toRecipients[] = $activity->lead->email;
                } else {
                    LogFacade::warning('No recipients found for activity email', [
                        'activity_id' => $activity->id,
                        'lead_id' => $activity->lead_id
                    ]);
                    return;
                }
            }
            
            // Prepare attachments - ensure it's always an array
            $attachmentPaths = [];
            if (!empty($activity->file)) {
                // Ensure file is a string before exploding
                $fileString = is_string($activity->file) ? $activity->file : (string)$activity->file;
                $files = explode(',', $fileString);
                
                foreach ($files as $file) {
                    $file = trim($file);
                    if (empty($file)) {
                        continue;
                    }
                    
                    // Handle both relative and absolute paths
                    if (strpos($file, 'uploads/activities/') === 0) {
                        $filePath = public_path($file);
                    } else {
                        $filePath = public_path('uploads/activities/' . $file);
                    }
                    
                    // Normalize path separators for Windows
                    $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
                    
                    if (file_exists($filePath) && is_file($filePath) && is_readable($filePath)) {
                        $attachmentPaths[] = $filePath;
                    } else {
                        LogFacade::warning('Attachment file not found or not accessible', [
                            'activity_id' => $activity->id,
                            'file_path' => $filePath,
                            'original_file' => $file,
                            'exists' => file_exists($filePath),
                            'is_file' => is_file($filePath),
                            'readable' => is_readable($filePath)
                        ]);
                    }
                }
            }
            
            // Ensure attachmentPaths is always an array (not null or string)
            if (!is_array($attachmentPaths)) {
                $attachmentPaths = [];
            }
            
            // Send email to each recipient
            $successfulRecipients = [];
            $failedRecipients = [];
            
            // Send to 'to' recipients
            foreach ($toRecipients as $recipient) {
                try {
                    // Ensure we pass a valid array for attachments
                    // fromEmail: authorized SMTP sender, replyToEmail: actual sender
                    $mail = new ActivityEmail($activity, $subject, $body, $attachmentPaths, $fromEmail, $fromName, $senderEmail, $senderName);
                    Mail::to($recipient)->send($mail);
                    $successfulRecipients[] = $recipient;
                    LogFacade::info('Activity email sent successfully', [
                        'activity_id' => $activity->id,
                        'recipient' => $recipient,
                        'from_email' => $fromEmail,
                        'reply_to_email' => $senderEmail,
                        'sender_name' => $senderName,
                    ]);
                } catch (\Exception $e) {
                    $failedRecipients[] = [
                        'email' => $recipient,
                        'error' => $e->getMessage()
                    ];
                    LogFacade::error('Failed to send activity email to recipient', [
                        'activity_id' => $activity->id,
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Send to 'cc' recipients
            foreach ($ccRecipients as $recipient) {
                try {
                    $mail = new ActivityEmail($activity, $subject, $body, $attachmentPaths, $fromEmail, $fromName, $senderEmail, $senderName);
                    Mail::to($recipient)->send($mail);
                    $successfulRecipients[] = $recipient . ' (CC)';
                    LogFacade::info('Activity email sent successfully', [
                        'activity_id' => $activity->id,
                        'recipient' => $recipient,
                        'from_email' => $fromEmail,
                        'reply_to_email' => $senderEmail,
                        'sender_name' => $senderName,
                    ]);
                } catch (\Exception $e) {
                    $failedRecipients[] = [
                        'email' => $recipient . ' (CC)',
                        'error' => $e->getMessage()
                    ];
                    LogFacade::error('Failed to send activity email to CC recipient', [
                        'activity_id' => $activity->id,
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Send to 'bcc' recipients
            foreach ($bccRecipients as $recipient) {
                try {
                    $mail = new ActivityEmail($activity, $subject, $body, $attachmentPaths, $fromEmail, $fromName, $senderEmail, $senderName);
                    Mail::to($recipient)->send($mail);
                    $successfulRecipients[] = $recipient . ' (BCC)';
                } catch (\Exception $e) {
                    $failedRecipients[] = [
                        'email' => $recipient . ' (BCC)',
                        'error' => $e->getMessage()
                    ];
                    LogFacade::error('Failed to send activity email to BCC recipient', [
                        'activity_id' => $activity->id,
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Log results
            if (!empty($successfulRecipients)) {
                LogFacade::info('Activity email sent successfully', [
                    'activity_id' => $activity->id,
                    'sender_email' => $senderEmail,
                    'sender_name' => $senderName,
                    'successful_recipients' => $successfulRecipients,
                    'failed_count' => count($failedRecipients)
                ]);
            }
            
            if (!empty($failedRecipients)) {
                LogFacade::error('Failed to send activity email to some recipients', [
                    'activity_id' => $activity->id,
                    'sender_email' => $senderEmail,
                    'sender_name' => $senderName,
                    'failed_recipients' => $failedRecipients,
                    'successful_count' => count($successfulRecipients)
                ]);
            }
            
        } catch (\Exception $e) {
            LogFacade::error('Error sending activity email', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

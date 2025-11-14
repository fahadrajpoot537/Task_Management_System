<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use App\Models\Log;
use App\Models\Activity;
use App\Models\LeadType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

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
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('flg_reference', 'like', "%{$search}%");
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
        $lead = Lead::with(['project.statuses', 'addedBy', 'status', 'activities.createdBy', 'activities.assignedTo'])->findOrFail($id);

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
            'lead_type_id' => 'nullable|exists:lead_types,id',
            'added_by' => 'nullable|exists:users,id',
            'user_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // If added_by is being set, ensure user_name is also set
        // If added_by is null, clear user_name as well
        if (isset($data['added_by'])) {
            if ($data['added_by']) {
                // If user_name is not provided, fetch it from the user
                if (empty($data['user_name'])) {
                    $user = \App\Models\User::find($data['added_by']);
                    if ($user) {
                        $data['user_name'] = $user->name;
                    }
                }
            } else {
                // Clear user_name when added_by is null
                $data['user_name'] = null;
            }
        }

        $lead->update($data);

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

    /**
     * Get all active lead types.
     */
    public function getLeadTypes()
    {
        $leadTypes = LeadType::where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'lead_types' => $leadTypes
        ]);
    }

    /**
     * Export leads to CSV (matching the specified format)
     */
    public function exportLeads(Request $request)
    {
        $query = Lead::with(['project', 'addedBy', 'status', 'activities']);

        // Apply same filters as index
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('flg_reference', 'like', "%{$search}%");
            });
        }

        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $leads = $query->get();

        $filename = 'leads_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($leads) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers matching the specified format
            $headers = [
                'Reference', 'SubReference', 'ReceivedDateTime', 'LeadGroupID', 'LeadGroup', 'LeadType', 'Status', 'Progress',
                'PermissionToCall', 'PermissionToText', 'PermissionToEmail', 'PermissionToMail', 'PermissionToFax',
                'SiteID', 'SiteName', 'UserID', 'User', 'BuyerID', 'Buyer', 'BuyerReference',
                'IntroducerID', 'Introducer', 'IntroducerReference', 'Cost', 'Value', 'IPAddress',
                'MarketingSource', 'MarketingMedium', 'MarketingTerm', 'TransferDateTime', 'TransferSuccessful',
                'XMLPost', 'XMLResponse', 'XMLDateTime', 'XMLFails', 'XMLResult', 'XMLReference',
                'AppointmentDateTime', 'AppointmentNotes', 'ReturnStatus', 'ReturnDateTime', 'ReturnReason',
                'ReturnDecisionDateTime', 'ReturnDecisionUser', 'ReturnDecisionInformation',
                'LastNoteDateTime', 'LastNote', 'TaskExists', 'WorkflowExists',
                'FullName', 'Title', 'FirstName', 'LastName', 'CompanyName', 'JobTitle',
                'DOBDay', 'DOBMonth', 'DOBYear', 'Telephone1', 'Telephone2', 'Fax',
                'Email', 'Address', 'Address2', 'Address3', 'TownCity', 'Postcode', 'ContactTime',
                'Data1', 'Data2', 'Data3', 'Data4', 'Data5', 'Data6', 'Data7', 'Data8', 'Data9', 'Data10',
                'Data11', 'Data12', 'Data13', 'Data14', 'Data15', 'Data16', 'Data17', 'Data18', 'Data19', 'Data20',
                'Data21', 'Data22', 'Data23', 'Data24', 'Data25', 'Data26', 'Data27', 'Data28', 'Data29', 'Data30',
                'Data31', 'Data32', 'Data33', 'Data34', 'Data35', 'Data36', 'Data37', 'Data38', 'Data39', 'Data40',
                'Data41', 'Data42', 'Data43', 'Data44', 'Data45', 'Data46', 'Data47', 'Data48', 'Data49', 'Data50',
                'Type1', 'Type2', 'Type3', 'Type4', 'Type5', 'Type6', 'Type7', 'Type8', 'Type9', 'Type10',
                'Type11', 'Type12', 'Type13', 'Type14', 'Type15', 'Type16', 'Type17', 'Type18', 'Type19', 'Type20',
                'Type21', 'Type22', 'Type23', 'Type24', 'Type25', 'Type26', 'Type27', 'Type28', 'Type29', 'Type30',
                'Type31', 'Type32', 'Type33', 'Type34', 'Type35', 'Type36', 'Type37', 'Type38', 'Type39', 'Type40',
                'Type41', 'Type42', 'Type43', 'Type44', 'Type45', 'Type46', 'Type47', 'Type48', 'Type49', 'Type50'
            ];
            fputcsv($file, $headers);

            // Data - one row per lead
            foreach ($leads as $lead) {
                // Reference is the lead's id
                $reference = $lead->id ?? '';
                $subReference = $lead->sub_reference ?? '';
                
                // Format received date
                $receivedDateTime = '';
                if ($lead->received_date) {
                    if ($lead->created_at) {
                        $receivedDateTime = $lead->received_date->format('d/m/Y') . ' ' . $lead->created_at->format('H:i');
                    } else {
                        $receivedDateTime = $lead->received_date->format('d/m/Y') . ' 00:00';
                    }
                } elseif ($lead->created_at) {
                    $receivedDateTime = $lead->created_at->format('d/m/Y H:i');
                }
                
                // Lead Group (Project)
                // Use flg_group_id if available, otherwise use project_id
                $leadGroupID = '';
                if ($lead->project) {
                    $leadGroupID = $lead->project->flg_group_id ?? $lead->project->id ?? '';
                }
                $leadGroup = $lead->project ? $lead->project->title : '';
                $leadType = 'General'; // Default
                
                // Status
                $status = $lead->status ? $lead->status->name : '';
                $progress = $lead->progress ?? '';
                
                // Permissions
                $permissionToCall = ($lead->permission_to_call ?? false) ? 'Yes' : 'No';
                $permissionToText = ($lead->permission_to_text ?? false) ? 'Yes' : 'No';
                $permissionToEmail = ($lead->permission_to_email ?? false) ? 'Yes' : 'No';
                $permissionToMail = ($lead->permission_to_mail ?? false) ? 'Yes' : 'No';
                $permissionToFax = ($lead->permission_to_fax ?? false) ? 'Yes' : 'No';
                
                // Site
                $siteID = $lead->site_id ?? '';
                $siteName = $lead->site_name ?? '';
                
                // User
                $userID = $lead->user_id ?? ($lead->added_by ?? '');
                $user = $lead->user_name ?? ($lead->addedBy ? $lead->addedBy->name : '');
                
                // Buyer
                $buyerID = $lead->buyer_id ?? '';
                $buyer = $lead->buyer_name ?? '';
                $buyerReference = $lead->buyer_reference ?? '';
                
                // Introducer
                $introducerID = $lead->introducer_id ?? '';
                $introducer = $lead->introducer_name ?? '';
                $introducerReference = $lead->introducer_reference ?? '';
                
                // Cost and Value
                $cost = $lead->cost ?? '0';
                $value = $lead->value ?? '0';
                
                // IP Address
                $ipAddress = $lead->ip_address ?? '';
                
                // Marketing
                $marketingSource = $lead->marketing_source ?? '';
                $marketingMedium = $lead->marketing_medium ?? '';
                $marketingTerm = $lead->marketing_term ?? '';
                
                // Transfer
                $transferDateTime = $lead->transfer_date_time ? \Carbon\Carbon::parse($lead->transfer_date_time)->format('d/m/Y H:i') : '';
                $transferSuccessful = ($lead->transfer_successful ?? false) ? 'Yes' : 'No';
                
                // XML
                $xmlPost = $lead->xml_post ?? '';
                $xmlResponse = $lead->xml_response ?? '';
                $xmlDateTime = $lead->xml_date_time ? \Carbon\Carbon::parse($lead->xml_date_time)->format('d/m/Y H:i') : '';
                $xmlFails = $lead->xml_fails ?? '0';
                $xmlResult = $lead->xml_result ?? '';
                $xmlReference = $lead->xml_reference ?? '';
                
                // Appointment
                $appointmentDateTime = $lead->appointment_date_time ? \Carbon\Carbon::parse($lead->appointment_date_time)->format('d/m/Y H:i') : '';
                $appointmentNotes = $lead->appointment_notes ?? '';
                
                // Return
                $returnStatus = $lead->return_status ?? '';
                $returnDateTime = $lead->return_date_time ? \Carbon\Carbon::parse($lead->return_date_time)->format('d/m/Y H:i') : '';
                $returnReason = $lead->return_reason ?? '';
                $returnDecisionDateTime = $lead->return_decision_date_time ? \Carbon\Carbon::parse($lead->return_decision_date_time)->format('d/m/Y H:i') : '';
                $returnDecisionUser = $lead->return_decision_user ?? '';
                $returnDecisionInformation = $lead->return_decision_information ?? '';
                
                // Last Note
                $lastNoteDateTime = $lead->last_note_date_time ? \Carbon\Carbon::parse($lead->last_note_date_time)->format('d/m/Y H:i') : '';
                $lastNote = $lead->note ?? '';
                
                // Task and Workflow exists
                $taskExists = ($lead->task_exists ?? false) ? 'Yes' : (($lead->activities && $lead->activities->count() > 0) ? 'Yes' : 'No');
                $workflowExists = ($lead->workflow_exists ?? false) ? 'Yes' : 'No';
                
                // Name fields
                $fullName = $lead->full_name ?? trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''));
                $title = $lead->title ?? '';
                $firstName = $lead->first_name ?? '';
                $lastName = $lead->last_name ?? '';
                $companyName = $lead->company ?? '';
                $jobTitle = $lead->job_title ?? '';
                
                // Date of Birth
                $dobDay = '';
                $dobMonth = '';
                $dobYear = '';
                if ($lead->date_of_birth) {
                    $dobDay = $lead->date_of_birth->format('d');
                    $dobMonth = $lead->date_of_birth->format('m');
                    $dobYear = $lead->date_of_birth->format('Y');
                }
                
                // Phone
                $telephone1 = $lead->phone ?? '';
                $telephone2 = $lead->alternative_phone ?? '';
                $fax = $lead->fax ?? '';
                
                // Contact info
                $email = $lead->email ?? '';
                $address = $lead->address ?? '';
                $address2 = $lead->address2 ?? '';
                $address3 = $lead->address3 ?? '';
                $townCity = $lead->city ?? '';
                $postcode = $lead->postcode ?? '';
                $contactTime = $lead->contact_time ?? '';
                
                // Data1-50
                $dataFields = [];
                for ($i = 1; $i <= 50; $i++) {
                    $dataFields[] = $lead->{"data{$i}"} ?? '';
                }
                
                // Type1-50
                $typeFields = [];
                for ($i = 1; $i <= 50; $i++) {
                    $typeFields[] = $lead->{"type{$i}"} ?? '';
                }
                
                // Build row
                $row = [
                    $reference, $subReference, $receivedDateTime, $leadGroupID, $leadGroup, $leadType, $status, $progress,
                    $permissionToCall, $permissionToText, $permissionToEmail, $permissionToMail, $permissionToFax,
                    $siteID, $siteName, $userID, $user, $buyerID, $buyer, $buyerReference,
                    $introducerID, $introducer, $introducerReference, $cost, $value, $ipAddress,
                    $marketingSource, $marketingMedium, $marketingTerm, $transferDateTime, $transferSuccessful,
                    $xmlPost, $xmlResponse, $xmlDateTime, $xmlFails, $xmlResult, $xmlReference,
                    $appointmentDateTime, $appointmentNotes, $returnStatus, $returnDateTime, $returnReason,
                    $returnDecisionDateTime, $returnDecisionUser, $returnDecisionInformation,
                    $lastNoteDateTime, $lastNote, $taskExists, $workflowExists,
                    $fullName, $title, $firstName, $lastName, $companyName, $jobTitle,
                    $dobDay, $dobMonth, $dobYear, $telephone1, $telephone2, $fax,
                    $email, $address, $address2, $address3, $townCity, $postcode, $contactTime
                ];
                
                // Add Data1-50
                $row = array_merge($row, $dataFields);
                
                // Add Type1-50
                $row = array_merge($row, $typeFields);
                
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export activities for a lead to CSV
     */
    public function exportActivities($leadId)
    {
        $lead = Lead::with(['project', 'status', 'addedBy'])->findOrFail($leadId);
        // Get activities without date casting to access raw values
        $activities = Activity::with(['createdBy', 'assignedTo'])
            ->where('lead_id', $leadId)
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Get raw due_date and end_date values from database
        $rawActivities = \DB::table('activities')
            ->where('lead_id', $leadId)
            ->select('id', 'due_date', 'end_date')
            ->get()
            ->keyBy('id');

        $filename = 'activities_export_lead_' . $leadId . '_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($lead, $activities) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers matching the sample format
            fputcsv($file, [
                'Reference', 'SubReference', 'ReceivedDateTime', 'Name', 'Company',
                'ActivityID', 'ActivityDateTime', 'ActivityType', 'ActivityCreatedUser', 'ActivityAssignedUser',
                'ActivityField1', 'ActivityField2', 'ActivityEmail', 'ActivityBcc', 'ActivityCc', 'ActivityPhone',
                'ActivityActioned', 'ActivityDueDateTime', 'ActivityEndDateTime', 'ActivityPriority'
            ]);

            // Prepare lead data
            // Reference should be the lead's id
            $leadReference = $lead->id ?? '';
            $leadSubReference = $lead->sub_reference ?? '';
            // Format received date - if it has time, use it, otherwise add default time
            $leadReceivedDate = '';
            if ($lead->received_date) {
                // Check if created_at has time component we can use
                if ($lead->created_at) {
                    $leadReceivedDate = $lead->received_date->format('d/m/Y') . ' ' . $lead->created_at->format('H:i');
                } else {
                    $leadReceivedDate = $lead->received_date->format('d/m/Y') . ' 00:00';
                }
            }
            // Name should be just first_name + last_name (no title)
            $leadName = trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''));
            $leadCompany = $lead->company ?? '';

            // Data - each activity is a row with lead info repeated
            foreach ($activities as $activity) {
                // Format activity date - use date field, combine with time from created_at if available
                $activityDate = '';
                if ($activity->date) {
                    if ($activity->created_at) {
                        // Use date from date field and time from created_at
                        $activityDate = $activity->date->format('d/m/Y') . ' ' . $activity->created_at->format('H:i');
                    } else {
                        $activityDate = $activity->date->format('d/m/Y') . ' 00:00';
                    }
                } elseif ($activity->created_at) {
                    $activityDate = $activity->created_at->format('d/m/Y H:i');
                }
                
                // Format due date - may be stored as date or datetime string
                $dueDateTime = '0000-00-00 00:00:00';
                if ($activity->due_date) {
                    try {
                        if (is_string($activity->due_date)) {
                            // Check if it's a datetime string (contains space or :)
                            if (strpos($activity->due_date, ' ') !== false || strpos($activity->due_date, ':') !== false) {
                                $dueDateObj = \Carbon\Carbon::parse($activity->due_date);
                                $dueDateTime = $dueDateObj->format('d/m/Y H:i');
                            } else {
                                // It's just a date
                                $dueDateObj = \Carbon\Carbon::parse($activity->due_date);
                                $dueDateTime = $dueDateObj->format('d/m/Y') . ' 00:00';
                            }
                        } else {
                            // It's a Carbon date object - check if it has time component
                            if ($activity->due_date instanceof \Carbon\Carbon) {
                                $hour = $activity->due_date->format('H');
                                $minute = $activity->due_date->format('i');
                                if ($hour == '00' && $minute == '00') {
                                    $dueDateTime = $activity->due_date->format('d/m/Y') . ' 00:00';
                                } else {
                                    $dueDateTime = $activity->due_date->format('d/m/Y H:i');
                                }
                            } else {
                                $dueDateTime = $activity->due_date->format('d/m/Y') . ' 00:00';
                            }
                        }
                    } catch (\Exception $e) {
                        $dueDateTime = '0000-00-00 00:00:00';
                    }
                }
                
                // Format end date - may be stored as date or datetime string
                $endDateTime = '0000-00-00 00:00:00';
                if ($activity->end_date) {
                    try {
                        if (is_string($activity->end_date)) {
                            // Check if it's a datetime string (contains space or :)
                            if (strpos($activity->end_date, ' ') !== false || strpos($activity->end_date, ':') !== false) {
                                $endDateObj = \Carbon\Carbon::parse($activity->end_date);
                                $endDateTime = $endDateObj->format('d/m/Y H:i');
                            } else {
                                // It's just a date
                                $endDateObj = \Carbon\Carbon::parse($activity->end_date);
                                $endDateTime = $endDateObj->format('d/m/Y') . ' 00:00';
                            }
                        } else {
                            // It's a Carbon date object - check if it has time component
                            if ($activity->end_date instanceof \Carbon\Carbon) {
                                $hour = $activity->end_date->format('H');
                                $minute = $activity->end_date->format('i');
                                if ($hour == '00' && $minute == '00') {
                                    $endDateTime = $activity->end_date->format('d/m/Y') . ' 00:00';
                                } else {
                                    $endDateTime = $activity->end_date->format('d/m/Y H:i');
                                }
                            } else {
                                $endDateTime = $activity->end_date->format('d/m/Y') . ' 00:00';
                            }
                        }
                    } catch (\Exception $e) {
                        $endDateTime = '0000-00-00 00:00:00';
                    }
                }
                
                // Format actioned (Yes/No)
                $actioned = ($activity->actioned == 1 || $activity->actioned === true) ? 'Yes' : 'No';
                
                // Format priority - should be "No" if empty or false, otherwise show the priority value
                $priority = 'No';
                if ($activity->priority) {
                    $priorityValue = strtolower(trim($activity->priority));
                    // Check if it's a boolean-like value
                    if (in_array($priorityValue, ['yes', 'no', '1', '0', 'true', 'false', ''])) {
                        $priority = 'No';
                    } else {
                        // It's a priority value like High/Medium/Low
                        $priority = $activity->priority;
                    }
                }

                fputcsv($file, [
                    $leadReference,                                    // Reference
                    $leadSubReference,                                 // SubReference
                    $leadReceivedDate,                                 // ReceivedDateTime
                    $leadName,                                         // Name
                    $leadCompany,                                      // Company
                    $activity->id,                                     // ActivityID
                    $activityDate,                                     // ActivityDateTime
                    $activity->type ?? '',                             // ActivityType
                    $activity->createdBy ? $activity->createdBy->name : '',  // ActivityCreatedUser
                    $activity->assignedTo ? $activity->assignedTo->name : '', // ActivityAssignedUser
                    $activity->field_1 ?? '',                          // ActivityField1
                    $activity->field_2 ?? '',                          // ActivityField2
                    $activity->email ?? '',                            // ActivityEmail
                    $activity->bcc ?? '',                              // ActivityBcc
                    $activity->cc ?? '',                               // ActivityCc
                    $activity->phone ?? '',                            // ActivityPhone
                    $actioned,                                         // ActivityActioned
                    $dueDateTime,                                      // ActivityDueDateTime
                    $endDateTime,                                      // ActivityEndDateTime
                    $priority                                          // ActivityPriority
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Import leads from CSV (matching the specified format)
     */
    public function importLeads(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $data = array_map('str_getcsv', file($file->getRealPath()));
        
        // Remove BOM if present
        if (!empty($data[0][0])) {
            $data[0][0] = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $data[0][0]);
        }
        
        $headers = array_shift($data); // Remove header row
        // Trim headers once
        $headers = array_map('trim', $headers);
        $imported = 0;
        $errors = [];

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
            
            // Parse Name field into first_name and last_name
            $firstName = '';
            $lastName = null;
            
            // Helper function to clean and get value
            $getValue = function($key, $rowData) {
                // Try exact match first
                if (isset($rowData[$key]) && !empty(trim($rowData[$key]))) {
                    return trim($rowData[$key]);
                }
                // Try case-insensitive match
                foreach ($rowData as $colName => $value) {
                    if (strtolower(trim($colName)) === strtolower(trim($key)) && !empty(trim($value))) {
                        return trim($value);
                    }
                }
                return null;
            };
            
            // First try to get from separate FirstName/LastName columns (try multiple variations)
            $firstNameValue = $getValue('FirstName', $rowData) ?: $getValue('first_name', $rowData) ?: $getValue('First Name', $rowData);
            if ($firstNameValue) {
                // Remove special characters that might come from XLS conversion (like Â, non-breaking spaces, etc.)
                $firstName = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', $firstNameValue);
                $firstName = trim($firstName);
                // Remove leading special characters (BOM, Â, etc.)
                $firstName = ltrim($firstName, "Â \t\n\r\0\x0B");
                // Remove any invisible/special Unicode characters but keep letters, numbers, spaces, hyphens, apostrophes
                $firstName = preg_replace('/[^\p{L}\p{N}\s\-\.\']/u', '', $firstName);
                $firstName = trim($firstName);
            }
            
            $lastNameValue = $getValue('LastName', $rowData) ?: $getValue('last_name', $rowData) ?: $getValue('Last Name', $rowData);
            if ($lastNameValue) {
                $lastName = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', $lastNameValue);
                $lastName = trim($lastName);
                $lastName = ltrim($lastName, "Â \t\n\r\0\x0B");
                $lastName = preg_replace('/[^\p{L}\p{N}\s\-\.\']/u', '', $lastName);
                $lastName = trim($lastName);
            }
            
            // If FirstName is still empty, try FullName column
            if (empty($firstName) || trim($firstName) === '') {
                $fullNameValue = $getValue('FullName', $rowData) ?: $getValue('full_name', $rowData) ?: $getValue('Full Name', $rowData) ?: $getValue('Name', $rowData);
                if ($fullNameValue) {
                    $fullName = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', $fullNameValue);
                    $fullName = trim($fullName);
                    $fullName = ltrim($fullName, "Â \t\n\r\0\x0B");
                    if (!empty($fullName) && strlen($fullName) > 0) {
                        // Split name by space - first word is first_name, rest is last_name
                        $nameParts = preg_split('/\s+/', $fullName, 2);
                        if (!empty($nameParts[0])) {
                            $firstName = preg_replace('/[^\p{L}\p{N}\s\-\.\']/u', '', $nameParts[0]);
                            $firstName = trim($firstName);
                            if (isset($nameParts[1]) && !empty(trim($nameParts[1]))) {
                                $lastName = preg_replace('/[^\p{L}\p{N}\s\-\.\']/u', '', trim($nameParts[1]));
                                $lastName = trim($lastName);
                            }
                        }
                    }
                }
            }
            
            // Final cleanup - ensure firstName is not empty string
            $firstName = trim($firstName);
            if ($lastName) {
                $lastName = trim($lastName);
            }
            
            // Get Reference value - try multiple column name variations
            $reference = null;
            if (!empty($rowData['Reference'])) {
                $reference = trim($rowData['Reference']);
            } elseif (!empty($rowData['reference'])) {
                $reference = trim($rowData['reference']);
            } elseif (!empty($rowData['REFERENCE'])) {
                $reference = trim($rowData['REFERENCE']);
            }
            
            // Map CSV columns to database fields
            $leadData = [
                'flg_reference' => $reference,
                'sub_reference' => $getValue('SubReference', $rowData) ?: $getValue('sub_reference', $rowData),
                'title' => $getValue('Title', $rowData),
                'first_name' => $firstName ?: '',
                'last_name' => $lastName,
                'company' => $getValue('CompanyName', $rowData) ?: $getValue('company', $rowData),
                'email' => $getValue('Email', $rowData) ?: $getValue('email', $rowData),
                'phone' => $getValue('Telephone1', $rowData) ?: $getValue('phone', $rowData),
                'alternative_phone' => $getValue('Telephone2', $rowData) ?: $getValue('alternative_phone', $rowData),
                'city' => $getValue('TownCity', $rowData) ?: $getValue('city', $rowData),
                'postcode' => $getValue('Postcode', $rowData) ?: $getValue('postcode', $rowData),
                'note' => $getValue('LastNote', $rowData) ?: $getValue('note', $rowData),
                'added_by' => auth()->id(),
                
                // Extended fields
                'lead_type' => $getValue('LeadType', $rowData),
                'progress' => $getValue('Progress', $rowData),
                'permission_to_call' => $this->parseBoolean($getValue('PermissionToCall', $rowData)),
                'permission_to_text' => $this->parseBoolean($getValue('PermissionToText', $rowData)),
                'permission_to_email' => $this->parseBoolean($getValue('PermissionToEmail', $rowData)),
                'permission_to_mail' => $this->parseBoolean($getValue('PermissionToMail', $rowData)),
                'permission_to_fax' => $this->parseBoolean($getValue('PermissionToFax', $rowData)),
                'site_id' => $getValue('SiteID', $rowData),
                'site_name' => $getValue('SiteName', $rowData),
                'user_id' => $getValue('UserID', $rowData),
                'user_name' => $getValue('User', $rowData),
                'buyer_id' => $getValue('BuyerID', $rowData),
                'buyer_name' => $getValue('Buyer', $rowData),
                'buyer_reference' => $getValue('BuyerReference', $rowData),
                'introducer_id' => $getValue('IntroducerID', $rowData),
                'introducer_name' => $getValue('Introducer', $rowData),
                'introducer_reference' => $getValue('IntroducerReference', $rowData),
                'cost' => $this->parseDecimal($getValue('Cost', $rowData)),
                'value' => $this->parseDecimal($getValue('Value', $rowData)),
                'ip_address' => $getValue('IPAddress', $rowData),
                'marketing_source' => $getValue('MarketingSource', $rowData),
                'marketing_medium' => $getValue('MarketingMedium', $rowData),
                'marketing_term' => $getValue('MarketingTerm', $rowData),
                'transfer_date_time' => $this->parseDateTime($getValue('TransferDateTime', $rowData)),
                'transfer_successful' => $this->parseBoolean($getValue('TransferSuccessful', $rowData)),
                'xml_post' => $getValue('XMLPost', $rowData),
                'xml_response' => $getValue('XMLResponse', $rowData),
                'xml_date_time' => $this->parseDateTime($getValue('XMLDateTime', $rowData)),
                'xml_fails' => $this->parseInteger($getValue('XMLFails', $rowData)),
                'xml_result' => $getValue('XMLResult', $rowData),
                'xml_reference' => $getValue('XMLReference', $rowData),
                'appointment_date_time' => $this->parseDateTime($getValue('AppointmentDateTime', $rowData)),
                'appointment_notes' => $getValue('AppointmentNotes', $rowData),
                'return_status' => $getValue('ReturnStatus', $rowData),
                'return_date_time' => $this->parseDateTime($getValue('ReturnDateTime', $rowData)),
                'return_reason' => $getValue('ReturnReason', $rowData),
                'return_decision_date_time' => $this->parseDateTime($getValue('ReturnDecisionDateTime', $rowData)),
                'return_decision_user' => $getValue('ReturnDecisionUser', $rowData),
                'return_decision_information' => $getValue('ReturnDecisionInformation', $rowData),
                'last_note_date_time' => $this->parseDateTime($getValue('LastNoteDateTime', $rowData)),
                'task_exists' => $this->parseBoolean($getValue('TaskExists', $rowData)),
                'workflow_exists' => $this->parseBoolean($getValue('WorkflowExists', $rowData)),
                'full_name' => $getValue('FullName', $rowData),
                'job_title' => $getValue('JobTitle', $rowData),
                'fax' => $getValue('Fax', $rowData),
                'address2' => $getValue('Address2', $rowData),
                'address3' => $getValue('Address3', $rowData),
                'contact_time' => $getValue('ContactTime', $rowData),
            ];
            
            // Handle address - combine Address, Address2, Address3 if they exist
            $addressParts = [];
            if ($getValue('Address', $rowData)) {
                $addressParts[] = $getValue('Address', $rowData);
            }
            if ($getValue('Address2', $rowData)) {
                $addressParts[] = $getValue('Address2', $rowData);
            }
            if ($getValue('Address3', $rowData)) {
                $addressParts[] = $getValue('Address3', $rowData);
            }
            if (!empty($addressParts)) {
                $leadData['address'] = implode("\n", $addressParts);
            } elseif ($getValue('address', $rowData)) {
                $leadData['address'] = $getValue('address', $rowData);
            }
            
            // Add Data1-Data50 fields
            for ($i = 1; $i <= 50; $i++) {
                $leadData["data{$i}"] = $getValue("Data{$i}", $rowData);
            }
            
            // Add Type1-Type50 fields
            for ($i = 1; $i <= 50; $i++) {
                $leadData["type{$i}"] = $getValue("Type{$i}", $rowData);
            }
            
            // Handle date of birth (DOBDay, DOBMonth, DOBYear)
            if (!empty($rowData['DOBDay']) && !empty($rowData['DOBMonth']) && !empty($rowData['DOBYear'])) {
                try {
                    $dobDay = (int)$rowData['DOBDay'];
                    $dobMonth = (int)$rowData['DOBMonth'];
                    $dobYear = (int)$rowData['DOBYear'];
                    
                    if ($dobDay >= 1 && $dobDay <= 31 && $dobMonth >= 1 && $dobMonth <= 12 && $dobYear >= 1900 && $dobYear <= 2100) {
                        $dobString = sprintf('%04d-%02d-%02d', $dobYear, $dobMonth, $dobDay);
                        $leadData['date_of_birth'] = $dobString;
                    }
                } catch (\Exception $e) {
                    // Invalid date, skip
                }
            }
            
            // Handle received date (ReceivedDateTime)
            if (!empty($rowData['ReceivedDateTime'])) {
                try {
                    // Try to parse d/m/Y H:i format
                    $receivedDate = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $rowData['ReceivedDateTime']);
                    $leadData['received_date'] = $receivedDate->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        // Try d/m/Y format
                        $receivedDate = \Carbon\Carbon::createFromFormat('d/m/Y', $rowData['ReceivedDateTime']);
                        $leadData['received_date'] = $receivedDate->format('Y-m-d');
                    } catch (\Exception $e2) {
                        // Try other formats
                        try {
                            $receivedDate = \Carbon\Carbon::parse($rowData['ReceivedDateTime']);
                            $leadData['received_date'] = $receivedDate->format('Y-m-d');
                        } catch (\Exception $e3) {
                            // Invalid date, skip
                        }
                    }
                }
            }

            // Handle project lookup (LeadGroup or LeadGroupID) - try multiple column name variations
            $project = null;
            $leadGroupID = null;
            $leadGroup = null;
            
            // Helper function to get value with case-insensitive search
            $getProjectValue = function($key, $rowData) {
                // Try exact match first
                if (isset($rowData[$key]) && !empty(trim($rowData[$key]))) {
                    return trim($rowData[$key]);
                }
                // Try case-insensitive match
                foreach ($rowData as $colName => $value) {
                    if (strtolower(trim($colName)) === strtolower(trim($key)) && !empty(trim($value))) {
                        return trim($value);
                    }
                }
                return null;
            };
            
            $leadGroupID = $getProjectValue('LeadGroupID', $rowData);
            $leadGroup = $getProjectValue('LeadGroup', $rowData);
            
            // Clean up values - remove special characters from XLS conversion
            if ($leadGroupID) {
                $leadGroupID = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', $leadGroupID);
                $leadGroupID = trim($leadGroupID);
            }
            if ($leadGroup) {
                $leadGroup = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', $leadGroup);
                $leadGroup = trim($leadGroup);
            }
            
            if ($leadGroupID) {
                // First, try to find by flg_group_id
                $project = Project::where('flg_group_id', $leadGroupID)->first();
                
                // If not found, try to find by ID
                if (!$project && is_numeric($leadGroupID)) {
                    $project = Project::find($leadGroupID);
                }
                
                // If still not found, create a new project
                if (!$project) {
                    // Clean up the project name
                    $projectTitle = $leadGroup ? trim($leadGroup) : ('Project ' . $leadGroupID);
                    $projectTitle = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', $projectTitle);
                    $projectTitle = trim($projectTitle);
                    
                    // Check if a project with this title already exists (case-insensitive)
                    $existingProject = Project::whereRaw('LOWER(title) = ?', [strtolower($projectTitle)])->first();
                    if ($existingProject) {
                        // Use existing project and update its flg_group_id if needed
                        $project = $existingProject;
                        if (!$project->flg_group_id) {
                            $project->flg_group_id = $leadGroupID;
                            $project->save();
                        }
                    } else {
                        // Create new project
                        try {
                            $project = Project::create([
                                'flg_group_id' => $leadGroupID,
                                'title' => $projectTitle,
                                'description' => null,
                                'created_by_user_id' => auth()->id(),
                            ]);
                        } catch (\Exception $e) {
                            // If creation fails (e.g., duplicate name), try to find again
                            $project = Project::whereRaw('LOWER(title) = ?', [strtolower($projectTitle)])->first();
                            if ($project && !$project->flg_group_id) {
                                $project->flg_group_id = $leadGroupID;
                                $project->save();
                            } elseif (!$project) {
                                $errors[] = "Row " . ($index + 2) . ": Failed to create/find project '{$projectTitle}' - " . $e->getMessage();
                                continue;
                            }
                        }
                    }
                }
            } elseif ($leadGroup) {
                // Clean up the project name
                $leadGroup = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', trim($leadGroup));
                $leadGroup = trim($leadGroup);
                
                if (!empty($leadGroup)) {
                    // Try to find by title (exact match first, then case-insensitive)
                    $project = Project::where('title', $leadGroup)->first();
                    if (!$project) {
                        $project = Project::whereRaw('LOWER(title) = ?', [strtolower($leadGroup)])->first();
                    }
                    
                    // If not found, create a new project
                    if (!$project) {
                        try {
                            $project = Project::create([
                                'flg_group_id' => null,
                                'title' => $leadGroup,
                                'description' => null,
                                'created_by_user_id' => auth()->id(),
                            ]);
                        } catch (\Exception $e) {
                            // If creation fails (e.g., duplicate name), try to find again
                            $project = Project::where('title', $leadGroup)->first();
                            if (!$project) {
                                $project = Project::whereRaw('LOWER(title) = ?', [strtolower($leadGroup)])->first();
                            }
                            if (!$project) {
                                $errors[] = "Row " . ($index + 2) . ": Failed to create/find project '{$leadGroup}' - " . $e->getMessage();
                                continue;
                            }
                        }
                    }
                } else {
                    $errors[] = "Row " . ($index + 2) . ": Project (LeadGroup or LeadGroupID) is required";
                    continue;
                }
            } else {
                $errors[] = "Row " . ($index + 2) . ": Project (LeadGroup or LeadGroupID) is required";
                continue;
            }
            
            // Set the project_id for the lead
            $leadData['project_id'] = $project->id;

            // Handle LeadType lookup and creation
            $leadTypeName = $getValue('LeadType', $rowData);
            if (!empty($leadTypeName)) {
                // Clean up the lead type name
                $leadTypeName = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', trim($leadTypeName));
                $leadTypeName = trim($leadTypeName);
                
                if (!empty($leadTypeName)) {
                    // Try to find existing lead type by name (case-insensitive)
                    $leadType = LeadType::whereRaw('LOWER(name) = ?', [strtolower($leadTypeName)])->first();
                    
                    // If not found, create a new lead type
                    if (!$leadType) {
                        try {
                            $leadType = LeadType::create([
                                'name' => $leadTypeName,
                                'description' => null,
                                'color' => '#007bff', // Default color
                                'order' => 0,
                                'is_active' => true,
                                'created_by' => auth()->id(),
                            ]);
                        } catch (\Exception $e) {
                            // If creation fails (e.g., duplicate name), try to find again
                            $leadType = LeadType::whereRaw('LOWER(name) = ?', [strtolower($leadTypeName)])->first();
                            if (!$leadType) {
                                $errors[] = "Row " . ($index + 2) . ": Failed to create/find lead type '{$leadTypeName}' - " . $e->getMessage();
                            }
                        }
                    }
                    
                    // Set lead_type_id if lead type was found/created
                    if ($leadType) {
                        $leadData['lead_type_id'] = $leadType->id;
                    }
                }
            }

            // Handle Status lookup and creation
            $statusName = $getValue('Status', $rowData);
            if (!empty($statusName) && isset($leadData['project_id'])) {
                // Clean up the status name
                $statusName = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', trim($statusName));
                $statusName = trim($statusName);
                
                if (!empty($statusName)) {
                    // Try to find existing status by name for this project (case-insensitive)
                    $status = Status::where('project_id', $leadData['project_id'])
                        ->whereRaw('LOWER(name) = ?', [strtolower($statusName)])
                        ->first();
                    
                    // If not found, create a new status for this project
                    if (!$status) {
                        try {
                            $status = Status::create([
                                'project_id' => $leadData['project_id'],
                                'name' => $statusName,
                                'color' => 'secondary', // Default color
                                'order' => 0,
                                'is_default' => false,
                            ]);
                        } catch (\Exception $e) {
                            // If creation fails (e.g., duplicate name for same project), try to find again
                            $status = Status::where('project_id', $leadData['project_id'])
                                ->whereRaw('LOWER(name) = ?', [strtolower($statusName)])
                                ->first();
                            if (!$status) {
                                $errors[] = "Row " . ($index + 2) . ": Failed to create/find status '{$statusName}' for project - " . $e->getMessage();
                            }
                        }
                    }
                    
                    // Set status_id if status was found/created
                    if ($status) {
                        $leadData['status_id'] = $status->id;
                    }
                }
            }
            
            // Handle UserID (added_by)
            if (!empty($rowData['UserID'])) {
                $user = User::find($rowData['UserID']);
                if ($user) {
                    $leadData['added_by'] = $user->id;
                }
            }

            // Validate required fields - check if first_name is actually empty after all processing
            if (empty($leadData['first_name']) || trim($leadData['first_name']) === '') {
                // Try one more time to get from FullName if available
                if (empty($leadData['first_name'])) {
                    $fullNameValue = $getValue('FullName', $rowData) ?: $getValue('Name', $rowData);
                    if ($fullNameValue) {
                        $fullName = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', trim($fullNameValue));
                        $fullName = ltrim($fullName, "Â \t\n\r\0\x0B");
                        if (!empty($fullName)) {
                            $nameParts = preg_split('/\s+/', $fullName, 2);
                            $leadData['first_name'] = $nameParts[0];
                            if (isset($nameParts[1])) {
                                $leadData['last_name'] = trim($nameParts[1]);
                            }
                        }
                    }
                }
                
                // Final validation - check what columns are actually available for debugging
                if (empty($leadData['first_name']) || trim($leadData['first_name']) === '') {
                    $availableColumns = array_keys(array_filter($rowData, function($v) { return !empty(trim($v)); }));
                    $errors[] = "Row " . ($index + 2) . ": First Name is required. Available columns with data: " . implode(', ', array_slice($availableColumns, 0, 10));
                    continue;
                }
            }
            
            // Check if lead with same flg_reference already exists
            $existingLead = null;
            if (!empty($leadData['flg_reference'])) {
                // Check by flg_reference
                $existingLead = Lead::where('flg_reference', $leadData['flg_reference'])->first();
                
                // If not found, check if Reference matches an existing lead ID
                if (!$existingLead && is_numeric($leadData['flg_reference'])) {
                    $existingLead = Lead::find($leadData['flg_reference']);
                }
                
                // If lead exists, show message and skip
                if ($existingLead) {
                    $errors[] = "Row " . ($index + 2) . ": Lead with Reference '{$leadData['flg_reference']}' already exists (Lead ID: {$existingLead->id}, Name: {$existingLead->first_name} {$existingLead->last_name})";
                    continue;
                }
            }

            try {
                // Create new lead
                Lead::create($leadData);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        // Log the action
        if ($imported > 0) {
            Log::createLog(auth()->id(), 'import_leads', "Imported {$imported} leads from CSV");
        }

        // If there are errors or no leads imported, return error response
        if (count($errors) > 0 || $imported === 0) {
            $errorMessage = "Import failed. ";
            if ($imported === 0) {
                $errorMessage .= "No leads were imported. ";
            } else {
                $errorMessage .= "Only {$imported} lead(s) imported. ";
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
            'message' => "Successfully imported {$imported} lead(s).",
            'imported' => $imported,
            'errors' => $errors
        ]);
    }

    /**
     * Parse boolean value from CSV
     */
    private function parseBoolean($value)
    {
        if (empty($value)) {
            return false;
        }
        $value = strtolower(trim($value));
        return in_array($value, ['yes', 'true', '1', 'y']);
    }

    /**
     * Parse decimal value from CSV
     */
    private function parseDecimal($value)
    {
        if (empty($value) || $value === '0' || $value === 0) {
            return null;
        }
        // Handle scientific notation (e.g., 9.71582E+11)
        if (stripos($value, 'e') !== false) {
            $value = (float)$value;
        }
        $value = preg_replace('/[^0-9.-]/', '', $value);
        return !empty($value) ? (float)$value : null;
    }

    /**
     * Parse integer value from CSV
     */
    private function parseInteger($value)
    {
        if (empty($value) || $value === '0' || $value === 0) {
            return 0;
        }
        $value = preg_replace('/[^0-9-]/', '', $value);
        return !empty($value) ? (int)$value : 0;
    }

    /**
     * Parse datetime value from CSV
     */
    private function parseDateTime($value)
    {
        if (empty($value) || $value === '0000-00-00 00:00:00' || $value === '0000-00-00') {
            return null;
        }
        try {
            // Try d/m/Y H:i format first
            $date = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $value);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            try {
                // Try d/m/Y format
                $date = \Carbon\Carbon::createFromFormat('d/m/Y', $value);
                return $date->format('Y-m-d H:i:s');
            } catch (\Exception $e2) {
                try {
                    // Try standard formats
                    $date = \Carbon\Carbon::parse($value);
                    return $date->format('Y-m-d H:i:s');
                } catch (\Exception $e3) {
                    return null;
                }
            }
        }
    }
}

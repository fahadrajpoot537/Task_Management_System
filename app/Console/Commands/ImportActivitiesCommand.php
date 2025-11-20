<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Models\Log;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ImportActivitiesCommand extends Command
{
    protected $signature = 'activities:import 
                            {file : The path to the CSV file to import}
                            {--user-id= : The user ID to use for created_by field (defaults to 1)}
                            {--export-errors= : Optional path to export errors to a CSV file}';
    protected $description = 'Import activities from a CSV file';

    public function handle()
    {
        // Increase memory limit for large imports
        ini_set('memory_limit', '1024M');
        
        $filePath = $this->argument('file');
        $userId = $this->option('user-id') ?: 1;

        if (preg_match('/^[A-Za-z]:/', $filePath)) {
            $filePath = str_replace('/', '\\', $filePath);
        } else {
            $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
        }
        
        if (strpos($filePath, '/') === 0 && !preg_match('/^[A-Za-z]:/', $filePath)) {
            $filePath = ltrim($filePath, '/');
        }

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return Command::FAILURE;
        }

        $this->info("Starting import from: {$filePath}");
        $this->info("Using user: {$user->name} (ID: {$userId})");
        $this->newLine();

        try {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                $this->error("Failed to open file: {$filePath}");
                return Command::FAILURE;
            }

            $headers = fgetcsv($handle);
            if ($headers === false) {
                $this->error("Failed to read header row from CSV file");
                fclose($handle);
                return Command::FAILURE;
            }
            
            if (!empty($headers[0])) {
                $headers[0] = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $headers[0]);
            }
            
            $headers = array_map('trim', $headers);
            
            // For large files, skip row counting to save memory
            $this->info("Processing CSV file with multi-line record detection...");
            $this->newLine();

            $imported = 0;
            $errors = [];
            $errorCategories = [
                'duplicate_row_csv' => [],
                'continuation_row' => [],
                'invalid_reference' => [],
                'missing_reference' => [],
                'lead_creation_failed' => [],
                'missing_activity_type' => [],
                'duplicate_activity_id' => [],
                'duplicate_activity' => [],
                'database_error' => [],
                'other' => []
            ];
            $progressBar = null; // Will create after first row if needed
            $showProgress = false;

            $index = 0;
            $processedRows = []; // Limit size to prevent memory issues

            // Read multi-line CSV records
            while (($row = $this->readMultiLineCsvRecord($handle, $headers)) !== false) {
                $index++;
                
                // Initialize progress bar on first row
                if ($progressBar === null && $index === 1) {
                    $progressBar = $this->output->createProgressBar();
                    $progressBar->start();
                    $showProgress = true;
                }
                
                // Wrap entire row processing in try-catch to prevent crashes on corrupted data
                try {
                    if (empty(array_filter($row))) {
                        if ($progressBar) $progressBar->advance();
                        continue;
                    }
                
                while (count($row) < count($headers)) {
                    $row[] = '';
                }
                
                $row = array_map('trim', $row);
                $rowData = array_combine($headers, $row);
                
                $getValue = function($key, $rowData) {
                    if (isset($rowData[$key]) && !empty(trim($rowData[$key]))) {
                        return trim($rowData[$key]);
                    }
                    foreach ($rowData as $colName => $value) {
                        if (strtolower(trim($colName)) === strtolower(trim($key)) && !empty(trim($value))) {
                            return trim($value);
                        }
                    }
                    return null;
                };
                
                $reference = $getValue('Reference', $rowData) ?: '';
                $activityType = $getValue('ActivityType', $rowData) ?: '';
                $activityDateTime = $getValue('ActivityDateTime', $rowData) ?: '';
                // Extract ActivityID early so we can include it in all errors
                $csvActivityId = $getValue('ActivityID', $rowData);
                $rowKey = md5($reference . '|' . $activityType . '|' . $activityDateTime);
                
                // Helper function to create error with ActivityID
                $createError = function($message, $category) use (&$errors, &$errorCategories, $index, $csvActivityId) {
                    $errorMsg = $message;
                    $errors[] = $errorMsg;
                    // Store as array with ActivityID if available
                    if (!empty($csvActivityId)) {
                        $errorCategories[$category][] = ['message' => $errorMsg, 'activity_id' => (string)$csvActivityId, 'row' => $index + 2];
                    } else {
                        $errorCategories[$category][] = $errorMsg;
                    }
                };
                
                // Limit processedRows array size to prevent memory issues (keep last 1000)
                if (count($processedRows) > 1000) {
                    $processedRows = array_slice($processedRows, -1000, 1000, true);
                }
                
                if (isset($processedRows[$rowKey])) {
                    $createError("Row " . ($index + 2) . ": Duplicate row in CSV file", 'duplicate_row_csv');
                    if ($progressBar) $progressBar->advance();
                    continue;
                }
                $processedRows[$rowKey] = $index;
                
                $hasReference = !empty($reference) && trim($reference) !== '';
                $hasActivityType = !empty($activityType) && trim($activityType) !== '';
                
                if (!$hasReference && !$hasActivityType) {
                    $hasAnyData = false;
                    foreach ($rowData as $key => $value) {
                        if (!empty(trim($value)) && strlen(trim($value)) > 3) {
                            $hasAnyData = true;
                            break;
                        }
                    }
                    if (!$hasAnyData) {
                        if ($progressBar) $progressBar->advance();
                        continue;
                    }
                    $createError("Row " . ($index + 2) . ": Appears to be a continuation row - skipping", 'continuation_row');
                    if ($progressBar) $progressBar->advance();
                    continue;
                }
                
                $lead = null;
                $reference = $getValue('Reference', $rowData);
                
                if (empty($reference) || 
                    strlen($reference) > 100 || 
                    strpos($reference, "\n") !== false ||
                    strpos($reference, "\r") !== false ||
                    stripos($reference, 'Telephone:') !== false ||
                    stripos($reference, 'Email:') !== false ||
                    trim($reference) === '' ||
                    $reference === 'Test Test'
                ) {
                    $createError("Row " . ($index + 2) . ": Invalid or empty Reference value", 'invalid_reference');
                    if ($progressBar) $progressBar->advance();
                    continue;
                }
                
                if ($reference) {
                    $lead = Lead::where('flg_reference', $reference)->first();
                    
                    if (!$lead && is_numeric($reference)) {
                        $lead = Lead::find($reference);
                    }
                    
                    if (!$lead) {
                        $leadName = $getValue('Name', $rowData) ?: '';
                        $leadCompany = $getValue('Company', $rowData);
                        
                        $firstName = 'Unknown';
                        $lastName = null;
                        if (!empty($leadName)) {
                            $nameParts = explode(' ', $leadName, 2);
                            $firstName = $nameParts[0];
                            $lastName = isset($nameParts[1]) ? $nameParts[1] : null;
                        }
                        
                        $projectId = null;
                        $leadGroupID = $getValue('LeadGroupID', $rowData);
                        $leadGroup = $getValue('LeadGroup', $rowData);
                        
                        if ($leadGroupID) {
                            $project = Project::where('flg_group_id', $leadGroupID)->first();
                            if ($project) {
                                $projectId = $project->id;
                            } else {
                                $projectTitle = $leadGroup ?: ('Project ' . $leadGroupID);
                                $project = Project::create([
                                    'flg_group_id' => $leadGroupID,
                                    'title' => $projectTitle,
                                    'description' => null,
                                    'created_by_user_id' => $userId,
                                ]);
                                $projectId = $project->id;
                            }
                        } elseif ($leadGroup) {
                            $project = Project::where('title', $leadGroup)->first();
                            if ($project) {
                                $projectId = $project->id;
                            } else {
                                $project = Project::create([
                                    'flg_group_id' => null,
                                    'title' => $leadGroup,
                                    'description' => null,
                                    'created_by_user_id' => $userId,
                                ]);
                                $projectId = $project->id;
                            }
                        } else {
                            $defaultProject = Project::first();
                            if (!$defaultProject) {
                                $defaultProject = Project::create([
                                    'flg_group_id' => null,
                                    'title' => 'Default Project',
                                    'description' => 'Auto-created for imported leads',
                                    'created_by_user_id' => $userId,
                                ]);
                            }
                            $projectId = $defaultProject->id;
                        }
                        
                        $receivedDate = null;
                        $receivedDateTime = $getValue('ReceivedDateTime', $rowData);
                        if (!empty($receivedDateTime)) {
                            $receivedDateTime = $this->sanitizeDateString($receivedDateTime);
                            if ($receivedDateTime) {
                                try {
                                    $receivedDateObj = Carbon::createFromFormat('d/m/Y H:i', $receivedDateTime);
                                    $receivedDate = $receivedDateObj->format('Y-m-d');
                                } catch (\Exception $e) {
                                    try {
                                        $receivedDateObj = Carbon::createFromFormat('d/m/Y', $receivedDateTime);
                                        $receivedDate = $receivedDateObj->format('Y-m-d');
                                    } catch (\Exception $e2) {
                                        $receivedDate = now()->format('Y-m-d');
                                    }
                                }
                            } else {
                                $receivedDate = now()->format('Y-m-d');
                            }
                        } else {
                            $receivedDate = now()->format('Y-m-d');
                        }
                        
                        try {
                            $lead = Lead::create([
                                'flg_reference' => $reference,
                                'sub_reference' => $getValue('SubReference', $rowData),
                                'project_id' => $projectId,
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                                'company' => $leadCompany,
                                'received_date' => $receivedDate,
                                'added_by' => $userId,
                            ]);
                        } catch (\Exception $e) {
                            $createError("Row " . ($index + 2) . ": Failed to create lead - " . $e->getMessage(), 'lead_creation_failed');
                            if ($progressBar) $progressBar->advance();
                            continue;
                        }
                    }
                } else {
                    $createError("Row " . ($index + 2) . ": Reference is required", 'missing_reference');
                    if ($progressBar) $progressBar->advance();
                    continue;
                }
                
                $activityType = null;
                $typeColumns = ['ActivityType', 'activity_type', 'Activity_Type', 'Type', 'type', 'ActivityTypeName', 'activityType'];
                
                foreach ($typeColumns as $colName) {
                    if (isset($rowData[$colName]) && !empty(trim($rowData[$colName]))) {
                        $activityType = trim($rowData[$colName]);
                        break;
                    }
                }
                
                if (empty($activityType)) {
                    foreach ($rowData as $colName => $colValue) {
                        $colNameLower = strtolower(trim($colName));
                        if (in_array($colNameLower, ['activitytype', 'activity_type', 'type']) && !empty(trim($colValue))) {
                            $activityType = trim($colValue);
                            break;
                        }
                    }
                }
                
                // Set default type if missing (store activity even without type)
                if (empty($activityType) || trim($activityType) === '') {
                    $activityType = 'Unknown'; // Default type when not provided
                }
                
                // Get ActivityDateTime early for duplicate check
                $activityDateValue = null;
                $dateColumns = ['ActivityDateTime', 'activity_date_time', 'Activity_DateTime', 'Date', 'date', 'ActivityDate'];
                foreach ($dateColumns as $colName) {
                    $dateValue = $getValue($colName, $rowData);
                    if (!empty($dateValue) && trim($dateValue) !== '' && trim($dateValue) !== '0000-00-00 00:00:00') {
                        $activityDateValue = trim($dateValue);
                        break;
                    }
                }
                
                // Parse ActivityDateTime for duplicate check
                $activityDateTimeForComparison = null;
                $activityDate = null;
                if ($activityDateValue) {
                    $activityDateValue = $this->sanitizeDateString($activityDateValue);
                    if ($activityDateValue) {
                        try {
                            $activityDateTimeForComparison = Carbon::createFromFormat('Y-m-d H:i:s', $activityDateValue);
                            $activityDate = $activityDateTimeForComparison->format('Y-m-d');
                        } catch (\Exception $e) {
                            try {
                                $activityDateTimeForComparison = Carbon::createFromFormat('d/m/Y H:i:s', $activityDateValue);
                                $activityDate = $activityDateTimeForComparison->format('Y-m-d');
                            } catch (\Exception $e2) {
                                try {
                                    $activityDateTimeForComparison = Carbon::createFromFormat('Y-m-d H:i', $activityDateValue);
                                    $activityDate = $activityDateTimeForComparison->format('Y-m-d');
                                } catch (\Exception $e3) {
                                    try {
                                        $activityDateTimeForComparison = Carbon::createFromFormat('d/m/Y H:i', $activityDateValue);
                                        $activityDate = $activityDateTimeForComparison->format('Y-m-d');
                                    } catch (\Exception $e4) {
                                        try {
                                            $activityDateTimeForComparison = Carbon::parse($activityDateValue);
                                            if ($activityDateTimeForComparison->year > 1900 && $activityDateTimeForComparison->year < 2100) {
                                                $activityDate = $activityDateTimeForComparison->format('Y-m-d');
                                            }
                                        } catch (\Exception $e5) {
                                            // If parsing fails, try to get just the date
                                            try {
                                                $activityDate = Carbon::parse($activityDateValue)->format('Y-m-d');
                                            } catch (\Exception $e6) {
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                if (empty($activityDate)) {
                    $activityDate = now()->format('Y-m-d');
                }
                
                // CHECK DATABASE FIRST for duplicates before processing any other data
                // This prevents unnecessary processing if data already exists
                $existingActivity = null;
                
                // First check: If ActivityID exists in CSV, check if it's already in database
                if (!empty($csvActivityId)) {
                    $existingActivity = Activity::find($csvActivityId);
                    if ($existingActivity) {
                        $errorMsg = "Row " . ($index + 2) . ": Activity with ID '{$csvActivityId}' already exists in database";
                        $errors[] = $errorMsg;
                        // Ensure Activity ID is stored as string
                        $activityIdStr = (string)$csvActivityId;
                        $errorCategories['duplicate_activity_id'][] = ['message' => $errorMsg, 'activity_id' => $activityIdStr, 'row' => $index + 2];
                        if ($progressBar) $progressBar->advance();
                        continue;
                    }
                }
                
                // Second check: Check by ActivityDateTime + lead_id + type
                if ($activityDateTimeForComparison && !$existingActivity) {
                    // Get all activities for this lead with the same date and type
                    $matchingActivities = Activity::where('lead_id', $lead->id)
                        ->where('type', $activityType)
                        ->where('date', $activityDate)
                        ->get();
                    
                    foreach ($matchingActivities as $match) {
                        if ($match->created_at) {
                            try {
                                $matchTimestamp = Carbon::parse($match->created_at);
                                // Compare timestamps - check if they match exactly or within 2 seconds
                                $diffInSeconds = abs($matchTimestamp->diffInSeconds($activityDateTimeForComparison));
                                
                                // Match if within 2 seconds (more lenient for exact duplicates)
                                if ($diffInSeconds <= 2) {
                                    $existingActivity = $match;
                                    break;
                                }
                                
                                // Also check if the dates and times are the same (ignoring seconds)
                                if ($matchTimestamp->format('Y-m-d H:i') === $activityDateTimeForComparison->format('Y-m-d H:i')) {
                                    $existingActivity = $match;
                                    break;
                                }
                            } catch (\Exception $e) {
                                // If parsing fails, continue to next activity
                                continue;
                            }
                        }
                    }
                }
                
                // If duplicate found, skip all processing
                if ($existingActivity) {
                    $activityId = $existingActivity->id;
                    $activityDateTimeStr = $activityDateTimeForComparison ? $activityDateTimeForComparison->format('Y-m-d H:i:s') : 'N/A';
                    
                    // Include CSV ActivityID if present
                    $csvActivityIdStr = !empty($csvActivityId) ? "CSV ActivityID: {$csvActivityId}, " : '';
                    
                    $errorMsg = "Row " . ($index + 2) . ": Duplicate activity detected in database ({$csvActivityIdStr}Existing Activity ID: {$activityId}, ActivityDateTime: {$activityDateTimeStr})";
                    $errors[] = $errorMsg;
                    
                    // Use CSV ActivityID if available, otherwise use existing activity ID
                    $errorActivityId = !empty($csvActivityId) ? $csvActivityId : $activityId;
                    // Ensure we store it as a string for consistency
                    $errorActivityId = !empty($errorActivityId) ? (string)$errorActivityId : '';
                    $errorCategories['duplicate_activity'][] = ['message' => $errorMsg, 'activity_id' => $errorActivityId, 'row' => $index + 2];
                    if ($progressBar) $progressBar->advance();
                    continue;
                }
                
                // Now proceed with processing the rest of the activity data
                $activityData = [
                    'lead_id' => $lead->id,
                    'type' => $activityType,
                    'date' => $activityDate, // Use already parsed date
                    'field_1' => $getValue('ActivityField1', $rowData) ?: $getValue('field_1', $rowData),
                    'field_2' => $getValue('ActivityField2', $rowData) ?: $getValue('field_2', $rowData),
                    'email' => $getValue('ActivityEmail', $rowData) ?: $getValue('email', $rowData),
                    'bcc' => $getValue('ActivityBcc', $rowData) ?: $getValue('bcc', $rowData),
                    'cc' => $getValue('ActivityCc', $rowData) ?: $getValue('cc', $rowData),
                    'phone' => $getValue('ActivityPhone', $rowData) ?: $getValue('phone', $rowData),
                    'created_by' => $userId,
                ];
                
                $dueDateTime = $getValue('ActivityDueDateTime', $rowData);
                if (!empty($dueDateTime) && $dueDateTime !== '0000-00-00 00:00:00') {
                    $dueDateTime = $this->sanitizeDateString($dueDateTime);
                    if ($dueDateTime) {
                        try {
                            $dueDate = Carbon::createFromFormat('d/m/Y H:i', $dueDateTime);
                            $activityData['due_date'] = $dueDate->format('Y-m-d');
                        } catch (\Exception $e) {
                            try {
                                $dueDate = Carbon::createFromFormat('d/m/Y', $dueDateTime);
                                $activityData['due_date'] = $dueDate->format('Y-m-d');
                            } catch (\Exception $e2) {
                                try {
                                    $dueDate = Carbon::parse($dueDateTime);
                                    $activityData['due_date'] = $dueDate->format('Y-m-d');
                                } catch (\Exception $e3) {
                                }
                            }
                        }
                    }
                }
                
                $endDateTime = $getValue('ActivityEndDateTime', $rowData);
                if (!empty($endDateTime) && $endDateTime !== '0000-00-00 00:00:00') {
                    $endDateTime = $this->sanitizeDateString($endDateTime);
                    if ($endDateTime) {
                        try {
                            $endDate = Carbon::createFromFormat('d/m/Y H:i', $endDateTime);
                            $activityData['end_date'] = $endDate->format('Y-m-d');
                        } catch (\Exception $e) {
                            try {
                                $endDate = Carbon::createFromFormat('d/m/Y', $endDateTime);
                                $activityData['end_date'] = $endDate->format('Y-m-d');
                            } catch (\Exception $e2) {
                                try {
                                    $endDate = Carbon::parse($endDateTime);
                                    $activityData['end_date'] = $endDate->format('Y-m-d');
                                } catch (\Exception $e3) {
                                }
                            }
                        }
                    }
                }
                
                $actionedValue = $getValue('ActivityActioned', $rowData);
                if (!empty($actionedValue)) {
                    $actionedValue = strtolower(trim($actionedValue));
                    $activityData['actioned'] = in_array($actionedValue, ['yes', '1', 'true', 'y']);
                }
                
                $priorityValue = $getValue('ActivityPriority', $rowData);
                if (!empty($priorityValue)) {
                    $priorityValue = strtolower(trim($priorityValue));
                    if (!in_array($priorityValue, ['yes', 'no', '1', '0', 'true', 'false', ''])) {
                        $activityData['priority'] = $getValue('ActivityPriority', $rowData);
                    }
                }
                
                $createdUserName = $getValue('ActivityCreatedUser', $rowData);
                if (!empty($createdUserName)) {
                    $createdUser = User::where('name', $createdUserName)->first();
                    if ($createdUser) {
                        $activityData['created_by'] = $createdUser->id;
                    }
                }
                
                $assignedUserName = $getValue('ActivityAssignedUser', $rowData);
                if (!empty($assignedUserName)) {
                    $assignedUser = User::where('name', $assignedUserName)->first();
                    if ($assignedUser) {
                        $activityData['assigned_to'] = $assignedUser->id;
                    }
                }
                
                // Ensure type is set (use default if still empty)
                if (empty($activityData['type']) || trim($activityData['type']) === '') {
                    $activityData['type'] = 'Unknown'; // Default type when not provided
                }
                
                if (empty($activityData['date']) || strpos($activityData['date'], '-0001') !== false) {
                    $activityData['date'] = now()->format('Y-m-d');
                }
                
                // CHECK DATABASE FIRST for duplicates before processing
                // This prevents unnecessary processing if data already exists
                $existingActivity = null;
                
                // First check: If ActivityID exists in CSV, check if it's already in database
                if (!empty($csvActivityId)) {
                    $existingActivity = Activity::find($csvActivityId);
                    if ($existingActivity) {
                        $errorMsg = "Row " . ($index + 2) . ": Activity with ID '{$csvActivityId}' already exists in database";
                        $errors[] = $errorMsg;
                        // Ensure Activity ID is stored as string
                        $activityIdStr = (string)$csvActivityId;
                        $errorCategories['duplicate_activity_id'][] = ['message' => $errorMsg, 'activity_id' => $activityIdStr, 'row' => $index + 2];
                        if ($progressBar) $progressBar->advance();
                        continue;
                    }
                }
                
                // Second check: Check by ActivityDateTime + lead_id + type
                // Parse ActivityDateTime early for duplicate check
                $activityDateTimeForComparison = null;
                if ($activityDateValue) {
                    $activityDateValue = $this->sanitizeDateString($activityDateValue);
                    if ($activityDateValue) {
                        try {
                            $activityDateTimeForComparison = Carbon::createFromFormat('Y-m-d H:i:s', $activityDateValue);
                        } catch (\Exception $e) {
                            try {
                                $activityDateTimeForComparison = Carbon::createFromFormat('d/m/Y H:i:s', $activityDateValue);
                            } catch (\Exception $e2) {
                                try {
                                    $activityDateTimeForComparison = Carbon::createFromFormat('Y-m-d H:i', $activityDateValue);
                                } catch (\Exception $e3) {
                                    try {
                                        $activityDateTimeForComparison = Carbon::createFromFormat('d/m/Y H:i', $activityDateValue);
                                    } catch (\Exception $e4) {
                                        try {
                                            $activityDateTimeForComparison = Carbon::parse($activityDateValue);
                                        } catch (\Exception $e5) {
                                            // If parsing fails, use the date only
                                            if ($activityData['date']) {
                                                $activityDateTimeForComparison = Carbon::parse($activityData['date'])->startOfDay();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Check database for duplicate by ActivityDateTime
                if ($activityDateTimeForComparison && !$existingActivity) {
                    // Get all activities for this lead with the same date and type
                    $matchingActivities = Activity::where('lead_id', $activityData['lead_id'])
                        ->where('type', $activityData['type'])
                        ->where('date', $activityData['date'])
                        ->get();
                    
                    foreach ($matchingActivities as $match) {
                        if ($match->created_at) {
                            try {
                                $matchTimestamp = Carbon::parse($match->created_at);
                                // Compare timestamps - check if they match exactly or within 2 seconds
                                $diffInSeconds = abs($matchTimestamp->diffInSeconds($activityDateTimeForComparison));
                                
                                // Match if within 2 seconds (more lenient for exact duplicates)
                                if ($diffInSeconds <= 2) {
                                    $existingActivity = $match;
                                    break;
                                }
                                
                                // Also check if the dates and times are the same (ignoring seconds)
                                if ($matchTimestamp->format('Y-m-d H:i') === $activityDateTimeForComparison->format('Y-m-d H:i')) {
                                    $existingActivity = $match;
                                    break;
                                }
                            } catch (\Exception $e) {
                                // If parsing fails, continue to next activity
                                continue;
                            }
                        }
                    }
                }
                
                // If duplicate found, skip processing
                if ($existingActivity) {
                    $activityId = $existingActivity->id;
                    $activityDateTimeStr = $activityDateTimeForComparison ? $activityDateTimeForComparison->format('Y-m-d H:i:s') : 'N/A';
                    
                    // Include CSV ActivityID if present
                    $csvActivityIdStr = !empty($csvActivityId) ? "CSV ActivityID: {$csvActivityId}, " : '';
                    
                    $errorMsg = "Row " . ($index + 2) . ": Duplicate activity detected in database ({$csvActivityIdStr}Existing Activity ID: {$activityId}, ActivityDateTime: {$activityDateTimeStr})";
                    $errors[] = $errorMsg;
                    
                    // Use CSV ActivityID if available, otherwise use existing activity ID
                    $errorActivityId = !empty($csvActivityId) ? $csvActivityId : $activityId;
                    // Ensure we store it as a string for consistency
                    $errorActivityId = !empty($errorActivityId) ? (string)$errorActivityId : '';
                    $errorCategories['duplicate_activity'][] = ['message' => $errorMsg, 'activity_id' => $errorActivityId, 'row' => $index + 2];
                    if ($progressBar) $progressBar->advance();
                    continue;
                }

                try {
                    // Create activity first
                    $activity = Activity::create($activityData);
                    
                    // Set created_at to match ActivityDateTime for proper duplicate detection
                    // This ensures duplicates can be detected by comparing ActivityDateTime
                    if ($activityDateTimeForComparison) {
                        // Update timestamps to match ActivityDateTime from CSV
                        // This is done after creation because created_at/updated_at are not fillable
                        $activity->created_at = $activityDateTimeForComparison;
                        $activity->updated_at = $activityDateTimeForComparison;
                        $activity->save();
                        // Refresh to ensure the timestamp is updated in the model instance
                        $activity->refresh();
                    }
                    
                    $imported++;
                } catch (\Exception $e) {
                    $createError("Row " . ($index + 2) . ": " . $e->getMessage(), 'database_error');
                }

                if ($progressBar) $progressBar->advance();
                
                // Periodically clear processedRows to free memory
                if ($index % 5000 === 0 && count($processedRows) > 500) {
                    $processedRows = array_slice($processedRows, -500, 500, true);
                }
                
                } catch (\Exception $e) {
                    // Catch any unexpected errors during row processing (e.g., corrupted date data)
                    $createError("Row " . ($index + 2) . ": Unexpected error - " . $e->getMessage(), 'database_error');
                    if ($progressBar) $progressBar->advance();
                    continue;
                }
            }

            fclose($handle);
            if ($progressBar) {
                $progressBar->finish();
            }
            $this->newLine(2);

            if ($imported > 0) {
                Log::createLog($userId, 'import_activities', "Imported {$imported} activities from CSV via command line");
            }

            $this->info("Import completed!");
            $this->info("Successfully imported: {$imported} activity(ies)");
            
            if (count($errors) > 0) {
                $this->warn("Errors encountered: " . count($errors));
                $this->newLine();
                
                // Display error summary by category
                $this->info("Error Summary:");
                $totalCategorized = 0;
                foreach ($errorCategories as $category => $categoryErrors) {
                    if (count($categoryErrors) > 0) {
                        $categoryName = ucwords(str_replace('_', ' ', $category));
                        $this->line("  • {$categoryName}: " . count($categoryErrors));
                        $totalCategorized += count($categoryErrors);
                    }
                }
                
                // Check for uncategorized errors
                $uncategorizedCount = count($errors) - $totalCategorized;
                if ($uncategorizedCount > 0) {
                    $this->line("  • Other: {$uncategorizedCount}");
                }
                
                $this->newLine();
                
                if ($this->option('verbose')) {
                    $this->error("Error details by category:");
                    $this->newLine();
                    
                    foreach ($errorCategories as $category => $categoryErrors) {
                        if (count($categoryErrors) > 0) {
                            $categoryName = ucwords(str_replace('_', ' ', $category));
                            $this->line("<fg=yellow>{$categoryName} (" . count($categoryErrors) . "):</>");
                            
                            // Show first 5 errors of each category, or all if less than 5
                            $displayErrors = array_slice($categoryErrors, 0, 5);
                            foreach ($displayErrors as $error) {
                                $this->line("  - {$error}");
                            }
                            
                            if (count($categoryErrors) > 5) {
                                $remaining = count($categoryErrors) - 5;
                                $this->comment("  ... and {$remaining} more {$categoryName} errors");
                            }
                            $this->newLine();
                        }
                    }
                    
                    // Show uncategorized errors if any
                    if ($uncategorizedCount > 0) {
                        $this->line("<fg=yellow>Other Errors ({$uncategorizedCount}):</>");
                        $otherErrors = array_diff($errors, array_merge(...array_values($errorCategories)));
                        $displayOther = array_slice($otherErrors, 0, 5);
                        foreach ($displayOther as $error) {
                            $this->line("  - {$error}");
                        }
                        if (count($otherErrors) > 5) {
                            $remaining = count($otherErrors) - 5;
                            $this->comment("  ... and {$remaining} more other errors");
                        }
                    }
                } else {
                    $this->comment("Run with --verbose flag to see detailed error messages");
                }
                
                // Export errors to file if requested
                $exportPath = $this->option('export-errors');
                if ($exportPath) {
                    $this->exportErrorsToFile($exportPath, $errors, $errorCategories);
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            $this->error("Import failed with error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Export errors to a CSV file
     */
    private function exportErrorsToFile($filePath, $errors, $errorCategories)
    {
        try {
            // Normalize file path (handle Windows paths)
            if (preg_match('/^[A-Za-z]:/', $filePath)) {
                $filePath = str_replace('/', '\\', $filePath);
            } else {
                $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
            }

            // Create directory if it doesn't exist
            $directory = dirname($filePath);
            if (!is_dir($directory) && !empty($directory)) {
                mkdir($directory, 0755, true);
            }

            $handle = fopen($filePath, 'w');
            if ($handle === false) {
                $this->warn("Failed to create error export file: {$filePath}");
                return;
            }

            // Write CSV header
            fputcsv($handle, ['Category', 'Row Number', 'Activity ID', 'Error Message']);

            // Write errors grouped by category
            foreach ($errorCategories as $category => $categoryErrors) {
                foreach ($categoryErrors as $error) {
                    $rowNumber = '';
                    $activityId = '';
                    
                    // Handle both string errors and array errors (for duplicate_activity and duplicate_activity_id)
                    if (is_array($error)) {
                        $errorMessage = $error['message'] ?? '';
                        $rowNumber = $error['row'] ?? '';
                        $activityId = isset($error['activity_id']) ? (string)$error['activity_id'] : '';
                    } else {
                        $errorMessage = $error;
                        // Extract row number from error message
                        if (preg_match('/Row (\d+):/', $errorMessage, $matches)) {
                            $rowNumber = $matches[1];
                        }
                        // Extract activity ID from error message if present
                        // Try multiple patterns: "CSV ActivityID: 123", "Activity ID: 123", "ID '123'", "ID: 123", etc.
                        if (preg_match('/CSV ActivityID: (\d+)/i', $errorMessage, $matches)) {
                            $activityId = $matches[1];
                        } elseif (preg_match('/Existing Activity ID: (\d+)/i', $errorMessage, $matches)) {
                            $activityId = $matches[1];
                        } elseif (preg_match('/Activity ID: (\d+)/i', $errorMessage, $matches)) {
                            $activityId = $matches[1];
                        } elseif (preg_match("/ID '(\d+)'/i", $errorMessage, $matches)) {
                            $activityId = $matches[1];
                        } elseif (preg_match('/ID: (\d+)/i', $errorMessage, $matches)) {
                            $activityId = $matches[1];
                        } elseif (preg_match('/ID (\d+)/i', $errorMessage, $matches)) {
                            $activityId = $matches[1];
                        }
                    }
                    
                    $categoryName = ucwords(str_replace('_', ' ', $category));
                    // Ensure activityId is a string and not empty/null
                    $activityIdStr = !empty($activityId) ? (string)$activityId : '';
                    fputcsv($handle, [$categoryName, $rowNumber, $activityIdStr, $errorMessage]);
                }
            }

            // Write uncategorized errors
            $categorizedErrors = array_merge(...array_values($errorCategories));
            $categorizedErrorMessages = array_map(function($error) {
                return is_array($error) ? ($error['message'] ?? '') : $error;
            }, $categorizedErrors);
            $uncategorizedErrors = array_diff($errors, $categorizedErrorMessages);
            foreach ($uncategorizedErrors as $error) {
                $rowNumber = '';
                $activityId = '';
                if (preg_match('/Row (\d+):/', $error, $matches)) {
                    $rowNumber = $matches[1];
                }
                // Extract activity ID from error message if present
                // Try multiple patterns: "CSV ActivityID: 123", "Activity ID: 123", "ID '123'", "ID: 123", etc.
                if (preg_match('/CSV ActivityID: (\d+)/i', $error, $matches)) {
                    $activityId = $matches[1];
                } elseif (preg_match('/Existing Activity ID: (\d+)/i', $error, $matches)) {
                    $activityId = $matches[1];
                } elseif (preg_match('/Activity ID: (\d+)/i', $error, $matches)) {
                    $activityId = $matches[1];
                } elseif (preg_match("/ID '(\d+)'/i", $error, $matches)) {
                    $activityId = $matches[1];
                } elseif (preg_match('/ID: (\d+)/i', $error, $matches)) {
                    $activityId = $matches[1];
                } elseif (preg_match('/ID (\d+)/i', $error, $matches)) {
                    $activityId = $matches[1];
                }
                // Ensure activityId is a string and not empty/null
                $activityIdStr = !empty($activityId) ? (string)$activityId : '';
                fputcsv($handle, ['Other', $rowNumber, $activityIdStr, $error]);
            }

            fclose($handle);
            $this->info("Errors exported to: {$filePath}");
        } catch (\Exception $e) {
            $this->warn("Failed to export errors: " . $e->getMessage());
        }
    }

    /**
     * Read a multi-line CSV record that may span multiple physical lines
     * Records start with a numeric Reference and end with ActivityPriority (Yes/No)
     * 
     * @param resource $handle File handle
     * @param array $headers Expected CSV headers
     * @return array|false Parsed CSV row or false on EOF
     */
    private function readMultiLineCsvRecord($handle, $headers)
    {
        $recordLines = [];
        $inRecord = false;
        $expectedColumnCount = count($headers);
        $lineStartPosition = null;
        
        while (true) {
            // Store position before reading
            $lineStartPosition = ftell($handle);
            $line = fgets($handle);
            
            if ($line === false) {
                break;
            }
            
            $trimmedLine = trim($line);
            
            // Check if this line starts a new record (starts with numeric Reference)
            // A record starts with a number (Reference) followed by comma
            if (preg_match('/^(\d+),/', $trimmedLine)) {
                // If we were already in a record, process the previous one first
                if ($inRecord && !empty($recordLines)) {
                    // Rewind to start of this line so we can process it next time
                    fseek($handle, $lineStartPosition, SEEK_SET);
                    break;
                }
                
                // Start new record
                $inRecord = true;
                $recordLines = [$line];
                continue;
            }
            
            // If we're in a record, accumulate lines
            if ($inRecord) {
                $recordLines[] = $line;
                
                // Try to parse the accumulated lines as CSV to check if record is complete
                $fullRecord = implode('', $recordLines);
                $tempHandle = fopen('php://memory', 'r+');
                if ($tempHandle === false) {
                    continue;
                }
                fwrite($tempHandle, $fullRecord);
                rewind($tempHandle);
                
                $parsedRow = fgetcsv($tempHandle);
                fclose($tempHandle);
                
                // Check if we have a complete record:
                // 1. Parsing succeeded
                // 2. We have the expected number of columns (or close to it)
                // 3. The last column (ActivityPriority) is "Yes" or "No"
                if ($parsedRow !== false && count($parsedRow) >= $expectedColumnCount - 1) {
                    // Check if last column is Yes/No (ActivityPriority)
                    $lastColumn = trim(end($parsedRow));
                    if (strcasecmp($lastColumn, 'Yes') === 0 || strcasecmp($lastColumn, 'No') === 0) {
                        // Verify we have exactly the expected number of columns (or very close)
                        if (count($parsedRow) >= $expectedColumnCount - 1 && count($parsedRow) <= $expectedColumnCount + 1) {
                            // We have a complete record
                            break;
                        }
                    }
                }
            }
        }
        
        // If we reached EOF and have accumulated lines, process them
        if (!$inRecord && empty($recordLines)) {
            // No more records
            return false;
        }
        
        if (empty($recordLines)) {
            return false;
        }
        
        // Parse the accumulated lines as a CSV record
        $fullRecord = implode('', $recordLines);
        
        // Use a temporary memory stream to parse the CSV
        $tempHandle = fopen('php://memory', 'r+');
        if ($tempHandle === false) {
            return false;
        }
        fwrite($tempHandle, $fullRecord);
        rewind($tempHandle);
        
        $parsedRow = fgetcsv($tempHandle);
        fclose($tempHandle);
        
        if ($parsedRow === false) {
            return false;
        }
        
        // Ensure we have enough columns
        while (count($parsedRow) < $expectedColumnCount) {
            $parsedRow[] = '';
        }
        
        // Trim to expected column count if we have more
        if (count($parsedRow) > $expectedColumnCount) {
            $parsedRow = array_slice($parsedRow, 0, $expectedColumnCount);
        }
        
        return $parsedRow;
    }

    /**
     * Sanitize date string by removing null bytes and invalid characters
     * 
     * @param string $dateString
     * @return string|null Returns sanitized string or null if too corrupted
     */
    private function sanitizeDateString($dateString)
    {
        if (empty($dateString) || !is_string($dateString)) {
            return null;
        }

        // Remove null bytes
        $dateString = str_replace("\0", '', $dateString);
        
        // Remove other non-printable characters except spaces, tabs, and newlines
        $dateString = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $dateString);
        
        // Trim whitespace
        $dateString = trim($dateString);
        
        // Check if string is empty or too short to be a valid date
        if (empty($dateString) || strlen($dateString) < 3) {
            return null;
        }
        
        // Check if string contains mostly non-ASCII or binary data
        // If more than 50% of characters are non-printable ASCII, consider it corrupted
        $printableCount = 0;
        $totalLength = strlen($dateString);
        for ($i = 0; $i < $totalLength; $i++) {
            $char = $dateString[$i];
            $ord = ord($char);
            // Printable ASCII: space (32) to tilde (126), plus tab (9), newline (10), carriage return (13)
            if (($ord >= 32 && $ord <= 126) || $ord == 9 || $ord == 10 || $ord == 13) {
                $printableCount++;
            }
        }
        
        // If less than 50% are printable, consider it corrupted
        if ($printableCount < ($totalLength * 0.5)) {
            return null;
        }
        
        // Additional check: if string doesn't contain any date-like patterns, return null
        // Look for common date separators or digits
        if (!preg_match('/[\d\/\-\.:]/', $dateString)) {
            return null;
        }
        
        return $dateString;
    }
}

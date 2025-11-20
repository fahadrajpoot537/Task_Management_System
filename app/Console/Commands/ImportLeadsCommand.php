<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use App\Models\Log;
use App\Models\LeadType;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ImportLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:import 
                            {file : The path to the CSV file to import}
                            {--user-id= : The user ID to use for added_by field (defaults to 1)}
                            {--export-errors= : Optional path to export errors to a CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import leads from a CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $userId = $this->option('user-id') ?: 1;

        // Normalize file path (handle Windows paths)
        // Convert forward slashes to backslashes for Windows, but preserve absolute paths
        if (preg_match('/^[A-Za-z]:/', $filePath)) {
            // Windows absolute path (e.g., C:\...)
            $filePath = str_replace('/', '\\', $filePath);
        } else {
            // Relative path or Unix-style path
            $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
        }
        
        // Remove leading forward slash if present (common mistake)
        if (strpos($filePath, '/') === 0 && !preg_match('/^[A-Za-z]:/', $filePath)) {
            $filePath = ltrim($filePath, '/');
        }

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            $this->comment("Tip: Make sure to quote the file path if it contains spaces.");
            $this->comment("Example: php artisan leads:import \"C:\\path\\to\\file.csv\"");
            return Command::FAILURE;
        }

        // Validate user exists
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return Command::FAILURE;
        }

        $this->info("Starting import from: {$filePath}");
        $this->info("Using user: {$user->name} (ID: {$userId})");
        $this->newLine();

        try {
            // Open CSV file for streaming (memory-efficient)
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                $this->error("Failed to open file: {$filePath}");
                return Command::FAILURE;
            }

            // Read header row
            $headers = fgetcsv($handle);
            if ($headers === false) {
                $this->error("Failed to read header row from CSV file");
                fclose($handle);
                return Command::FAILURE;
            }
            
            // Remove BOM if present
            if (!empty($headers[0])) {
                $headers[0] = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $headers[0]);
            }
            
            // Trim headers once
            $headers = array_map('trim', $headers);
            
            // Count total rows for progress bar (optional - can be skipped for very large files)
            $this->info("Counting rows in CSV file...");
            $totalRows = 0;
            $tempHandle = fopen($filePath, 'r');
            if ($tempHandle !== false) {
                fgetcsv($tempHandle); // Skip header
                while (fgetcsv($tempHandle) !== false) {
                    $totalRows++;
                }
                fclose($tempHandle);
            }
            
            $this->info("Found {$totalRows} rows to process");
            $this->newLine();

            $imported = 0;
            $errors = [];
            $errorCategories = [
                'missing_first_name' => [],
                'missing_project' => [],
                'duplicate_lead' => [],
                'project_creation_failed' => [],
                'lead_type_creation_failed' => [],
                'status_creation_failed' => [],
                'database_error' => [],
                'other' => []
            ];
            $progressBar = $this->output->createProgressBar($totalRows);
            $progressBar->start();

            $index = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $index++;
                // Skip empty rows
                if (empty(array_filter($row))) {
                    $progressBar->advance();
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
                    'added_by' => $userId,
                    
                // Extended fields (lead_type will be set via lead_type_id below)
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
                        $receivedDate = Carbon::createFromFormat('d/m/Y H:i', $rowData['ReceivedDateTime']);
                        $leadData['received_date'] = $receivedDate->format('Y-m-d');
                    } catch (\Exception $e) {
                        try {
                            // Try d/m/Y format
                            $receivedDate = Carbon::createFromFormat('d/m/Y', $rowData['ReceivedDateTime']);
                            $leadData['received_date'] = $receivedDate->format('Y-m-d');
                        } catch (\Exception $e2) {
                            // Try other formats
                            try {
                                $receivedDate = Carbon::parse($rowData['ReceivedDateTime']);
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
                        // Clean up the project name - normalize for uniqueness check
                        $projectTitle = $leadGroup ? trim($leadGroup) : ('Project ' . $leadGroupID);
                        $projectTitle = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', $projectTitle);
                        $projectTitle = preg_replace('/\s+/', ' ', $projectTitle); // Normalize multiple spaces to single space
                        $projectTitle = trim($projectTitle);
                        
                        // Check if a project with this title already exists (case-insensitive, trimmed)
                        $existingProject = Project::whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($projectTitle))])->first();
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
                                    'created_by_user_id' => $userId,
                                ]);
                            } catch (\Exception $e) {
                                // If creation fails (e.g., duplicate name), try to find again (case-insensitive)
                                $project = Project::whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($projectTitle))])->first();
                                if ($project && !$project->flg_group_id) {
                                    $project->flg_group_id = $leadGroupID;
                                    $project->save();
                                } elseif (!$project) {
                                    $errorMsg = "Row " . ($index + 2) . ": Failed to create/find project '{$projectTitle}' - " . $e->getMessage();
                                    $errors[] = $errorMsg;
                                    $errorCategories['project_creation_failed'][] = $errorMsg;
                                    $progressBar->advance();
                                    continue;
                                }
                            }
                        }
                    }
                } elseif ($leadGroup) {
                    // Clean up the project name - normalize for uniqueness check
                    $leadGroup = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', trim($leadGroup));
                    $leadGroup = preg_replace('/\s+/', ' ', $leadGroup); // Normalize multiple spaces to single space
                    $leadGroup = trim($leadGroup);
                    
                    if (!empty($leadGroup)) {
                        // Always check case-insensitively first to ensure uniqueness
                        $project = Project::whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($leadGroup))])->first();
                        
                        // If not found, create a new project
                        if (!$project) {
                            try {
                                $project = Project::create([
                                    'flg_group_id' => null,
                                    'title' => $leadGroup,
                                    'description' => null,
                                    'created_by_user_id' => $userId,
                                ]);
                            } catch (\Exception $e) {
                                // If creation fails (e.g., duplicate name), try to find again (case-insensitive)
                                $project = Project::whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($leadGroup))])->first();
                                if (!$project) {
                                    $errorMsg = "Row " . ($index + 2) . ": Failed to create/find project '{$leadGroup}' - " . $e->getMessage();
                                    $errors[] = $errorMsg;
                                    $errorCategories['project_creation_failed'][] = $errorMsg;
                                    $progressBar->advance();
                                    continue;
                                }
                            }
                        }
                    } else {
                        $errorMsg = "Row " . ($index + 2) . ": Project (LeadGroup or LeadGroupID) is required";
                        $errors[] = $errorMsg;
                        $errorCategories['missing_project'][] = $errorMsg;
                        $progressBar->advance();
                        continue;
                    }
                } else {
                    $errorMsg = "Row " . ($index + 2) . ": Project (LeadGroup or LeadGroupID) is required";
                    $errors[] = $errorMsg;
                    $errorCategories['missing_project'][] = $errorMsg;
                    $progressBar->advance();
                    continue;
                }
                
                // Set the project_id for the lead
                $leadData['project_id'] = $project->id;

                // Handle LeadType lookup and creation - try multiple column name variations
                $leadTypeName = $getValue('LeadType', $rowData) 
                    ?: $getValue('Lead Type', $rowData) 
                    ?: $getValue('lead_type', $rowData)
                    ?: $getValue('LEADTYPE', $rowData);
                if (!empty($leadTypeName)) {
                    // Clean up and normalize the lead type name for uniqueness check
                    $leadTypeName = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', trim($leadTypeName));
                    $leadTypeName = preg_replace('/\s+/', ' ', $leadTypeName); // Normalize multiple spaces to single space
                    $leadTypeName = trim($leadTypeName);
                    
                    if (!empty($leadTypeName)) {
                        // Try to find existing lead type by name (case-insensitive, trimmed) - ensures uniqueness
                        $leadType = LeadType::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($leadTypeName))])->first();
                        
                        // If not found, create a new lead type
                        if (!$leadType) {
                            try {
                                $leadType = LeadType::create([
                                    'name' => $leadTypeName,
                                    'description' => null,
                                    'color' => '#007bff', // Default color
                                    'order' => 0,
                                    'is_active' => true,
                                    'created_by' => $userId,
                                ]);
                            } catch (\Exception $e) {
                                // If creation fails (e.g., duplicate name), try to find again (case-insensitive)
                                $leadType = LeadType::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($leadTypeName))])->first();
                                if (!$leadType) {
                                    $errorMsg = "Row " . ($index + 2) . ": Failed to create/find lead type '{$leadTypeName}' - " . $e->getMessage();
                                    $errors[] = $errorMsg;
                                    $errorCategories['lead_type_creation_failed'][] = $errorMsg;
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
                    // Clean up and normalize the status name for uniqueness check
                    $statusName = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}\x{00AD}]/u', '', trim($statusName));
                    $statusName = preg_replace('/\s+/', ' ', $statusName); // Normalize multiple spaces to single space
                    $statusName = trim($statusName);
                    
                    if (!empty($statusName)) {
                        // Try to find existing status by name for this project (case-insensitive, trimmed) - ensures uniqueness within project
                        $status = Status::where('project_id', $leadData['project_id'])
                            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($statusName))])
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
                                // If creation fails (e.g., duplicate name for same project), try to find again (case-insensitive)
                                $status = Status::where('project_id', $leadData['project_id'])
                                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($statusName))])
                                    ->first();
                                if (!$status) {
                                    $errorMsg = "Row " . ($index + 2) . ": Failed to create/find status '{$statusName}' for project - " . $e->getMessage();
                                    $errors[] = $errorMsg;
                                    $errorCategories['status_creation_failed'][] = $errorMsg;
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
                    $userFromCsv = User::find($rowData['UserID']);
                    if ($userFromCsv) {
                        $leadData['added_by'] = $userFromCsv->id;
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
                        $errorMsg = "Row " . ($index + 2) . ": First Name is required. Available columns with data: " . implode(', ', array_slice($availableColumns, 0, 10));
                        $errors[] = $errorMsg;
                        $errorCategories['missing_first_name'][] = $errorMsg;
                        $progressBar->advance();
                        continue;
                    }
                }
                
                // Check for duplicate leads - multiple strategies
                $existingLead = null;
                $duplicateReason = '';
                
                // Strategy 1: Check by flg_reference (primary check)
                if (!empty($leadData['flg_reference'])) {
                    // Check by flg_reference
                    $existingLead = Lead::where('flg_reference', $leadData['flg_reference'])->first();
                    
                    // If not found, check if Reference matches an existing lead ID
                    if (!$existingLead && is_numeric($leadData['flg_reference'])) {
                        $existingLead = Lead::find($leadData['flg_reference']);
                    }
                    
                    if ($existingLead) {
                        $duplicateReason = "Reference '{$leadData['flg_reference']}'";
                    }
                }
                
                // Strategy 2: Check by email + phone combination (if both provided)
                if (!$existingLead && !empty($leadData['email']) && !empty($leadData['phone'])) {
                    $existingLead = Lead::where('email', $leadData['email'])
                        ->where('phone', $leadData['phone'])
                        ->first();
                    
                    if ($existingLead) {
                        $duplicateReason = "Email '{$leadData['email']}' and Phone '{$leadData['phone']}'";
                    }
                }
                
                // Strategy 3: Check by first_name + last_name + phone (if all provided)
                if (!$existingLead && !empty($leadData['first_name']) && !empty($leadData['last_name']) && !empty($leadData['phone'])) {
                    $existingLead = Lead::where('first_name', $leadData['first_name'])
                        ->where('last_name', $leadData['last_name'])
                        ->where('phone', $leadData['phone'])
                        ->first();
                    
                    if ($existingLead) {
                        $duplicateReason = "Name '{$leadData['first_name']} {$leadData['last_name']}' and Phone '{$leadData['phone']}'";
                    }
                }
                
                // If duplicate found, skip this row
                if ($existingLead) {
                    $errorMsg = "Row " . ($index + 2) . ": Duplicate lead found with {$duplicateReason} already exists (Lead ID: {$existingLead->id}, Name: {$existingLead->first_name} {$existingLead->last_name})";
                    $errors[] = $errorMsg;
                    $errorCategories['duplicate_lead'][] = $errorMsg;
                    $progressBar->advance();
                    continue;
                }

                try {
                    // Create new lead
                    Lead::create($leadData);
                    $imported++;
                } catch (\Exception $e) {
                    $errorMsg = "Row " . ($index + 2) . ": " . $e->getMessage();
                    $errors[] = $errorMsg;
                    $errorCategories['database_error'][] = $errorMsg;
                }

                $progressBar->advance();
            }

            // Close the file handle
            fclose($handle);

            $progressBar->finish();
            $this->newLine(2);

            // Log the action
            if ($imported > 0) {
                Log::createLog($userId, 'import_leads', "Imported {$imported} leads from CSV via command line");
            }

            // Display results
            $this->info("Import completed!");
            $this->info("Successfully imported: {$imported} lead(s)");
            
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
            // Close file handle if it's still open
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            $this->error("Import failed with error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
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
            $date = Carbon::createFromFormat('d/m/Y H:i', $value);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            try {
                // Try d/m/Y format
                $date = Carbon::createFromFormat('d/m/Y', $value);
                return $date->format('Y-m-d H:i:s');
            } catch (\Exception $e2) {
                try {
                    // Try standard formats
                    $date = Carbon::parse($value);
                    return $date->format('Y-m-d H:i:s');
                } catch (\Exception $e3) {
                    return null;
                }
            }
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
            fputcsv($handle, ['Category', 'Row Number', 'Error Message']);

            // Write errors grouped by category
            foreach ($errorCategories as $category => $categoryErrors) {
                foreach ($categoryErrors as $error) {
                    // Extract row number from error message
                    $rowNumber = '';
                    if (preg_match('/Row (\d+):/', $error, $matches)) {
                        $rowNumber = $matches[1];
                    }
                    
                    $categoryName = ucwords(str_replace('_', ' ', $category));
                    fputcsv($handle, [$categoryName, $rowNumber, $error]);
                }
            }

            // Write uncategorized errors
            $categorizedErrors = array_merge(...array_values($errorCategories));
            $uncategorizedErrors = array_diff($errors, $categorizedErrors);
            foreach ($uncategorizedErrors as $error) {
                $rowNumber = '';
                if (preg_match('/Row (\d+):/', $error, $matches)) {
                    $rowNumber = $matches[1];
                }
                fputcsv($handle, ['Other', $rowNumber, $error]);
            }

            fclose($handle);
            $this->info("Errors exported to: {$filePath}");
        } catch (\Exception $e) {
            $this->warn("Failed to export errors: " . $e->getMessage());
        }
    }
}


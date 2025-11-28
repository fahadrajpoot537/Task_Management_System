<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use App\Models\Log;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ImportDocumentFilesCommand extends Command
{
    protected $signature = 'documents:import 
                            {folder : The path to the folder containing document files}
                            {--user-id= : The user ID to use for created_by field (defaults to 1)}
                            {--keep-original : Keep files in original location (default: copy to uploads/activities)}
                            {--dry-run : Show what would be imported without actually importing}';
    
    protected $description = 'Import document files and create Document-type activities for leads';

    public function handle()
    {
        $folderPath = $this->argument('folder');
        $userId = $this->option('user-id') ?: 1;
        $keepOriginal = $this->option('keep-original');
        $dryRun = $this->option('dry-run');

        // Normalize folder path for Windows
        if (preg_match('/^[A-Za-z]:/', $folderPath)) {
            $folderPath = str_replace('/', '\\', $folderPath);
        } else {
            $folderPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $folderPath);
        }

        if (!is_dir($folderPath)) {
            $this->error("Folder not found: {$folderPath}");
            return Command::FAILURE;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return Command::FAILURE;
        }

        $this->info("Scanning folder: {$folderPath}");
        $this->info("Using user: {$user->name} (ID: {$userId})");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No files will be imported");
        }
        if ($keepOriginal) {
            $this->info("Files will be kept in original location");
        } else {
            $this->info("Files will be copied to Leads/activities folder");
        }
        $this->newLine();

        // Get all files from the folder
        $files = $this->getFilesFromFolder($folderPath);
        
        if (empty($files)) {
            $this->warn("No files found in folder: {$folderPath}");
            return Command::SUCCESS;
        }

        $this->info("Found " . count($files) . " file(s) to process");
        $this->newLine();

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        foreach ($files as $filePath) {
            try {
                $fileName = basename($filePath);
                
                // Parse filename: {reference_id}_{filename} or {reference_id}-{filename}
                // Examples: 21222123_invoice.pdf or 196254019-LUX REALTY LLC.pdf
                $referenceId = null;
                $fileDescription = null;
                
                // Try underscore pattern first: {reference_id}_{filename}
                if (preg_match('/^(\d+)_(.+)$/', $fileName, $matches)) {
                    $referenceId = $matches[1];
                    $fileDescription = $matches[2];
                }
                // Try hyphen pattern: {reference_id}-{filename}
                elseif (preg_match('/^(\d+)-(.+)$/', $fileName, $matches)) {
                    $referenceId = $matches[1];
                    $fileDescription = $matches[2];
                }
                
                if (!$referenceId || !$fileDescription) {
                    $errors[] = "File '{$fileName}' does not match pattern: {reference_id}_{filename} or {reference_id}-{filename}";
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Find lead by reference ID
                $lead = Lead::where('flg_reference', $referenceId)->first();
                
                if (!$lead) {
                    // Try to find by ID if reference is numeric
                    if (is_numeric($referenceId)) {
                        $lead = Lead::find($referenceId);
                    }
                }

                if (!$lead) {
                    $errors[] = "Lead not found for reference ID: {$referenceId} (File: {$fileName})";
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // CHECK DATABASE FIRST for duplicates before uploading file
                // Check multiple ways to detect duplicates:
                // 1. Same lead, type Document, and same field_1 (filename part)
                // 2. Same lead, type Document, and file path contains the original filename
                // $existingActivity = Activity::where('lead_id', $lead->id)
                //     ->where('type', 'Document')
                //     ->where(function($query) use ($fileDescription, $fileName) {
                //         $query->where('field_1', $fileDescription)
                //               ->orWhere('file', 'like', '%' . $fileName)
                //               ->orWhere('file', 'like', '%' . pathinfo($fileName, PATHINFO_FILENAME) . '%');
                //     })
                //     ->first();

                // if ($existingActivity) {
                //     $errors[] = "Document activity already exists in database for file: {$fileName} (Lead: {$lead->flg_reference}, Activity ID: {$existingActivity->id})";
                //     $skipped++;
                //     $progressBar->advance();
                //     continue;
                // }

                if ($dryRun) {
                    $this->line("Would import: {$fileName} -> Lead #{$lead->id} ({$lead->flg_reference})");
                    $imported++;
                    $progressBar->advance();
                    continue;
                }

                // Now proceed with file upload since no duplicate exists
                // Prepare file path - always copy to Leads/activities folder by default
                $finalFilePath = null;
                
                if ($keepOriginal) {
                    // Store file path - prefer relative path if file is in public directory
                    $publicPath = public_path();
                    $normalizedFilePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
                    $normalizedPublicPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicPath);
                    
                    if (strpos($normalizedFilePath, $normalizedPublicPath) === 0) {
                        // File is inside public directory, store relative path
                        $relativePath = str_replace($normalizedPublicPath . DIRECTORY_SEPARATOR, '', $normalizedFilePath);
                        $finalFilePath = str_replace('\\', '/', $relativePath);
                    } else {
                        // File is outside public directory, store full path
                        $finalFilePath = $normalizedFilePath;
                    }
                } else {
                    // Copy file to Leads/activities folder (default behavior)
                    $uploadDir = public_path('TMS/Leads/activities');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Generate unique filename to avoid conflicts
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);
                    $newFileName = time() . '_' . uniqid() . '_' . $fileBaseName . '.' . $fileExtension;
                    $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;
                    
                    if (copy($filePath, $destinationPath)) {
                        $finalFilePath = 'Leads/activities/' . $newFileName;
                    } else {
                        $errors[] = "Failed to copy file: {$fileName}";
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }
                }

                // Get file modification date for activity date
                $fileDate = filemtime($filePath) ? Carbon::createFromTimestamp(filemtime($filePath)) : now();

                // Create Document activity
                $activity = Activity::create([
                    'lead_id' => $lead->id,
                    'type' => 'Document',
                    'field_1' => $fileDescription,
                    'file' => $finalFilePath,
                    'date' => $fileDate->format('Y-m-d'),
                    'created_by' => $userId,
                    'created_at' => $fileDate,
                    'updated_at' => $fileDate,
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Error processing file '{$fileName}': " . $e->getMessage();
                $skipped++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Import completed!");
        $this->info("Successfully imported: {$imported} document(s)");
        
        if ($skipped > 0) {
            $this->warn("Skipped: {$skipped} file(s)");
        }

        if (count($errors) > 0) {
            $this->newLine();
            $this->error("Errors encountered: " . count($errors));
            
            if ($this->option('verbose')) {
                $this->newLine();
                $this->line("Error details:");
                foreach ($errors as $error) {
                    $this->line("  - {$error}");
                }
            } else {
                $this->comment("Run with --verbose flag to see detailed error messages");
            }
        }

        // Log the action
        if ($imported > 0 && !$dryRun) {
            Log::createLog($userId, 'import_documents', "Imported {$imported} document files via command line");
        }

        return Command::SUCCESS;
    }

    /**
     * Get all files from a folder recursively
     * 
     * @param string $folderPath
     * @return array
     */
    private function getFilesFromFolder($folderPath)
    {
        $files = [];
        
        if (!is_dir($folderPath)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}


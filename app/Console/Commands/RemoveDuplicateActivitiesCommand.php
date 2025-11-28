<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateActivitiesCommand extends Command
{
    protected $signature = 'activities:remove-duplicates 
                            {--dry-run : Show duplicates without deleting them}
                            {--keep-oldest : Keep the oldest record (default: keep newest)}
                            {--limit= : Limit processing to first N activities (for testing)}';
    
    protected $description = 'Detect and remove duplicate activities from database (ignoring created_at/updated_at)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $keepOldest = $this->option('keep-oldest');
        $limit = $this->option('limit') ? (int)$this->option('limit') : null;

        $this->info("Scanning for duplicate activities...");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No records will be deleted");
        }
        $this->newLine();

        // Get all activities
        $query = Activity::query();
        if ($limit) {
            $query->limit($limit);
        }
        $activities = $query->get();

        $this->info("Total activities to check: " . $activities->count());
        $this->newLine();

        // First, find Document activities with empty file fields
        $this->info("Checking for Document activities with empty file fields...");
        $emptyFileDocuments = Activity::where('type', 'Document')
            ->where(function($query) {
                $query->whereNull('file')
                      ->orWhere('file', '')
                      ->orWhere('file', '=', 'null');
            })
            ->get();

        $emptyFileCount = $emptyFileDocuments->count();
        if ($emptyFileCount > 0) {
            $this->warn("Found {$emptyFileCount} Document activity/ies with empty file fields");
        } else {
            $this->info("No Document activities with empty file fields found");
        }
        $this->newLine();

        // Group activities by all fields except created_at/updated_at
        $this->info("Grouping activities to find duplicates...");
        $groups = [];
        $duplicateGroups = [];
        
        $progressBar = $this->output->createProgressBar($activities->count());
        $progressBar->start();

        foreach ($activities as $activity) {
            // Create a unique key based on all fields except timestamps
            $key = $this->getActivityKey($activity);
            
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            
            $groups[$key][] = $activity;
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Find groups with duplicates (more than 1 activity)
        foreach ($groups as $key => $group) {
            if (count($group) > 1) {
                $duplicateGroups[$key] = $group;
            }
        }

        $totalDuplicates = 0;
        $totalToDelete = 0;
        $deleted = 0;
        $deletedEmptyFiles = 0;
        $errors = [];

        if (empty($duplicateGroups) && $emptyFileCount == 0) {
            $this->info("No duplicate activities or empty Document files found!");
            return Command::SUCCESS;
        }

        if (!empty($duplicateGroups)) {
            $this->info("Found " . count($duplicateGroups) . " duplicate group(s)");
        }
        $this->newLine();

        $totalGroups = count($duplicateGroups) + ($emptyFileCount > 0 ? 1 : 0);
        $progressBar = $this->output->createProgressBar($totalGroups);
        $progressBar->start();

        // First, handle Document activities with empty file fields
        if ($emptyFileCount > 0) {
            if ($dryRun) {
                $this->newLine();
                $this->line("Document Activities with Empty File Fields:");
                $this->line("  Total: {$emptyFileCount} activities to delete");
                foreach ($emptyFileDocuments as $doc) {
                    $this->line("    - Activity ID {$doc->id} (Lead: {$doc->lead_id}, created: {$doc->created_at})");
                }
            } else {
                // Delete Document activities with empty file fields
                foreach ($emptyFileDocuments as $doc) {
                    try {
                        $doc->delete();
                        $deletedEmptyFiles++;
                        $deleted++;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to delete Activity ID {$doc->id}: " . $e->getMessage();
                    }
                }
            }
            $totalToDelete += $emptyFileCount;
            $progressBar->advance();
        }

        foreach ($duplicateGroups as $key => $group) {
            $totalDuplicates += count($group);
            
            // Convert array to collection and sort by created_at to determine which to keep
            $collection = collect($group);
            $sorted = $collection->sortBy('created_at');
            
            if ($keepOldest) {
                $keepActivity = $sorted->first();
                $deleteActivities = $sorted->slice(1)->values();
            } else {
                $keepActivity = $sorted->last();
                $deleteActivities = $sorted->slice(0, -1)->values();
            }

            $totalToDelete += $deleteActivities->count();

            if ($dryRun) {
                $this->newLine();
                $this->line("Duplicate Group (Key: {$key}):");
                $this->line("  Total: " . count($group) . " activities");
                $this->line("  Keep: Activity ID {$keepActivity->id} (created: {$keepActivity->created_at})");
                $this->line("  Delete: " . $deleteActivities->count() . " activity/ies");
                foreach ($deleteActivities as $deleteActivity) {
                    $this->line("    - Activity ID {$deleteActivity->id} (created: {$deleteActivity->created_at})");
                }
            } else {
                // Delete duplicate activities
                foreach ($deleteActivities as $deleteActivity) {
                    try {
                        $deleteActivity->delete();
                        $deleted++;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to delete Activity ID {$deleteActivity->id}: " . $e->getMessage();
                    }
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Scan completed!");
        
        if (!empty($duplicateGroups)) {
            $this->info("Total duplicate groups found: " . count($duplicateGroups));
            $this->info("Total duplicate activities: {$totalDuplicates}");
        }
        
        if ($emptyFileCount > 0) {
            $this->info("Document activities with empty file fields: {$emptyFileCount}");
        }
        
        if ($dryRun) {
            $this->warn("Would delete: {$totalToDelete} activity/ies");
            if (!empty($duplicateGroups)) {
                $this->warn("  - Duplicates: " . ($totalToDelete - $emptyFileCount));
                $this->warn("  - Empty file Documents: {$emptyFileCount}");
                $this->warn("Would keep: " . count($duplicateGroups) . " activity/ies (one per duplicate group)");
            }
        } else {
            $this->info("Deleted: {$deleted} activity/ies");
            if (!empty($duplicateGroups)) {
                $duplicateDeletes = $deleted - $deletedEmptyFiles;
                $this->info("  - Duplicates: {$duplicateDeletes}");
                $this->info("  - Empty file Documents: {$deletedEmptyFiles}");
                $this->info("Kept: " . count($duplicateGroups) . " activity/ies (one per duplicate group)");
            } else {
                $this->info("  - Empty file Documents: {$deletedEmptyFiles}");
            }
            
            // Log the action
            if ($deleted > 0) {
                $userId = auth()->id() ?: 1;
                $logMessage = "Removed {$deleted} activities via command line";
                if ($deletedEmptyFiles > 0) {
                    $logMessage .= " ({$deletedEmptyFiles} empty Document files";
                    if ($deleted - $deletedEmptyFiles > 0) {
                        $logMessage .= ", " . ($deleted - $deletedEmptyFiles) . " duplicates";
                    }
                    $logMessage .= ")";
                }
                Log::createLog($userId, 'remove_duplicate_activities', $logMessage);
            }
        }

        if (count($errors) > 0) {
            $this->newLine();
            $this->error("Errors encountered: " . count($errors));
            if ($this->option('verbose')) {
                foreach ($errors as $error) {
                    $this->line("  - {$error}");
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Generate a unique key for an activity based on all fields except timestamps
     * 
     * @param Activity $activity
     * @return string
     */
    private function getActivityKey(Activity $activity)
    {
        // Get all fillable fields
        $fields = [
            'lead_id',
            'date',
            'type',
            'created_by',
            'assigned_to',
            'field_1',
            'field_2',
            'email',
            'bcc',
            'cc',
            'phone',
            'actioned',
            'due_date',
            'end_date',
            'priority',
            'file',
        ];

        $values = [];
        foreach ($fields as $field) {
            $value = $activity->$field;
            // Normalize values for comparison
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
                $value = $value->format('Y-m-d');
            } elseif (is_null($value)) {
                $value = '';
            } else {
                $value = (string)$value;
            }
            $values[] = $field . ':' . $value;
        }

        return md5(implode('|', $values));
    }
}


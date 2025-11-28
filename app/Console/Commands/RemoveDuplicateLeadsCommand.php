<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateLeadsCommand extends Command
{
    protected $signature = 'leads:remove-duplicates 
                            {--dry-run : Show duplicates without deleting them}
                            {--keep-oldest : Keep the oldest record (default: keep newest)}
                            {--limit= : Limit processing to first N leads (for testing)}
                            {--include-null : Include leads with null reference numbers in duplicate check}';
    
    protected $description = 'Detect and remove duplicate leads by reference number (flg_reference)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $keepOldest = $this->option('keep-oldest');
        $limit = $this->option('limit') ? (int)$this->option('limit') : null;
        $includeNull = $this->option('include-null');

        $this->info("Scanning for duplicate leads by reference number...");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No records will be deleted");
        }
        $this->newLine();

        // Track non-numeric deletions
        $deletedNonNumeric = 0;

        // First, find and remove leads with non-numeric reference numbers
        $this->info("Finding leads with non-numeric reference numbers...");
        $nonNumericQuery = Lead::whereNotNull('flg_reference')
            ->where('flg_reference', '!=', '')
            ->whereRaw('flg_reference NOT REGEXP ?', ['^[0-9]+$']); // Only numeric values
        
        $nonNumericLeads = $nonNumericQuery->get();
        $nonNumericCount = $nonNumericLeads->count();
        
        if ($nonNumericCount > 0) {
            $this->warn("Found {$nonNumericCount} lead(s) with non-numeric reference numbers");
            if ($dryRun) {
                $this->line("Would delete {$nonNumericCount} lead(s) with non-numeric references:");
                foreach ($nonNumericLeads->take(10) as $lead) {
                    $name = trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''));
                    $this->line("  - Lead ID {$lead->id} - {$name} (Reference: '{$lead->flg_reference}')");
                }
                if ($nonNumericCount > 10) {
                    $this->line("  ... and " . ($nonNumericCount - 10) . " more");
                }
            } else {
                foreach ($nonNumericLeads as $lead) {
                    try {
                        $lead->delete();
                        $deletedNonNumeric++;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to delete Lead ID {$lead->id} (non-numeric reference): " . $e->getMessage();
                    }
                }
                $this->info("Deleted {$deletedNonNumeric} lead(s) with non-numeric reference numbers");
            }
        } else {
            $this->info("No leads with non-numeric reference numbers found");
        }
        $this->newLine();

        // Use SQL to find duplicate reference numbers efficiently (only numeric)
        $this->info("Finding duplicate numeric reference numbers using database query...");
        
        // Build query to find reference numbers with duplicates (only numeric)
        $duplicateQuery = DB::table('leads')
            ->select('flg_reference', DB::raw('COUNT(*) as count'))
            ->whereNotNull('flg_reference')
            ->where('flg_reference', '!=', '')
            ->whereRaw('flg_reference REGEXP ?', ['^[0-9]+$']) // Only numeric values
            ->groupBy('flg_reference')
            ->having('count', '>', 1);
        
        // Note: include-null option is ignored for duplicate detection (only numeric refs are checked)
        if ($includeNull) {
            $this->warn("Note: --include-null option is ignored. Only numeric reference numbers are processed for duplicates.");
        }
        
        if ($limit) {
            // For limit, we'll apply it later when processing
            $duplicateQuery->limit($limit);
        }
        
        $duplicateReferences = $duplicateQuery->get();
        
        $totalLeadsToCheck = $duplicateReferences->sum('count');
        $this->info("Found " . $duplicateReferences->count() . " reference number(s) with duplicates");
        $this->info("Total duplicate leads: {$totalLeadsToCheck}");
        
        // Count null/empty references
        $nullCount = DB::table('leads')
            ->where(function($query) {
                $query->whereNull('flg_reference')
                      ->orWhere('flg_reference', '');
            })
            ->count();
        if ($nullCount > 0) {
            $this->info("Skipping {$nullCount} lead(s) with null or empty reference numbers");
        }
        $this->newLine();

        if ($duplicateReferences->isEmpty()) {
            $this->info("No duplicate leads found!");
            return Command::SUCCESS;
        }

        // Now load only the leads that have duplicate references
        $this->info("Loading duplicate leads...");
        $duplicateGroups = [];
        
        $processedCount = 0;
        $progressBar = $this->output->createProgressBar($duplicateReferences->count());
        $progressBar->start();

        foreach ($duplicateReferences as $refData) {
            /** @var object{flg_reference: string|null, count: int} $refData */
            $reference = $refData->flg_reference ?? null;
            
            // Normalize reference (should be numeric at this point due to query filter)
            if ($reference !== null) {
                $reference = trim((string)$reference);
            } else {
                continue; // Skip if null (shouldn't happen due to query filter)
            }
            
            // Verify it's numeric (double-check)
            if (!is_numeric($reference)) {
                continue; // Skip non-numeric (shouldn't happen due to query filter)
            }
            
            // Load only leads with this reference number
            $query = Lead::where('flg_reference', $reference)
                ->whereRaw('flg_reference REGEXP ?', ['^[0-9]+$']); // Ensure it's numeric
            
            $leads = $query->orderBy('created_at')->get();
            
            if ($leads->count() > 1) {
                $duplicateGroups[$reference] = $leads;
            }
            
            $processedCount++;
            if ($limit && $processedCount >= $limit) {
                break;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $totalDuplicates = 0;
        $totalToDelete = 0;
        $deleted = 0;
        $errors = [];

        if (empty($duplicateGroups)) {
            $this->info("No duplicate leads found!");
            return Command::SUCCESS;
        }

        $this->info("Processing " . count($duplicateGroups) . " duplicate reference number(s)");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($duplicateGroups));
        $progressBar->start();

        foreach ($duplicateGroups as $reference => $group) {
            $totalDuplicates += $group->count();
            
            // Group is already sorted by created_at from the query
            if ($keepOldest) {
                $keepLead = $group->first();
                $deleteLeads = $group->slice(1)->values();
            } else {
                $keepLead = $group->last();
                $deleteLeads = $group->slice(0, -1)->values();
            }

            $totalToDelete += $deleteLeads->count();

            if ($dryRun) {
                $this->newLine();
                $referenceDisplay = $reference === '' ? '(empty/null)' : $reference;
                $this->line("Duplicate Reference: {$referenceDisplay}");
                $this->line("  Total: " . $group->count() . " lead(s)");
                $this->line("  Keep: Lead ID {$keepLead->id} - {$keepLead->first_name} {$keepLead->last_name} (created: {$keepLead->created_at})");
                $this->line("  Delete: " . $deleteLeads->count() . " lead(s)");
                foreach ($deleteLeads as $deleteLead) {
                    $name = trim(($deleteLead->first_name ?? '') . ' ' . ($deleteLead->last_name ?? ''));
                    $this->line("    - Lead ID {$deleteLead->id} - {$name} (created: {$deleteLead->created_at})");
                }
            } else {
                // Delete duplicate leads
                foreach ($deleteLeads as $deleteLead) {
                    try {
                        $name = trim(($deleteLead->first_name ?? '') . ' ' . ($deleteLead->last_name ?? ''));
                        $deleteLead->delete();
                        $deleted++;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to delete Lead ID {$deleteLead->id}: " . $e->getMessage();
                    }
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Scan completed!");
        $this->info("Total duplicate reference numbers found: " . count($duplicateGroups));
        $this->info("Total duplicate leads: {$totalDuplicates}");
        
        if ($dryRun) {
            $this->warn("Would delete: {$totalToDelete} lead(s)");
            $this->warn("Would keep: " . count($duplicateGroups) . " lead(s) (one per duplicate reference)");
        } else {
            $totalDeleted = $deleted + $deletedNonNumeric;
            
            if ($totalDeleted > 0) {
                $this->info("Deleted: {$totalDeleted} lead(s) total");
                if ($deleted > 0) {
                    $this->info("  - Duplicates: {$deleted}");
                }
                if ($deletedNonNumeric > 0) {
                    $this->info("  - Non-numeric references: {$deletedNonNumeric}");
                }
            }
            $this->info("Kept: " . count($duplicateGroups) . " lead(s) (one per duplicate reference)");
            
            // Log the action
            if ($totalDeleted > 0) {
                $userId = auth()->id() ?: 1;
                $logMessage = "Removed {$totalDeleted} leads via command line";
                if ($deleted > 0 && $deletedNonNumeric > 0) {
                    $logMessage .= " ({$deleted} duplicates, {$deletedNonNumeric} non-numeric references)";
                } elseif ($deleted > 0) {
                    $logMessage .= " ({$deleted} duplicates)";
                } elseif ($deletedNonNumeric > 0) {
                    $logMessage .= " ({$deletedNonNumeric} non-numeric references)";
                }
                Log::createLog($userId, 'remove_duplicate_leads', $logMessage);
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
}


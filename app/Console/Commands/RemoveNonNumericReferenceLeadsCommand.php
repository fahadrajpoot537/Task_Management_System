<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveNonNumericReferenceLeadsCommand extends Command
{
    protected $signature = 'leads:remove-non-numeric 
                            {--dry-run : Show leads without deleting them}
                            {--limit= : Limit processing to first N leads (for testing)}';
    
    protected $description = 'Remove leads where flg_reference is not a number';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int)$this->option('limit') : null;

        $this->info("Finding leads with non-numeric reference numbers...");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No records will be deleted");
        }
        $this->newLine();

        // Find leads with non-numeric reference numbers
        $query = Lead::whereNotNull('flg_reference')
            ->where('flg_reference', '!=', '')
            ->whereRaw('flg_reference NOT REGEXP ?', ['^[0-9]+$']); // Not numeric
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $nonNumericLeads = $query->get();
        $nonNumericCount = $nonNumericLeads->count();
        
        if ($nonNumericCount == 0) {
            $this->info("No leads with non-numeric reference numbers found!");
            return Command::SUCCESS;
        }

        $this->warn("Found {$nonNumericCount} lead(s) with non-numeric reference numbers");
        $this->newLine();

        if ($dryRun) {
            $this->line("Would delete {$nonNumericCount} lead(s) with non-numeric references:");
            $this->newLine();
            
            $displayCount = min(20, $nonNumericCount);
            foreach ($nonNumericLeads->take($displayCount) as $lead) {
                $name = trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''));
                $reference = $lead->flg_reference ?? '(null)';
                $this->line("  - Lead ID {$lead->id} - {$name} (Reference: '{$reference}')");
            }
            
            if ($nonNumericCount > $displayCount) {
                $this->line("  ... and " . ($nonNumericCount - $displayCount) . " more");
            }
            
            $this->newLine();
            $this->warn("Total: {$nonNumericCount} lead(s) would be deleted");
        } else {
            $this->info("Deleting {$nonNumericCount} lead(s)...");
            $this->newLine();
            
            $deleted = 0;
            $errors = [];
            $progressBar = $this->output->createProgressBar($nonNumericCount);
            $progressBar->start();

            foreach ($nonNumericLeads as $lead) {
                try {
                    $lead->delete();
                    $deleted++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete Lead ID {$lead->id}: " . $e->getMessage();
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("Deleted: {$deleted} lead(s)");
            
            if (count($errors) > 0) {
                $this->newLine();
                $this->error("Errors encountered: " . count($errors));
                if ($this->option('verbose')) {
                    foreach ($errors as $error) {
                        $this->line("  - {$error}");
                    }
                }
            }
            
            // Log the action
            if ($deleted > 0) {
                $userId = auth()->id() ?: 1;
                $logMessage = "Removed {$deleted} leads with non-numeric reference numbers via command line";
                Log::createLog($userId, 'remove_non_numeric_reference_leads', $logMessage);
            }
        }

        return Command::SUCCESS;
    }
}


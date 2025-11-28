<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EmailSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SyncEmailsCommand
 * 
 * Artisan command to sync emails from email provider and store them as lead activities.
 * 
 * Usage:
 *   php artisan emails:sync
 *   php artisan emails:sync --limit=50
 *   php artisan emails:sync --user-id=2
 */
class SyncEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:sync
                            {--limit= : Maximum number of emails to process (0 = unlimited)}
                            {--user-id= : User ID to use for created_by field (defaults to 1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch emails from email provider and attach them to leads as activities';

    /**
     * EmailSyncService instance
     */
    private EmailSyncService $emailSyncService;

    /**
     * Create a new command instance.
     */
    public function __construct(EmailSyncService $emailSyncService)
    {
        parent::__construct();
        $this->emailSyncService = $emailSyncService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting email sync...');
        $this->newLine();

        // Get options
        $limit = (int) $this->option('limit') ?: 0;
        $userId = (int) $this->option('user-id') ?: 1;

        // Log command execution
        Log::info('Email Sync Command: Started', [
            'command' => 'emails:sync',
            'limit' => $limit,
            'user_id' => $userId,
            'executed_by' => 'artisan_command',
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Validate user exists
        if ($userId && !User::find($userId)) {
            $this->error("User with ID {$userId} not found. Using default user ID 1.");
            Log::warning('Email Sync Command: Invalid user ID, using default', [
                'requested_user_id' => $userId,
                'default_user_id' => 1,
            ]);
            $userId = 1;
        }

        // Display configuration
        $this->info('Configuration:');
        $this->line("  Limit: " . ($limit > 0 ? $limit : 'Unlimited'));
        $this->line("  User ID: {$userId}");
        $this->newLine();

        try {
            // Perform email sync
            $stats = $this->emailSyncService->syncEmails($limit, $userId);

            // Display results
            $this->info('Email sync completed!');
            $this->newLine();
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Emails Fetched', $stats['total_fetched']],
                    ['Matched to Leads', $stats['matched_leads']],
                    ['Stored as Activities', $stats['stored']],
                    ['Skipped (No Lead Match)', $stats['skipped_no_lead']],
                    ['Skipped (Duplicate)', $stats['skipped_duplicate']],
                    ['Errors', $stats['errors']],
                ]
            );

            // Show summary
            if ($stats['stored'] > 0) {
                $this->info("✓ Successfully stored {$stats['stored']} email(s) as activities.");
            }

            if ($stats['skipped_no_lead'] > 0) {
                $this->warn("⚠ {$stats['skipped_no_lead']} email(s) skipped - no matching lead found.");
            }

            if ($stats['skipped_duplicate'] > 0) {
                $this->warn("⚠ {$stats['skipped_duplicate']} email(s) skipped - already imported.");
            }

            if ($stats['errors'] > 0) {
                $this->error("✗ {$stats['errors']} error(s) occurred during sync. Check logs for details.");
                Log::warning('Email Sync Command: Completed with errors', [
                    'statistics' => $stats,
                    'timestamp' => now()->toDateTimeString(),
                ]);
                return Command::FAILURE;
            }

            // Log successful completion
            Log::info('Email Sync Command: Completed successfully', [
                'statistics' => $stats,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Email sync failed: ' . $e->getMessage());
            Log::error('Email Sync Command: Failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'limit' => $limit,
                'user_id' => $userId,
                'timestamp' => now()->toDateTimeString(),
            ]);
            return Command::FAILURE;
        }
    }
}


<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ConvertProbationToPermanent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:convert-probation {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert users from probation to permanent status after 3 months from joining date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('🔄 Probation to Permanent Conversion System');
        $this->info('==========================================');
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }
        
        // Get users who are on probation and have been employed for 3+ months
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        
        $probationUsers = User::where('employment_status', 'probation')
            ->whereNotNull('joining_date')
            ->where('joining_date', '<=', $threeMonthsAgo)
            ->get();
            
        if ($probationUsers->isEmpty()) {
            $this->info('✅ No users found who are eligible for conversion from probation to permanent.');
            return;
        }
        
        $this->info("📊 Found {$probationUsers->count()} users eligible for conversion:");
        $this->newLine();
        
        $convertedCount = 0;
        
        foreach ($probationUsers as $user) {
            $joiningDate = Carbon::parse($user->joining_date);
            $monthsEmployed = $joiningDate->diffInMonths(Carbon::now());
            
            $this->line("👤 User: {$user->name} (ID: {$user->id})");
            $this->line("   📅 Joining Date: {$joiningDate->format('M d, Y')}");
            $this->line("   ⏰ Months Employed: {$monthsEmployed} months");
            $this->line("   📧 Email: {$user->email}");
            
            if (!$isDryRun) {
                // Update employment status to active (permanent)
                $user->update([
                    'employment_status' => 'active',
                    'probation_end_at' => Carbon::now(),
                ]);
                
                $this->info("   ✅ Status updated to: ACTIVE (Permanent)");
                
                Log::info('User converted from probation to permanent', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'joining_date' => $user->joining_date,
                    'conversion_date' => Carbon::now(),
                    'months_employed' => $monthsEmployed,
                ]);
                
                $convertedCount++;
            } else {
                $this->warn("   🔍 Would update status to: ACTIVE (Permanent)");
            }
            
            $this->newLine();
        }
        
        if ($isDryRun) {
            $this->warn("🔍 DRY RUN COMPLETE: {$probationUsers->count()} users would be converted");
        } else {
            $this->info("✅ CONVERSION COMPLETE: {$convertedCount} users converted to permanent status");
            
            // Send notification email to converted users (optional)
            $this->info("📧 Consider sending notification emails to converted users");
        }
        
        // Show summary of all employment statuses
        $this->newLine();
        $this->info('📊 Current Employment Status Summary:');
        $this->info('====================================');
        
        $statusCounts = User::selectRaw('employment_status, COUNT(*) as count')
            ->groupBy('employment_status')
            ->get();
            
        foreach ($statusCounts as $status) {
            $statusName = $status->employment_status ?: 'Not Set';
            $this->line("   {$statusName}: {$status->count} users");
        }
    }
}
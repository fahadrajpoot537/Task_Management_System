<?php

namespace App\Console\Commands;

use App\Services\RecurringTaskService;
use Illuminate\Console\Command;

class ProcessRecurringTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process recurring tasks and create next occurrences';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing recurring tasks...');
        
        $recurringService = new RecurringTaskService();
        $recurringService->processPendingRecurringTasks();
        
        $this->info('Recurring tasks processed successfully!');
        
        return Command::SUCCESS;
    }
}

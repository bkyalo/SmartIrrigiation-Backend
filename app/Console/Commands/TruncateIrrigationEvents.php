<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateIrrigationEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'irrigation:truncate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the irrigation_events table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->confirm('Are you sure you want to truncate the irrigation_events table? This cannot be undone.')) {
            try {
                DB::table('irrigation_events')->truncate();
                $this->info('The irrigation_events table has been truncated successfully.');
                return Command::SUCCESS;
            } catch (\Exception $e) {
                $this->error('Failed to truncate irrigation_events table: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->info('Operation cancelled.');
        return Command::SUCCESS;
    }
}

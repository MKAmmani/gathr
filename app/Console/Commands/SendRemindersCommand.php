<?php

namespace App\Console\Commands;

use App\Jobs\SendRemindersJob;
use Illuminate\Console\Command;

class SendRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually send scheduled reminder emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled reminders...');

        // Dispatch the job
        SendRemindersJob::dispatchSync();

        $this->info('Done! Check logs for details.');
        
        return Command::SUCCESS;
    }
}

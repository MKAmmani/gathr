<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gathr:make-admin {email : The email address of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant administrator privileges to a Gathr user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found.");
            return Command::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();

        $this->info("Success! User '{$user->name}' ({$user->email}) is now an administrator.");
        return Command::SUCCESS;
    }
}

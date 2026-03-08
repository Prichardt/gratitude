<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Gratitude\TierService;
use App\Models\User;

class CheckInactivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gratitude:check-inactivity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flags accounts as inactive if they have not traveled in 2 years and have zero bonus points.';

    /**
     * Execute the console command.
     */
    public function handle(TierService $tierService)
    {
        $this->info('Starting inactivity checks...');
        
        $users = User::all();
        $inactiveCount = 0;

        foreach ($users as $user) {
            if ($tierService->checkInactivity($user->id)) {
                $inactiveCount++;
            }
        }

        $this->info("Successfully flagged {$inactiveCount} accounts as inactive.");
    }
}

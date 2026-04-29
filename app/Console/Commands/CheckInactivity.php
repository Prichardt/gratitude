<?php

namespace App\Console\Commands;

use App\Models\Gratitude\Gratitude;
use App\Services\Gratitude\TierService;
use Illuminate\Console\Command;

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

        $gratitudes = Gratitude::whereNotNull('gratitudeNumber')->get();
        $inactiveCount = 0;

        foreach ($gratitudes as $gratitude) {
            if ($tierService->checkInactivity($gratitude->gratitudeNumber)) {
                $inactiveCount++;
            }
        }

        $this->info("Successfully flagged {$inactiveCount} accounts as inactive.");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Gratitude\PointService;

class ActivatePendingPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gratitude:activate-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activates pending tier points whose usable date has arrived.';

    /**
     * Execute the console command.
     */
    public function handle(PointService $pointService)
    {
        $this->info('Starting point activation...');
        $count = $pointService->activateTierPoints();
        $this->info("Successfully activated {$count} pending point batches.");
    }
}

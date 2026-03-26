<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Gratitude\PointService;

class ExpirePoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gratitude:expire-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks points as expired once they pass the configured expiry days for their level.';

    /**
     * Execute the console command.
     */
    public function handle(PointService $pointService)
    {
        $this->info('Starting point expiration...');
        $expired = $pointService->expirePoints();
        $this->info("Successfully expired {$expired['earned']} earned point records and {$expired['bonus']} bonus point records.");
    }
}

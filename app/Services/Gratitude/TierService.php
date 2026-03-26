<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Gratitude;
use Carbon\Carbon;

class TierService
{
    const TIER_EXPLORER = 'Explorer';
    
    const TIER_GLOBETROTTER = 'Globetrotter';
    const TIER_JETSETTER = 'Jetsetter';

    const BREAKPOINT_GLOBETROTTER = 15001;
    const BREAKPOINT_JETSETTER = 30001;

    /**
     * Recalculates the user's tier based on all active tier points earned in the rolling 2-year window.
     */
    public function recalculateTier($userId)
    {
        $twoYearsAgo = Carbon::today()->subYears(2);

        // Sum the TOTAL remaining tier points that became usable in the last 2 years (and are still active)
        $rollingTotalActive = EarnedPoint::where('user_id', $userId)
            ->activeStatus()
            ->where('usable_date', '>=', $twoYearsAgo)
            ->sum(EarnedPoint::raw('points - redeemed_points'));

        // Determine correct level
        $newLevel = self::TIER_EXPLORER;
        if ($rollingTotalActive >= self::BREAKPOINT_JETSETTER) {
            $newLevel = self::TIER_JETSETTER;
        } elseif ($rollingTotalActive >= self::BREAKPOINT_GLOBETROTTER) {
            $newLevel = self::TIER_GLOBETROTTER;
        }

        // Get or Create gratitude record for the user
        $gratitude = Gratitude::firstOrCreate(
            ['user_id' => $userId],
            ['level' => self::TIER_EXPLORER, 'useablePoints' => 0, 'totalPoints' => 0]
        );

        $oldLevel = $gratitude->level;
        $statusChange = null;

        if ($oldLevel !== $newLevel) {
            $statusChange = $this->determineStatusChange($oldLevel, $newLevel);
            // TODO: Dispatch Event/Email notification indicating tier upgrade/downgrade
        }

        // Also recalculate the current total usable points across both Bonus and Earned
        $totalUsableEarned = EarnedPoint::where('user_id', $userId)
            ->activeStatus()
            ->sum(EarnedPoint::raw('points - redeemed_points'));

        $totalUsableBonus = BonusPoint::where('user_id', $userId)
            ->activeStatus()
            ->sum(BonusPoint::raw('points - redeemed_points'));

        $totalUsable = $totalUsableEarned + $totalUsableBonus;

        $gratitude->update([
            'level' => $newLevel,
            'useablePoints' => $totalUsable,
            'statusChange' => $statusChange ?: $gratitude->statusChange
        ]);

        return $gratitude;
    }

    /**
     * Determines if a tier change constitutes an Upgrade or Downgrade
     */
    private function determineStatusChange($oldLevel, $newLevel)
    {
        $hierarchy = [
            self::TIER_EXPLORER => 1,
            self::TIER_GLOBETROTTER => 2,
            self::TIER_JETSETTER => 3
        ];

        $oldRank = $hierarchy[$oldLevel] ?? 1;
        $newRank = $hierarchy[$newLevel] ?? 1;

        if ($newRank > $oldRank) {
            return 'upgrade';
        } elseif ($newRank < $oldRank) {
            return 'downgrade';
        }

        return null;
    }

    /**
     * Flags an account as inactive if the client has not traveled within 2 years and has 0 Bonus Points.
     */
    public function checkInactivity($userId)
    {
        $twoYearsAgo = Carbon::today()->subYears(2);

        // Check if there are any journeys completed (usable_date is the journey end date) within the last 2 years
        $recentJourneyCount = EarnedPoint::where('user_id', $userId)
            ->whereNotNull('usable_date')
            ->where('usable_date', '>=', $twoYearsAgo)
            ->count();

        if ($recentJourneyCount > 0) {
            // They have traveled recently, not inactive
            return false;
        }

        // Check Bonus Points balance
        $bonusBalance = BonusPoint::where('user_id', $userId)
            ->activeStatus()
            ->sum(BonusPoint::raw('points - redeemed_points'));

        if ($bonusBalance <= 0) {
            // Mark account inactive
            $gratitude = Gratitude::where('user_id', $userId)->first();
            if ($gratitude) {
                $gratitude->update(['status' => 'inactive']);
                // TODO: Dispatch warning email for inactivity
            }
            return true;
        }

        return false;
    }
}

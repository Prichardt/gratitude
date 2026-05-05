<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\GratitudeLevel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TierService
{
    const TIER_EXPLORER = 'Explorer';

    const TIER_GLOBETROTTER = 'Globetrotter';

    const TIER_JETSETTER = 'Jetsetter';

    /**
     * Recalculate a member's tier based on earned journey points within the membership interval.
     *
     * Rules:
     *  - Only earned journey points drive level changes — bonus points and redemptions are excluded.
     *  - The interval starts at level_obtained_at and spans level_interval_years years.
     *  - Before the interval expires: a member can be upgraded, but is not downgraded mid-interval.
     *  - Once the interval expires: the earned journey points inside that interval are evaluated
     *    and a fresh 2-year interval begins.
     *  - If systemLevelUpdate = false the level was manually overridden; skip auto-recalc.
     *  - Jetsetter also requires a minimum number of qualifying journeys (by length).
     *
     * @param  int|string  $userId
     * @param  string  $changedBy  'system' or an admin identifier
     */
    public function recalculateTier($gratitudeNumber, string $changedBy = 'system'): ?Gratitude
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->first();
        if (! $gratitude) {
            return null;
        }

        // Respect manual overrides: do not touch the level
        if (! $gratitude->systemLevelUpdate) {
            return $gratitude;
        }

        // Load all active levels ordered highest → lowest
        $allLevels = GratitudeLevel::where('status', true)
            ->orderByDesc('min_points')
            ->get();

        if ($allLevels->isEmpty()) {
            return $gratitude;
        }

        $now = Carbon::now();

        // Determine the interval length from the current level config (default 2 years)
        $currentLevelConfig = $allLevels->firstWhere('name', $gratitude->level)
            ?? $allLevels->sortBy('min_points')->first();

        $intervalYears = (int) ($currentLevelConfig->level_interval_years ?? 2);

        $intervalStart = $gratitude->level_obtained_at
            ? Carbon::parse($gratitude->level_obtained_at)
            : ($gratitude->created_at ? Carbon::parse($gratitude->created_at) : $now->copy());

        $intervalEnd = $intervalStart->copy()->addYears($intervalYears);
        $intervalExpired = $now->greaterThan($intervalEnd);

        // When the interval has expired, evaluate only the closed window [intervalStart, intervalEnd]
        // so that points earned after expiry correctly count toward the fresh window.
        // During an active window evaluate up to now (supports mid-interval upgrades).
        $pointsUpperBound = $intervalExpired ? $intervalEnd : $now;

        // Earned journey points within the evaluation window. Redeemed points do not
        // reduce tier qualification; cancellations do.
        $earnedInInterval = (int) EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->activeStatus()
            ->whereNull('cancel_id')
            ->whereNotNull('journey_id')
            ->whereNotNull('usable_date')
            ->where('usable_date', '>=', $intervalStart)
            ->where('usable_date', '<=', $pointsUpperBound)
            ->sum(DB::raw('CASE WHEN COALESCE(points, 0) - COALESCE(cancelled_points, 0) > 0 THEN COALESCE(points, 0) - COALESCE(cancelled_points, 0) ELSE 0 END'));

        $oldLevel = $gratitude->level ?? self::TIER_EXPLORER;
        $newLevel = $this->resolveLevel($earnedInInterval, $gratitudeNumber, $intervalStart, $pointsUpperBound, $allLevels);

        $hierarchy = $this->buildHierarchy($allLevels);
        $oldRank = $hierarchy[$oldLevel] ?? 1;
        $newRank = $hierarchy[$newLevel] ?? 1;

        if (! $gratitude->level || $newRank > $oldRank || $intervalExpired) {
            // For an expired interval: new window starts at intervalEnd so any points earned
            // after the old window closes are counted in the next recalculation.
            // For an upgrade mid-interval: window restarts from now.
            $newIntervalStart = $intervalExpired ? $intervalEnd : $now;
            $this->applyLevelChange($gratitude, $oldLevel, $newLevel, $earnedInInterval, $changedBy, $now, $newIntervalStart);
        }

        return $gratitude->fresh();
    }

    /**
     * Force a manual level override. Sets systemLevelUpdate = false so auto-recalc is suppressed.
     *
     * @param  string  $changedBy  Admin identifier or user description
     */
    public function setLevelManually(Gratitude $gratitude, string $newLevel, string $changedBy, ?string $reason = null): Gratitude
    {
        $oldLevel = $gratitude->level ?? self::TIER_EXPLORER;
        $now = Carbon::now();

        $earnedPoints = (int) EarnedPoint::where('gratitudeNumber', $gratitude->gratitudeNumber)
            ->activeStatus()
            ->whereNotNull('journey_id')
            ->sum(DB::raw('CASE WHEN COALESCE(points, 0) - COALESCE(cancelled_points, 0) > 0 THEN COALESCE(points, 0) - COALESCE(cancelled_points, 0) ELSE 0 END'));

        $history = $this->appendLevelHistory(
            $gratitude->levelHistory ?? [],
            $oldLevel,
            $newLevel,
            $now,
            $earnedPoints,
            $changedBy,
            $this->determineStatusChange($oldLevel, $newLevel),
            $reason ?? 'Manual override'
        );

        $gratitude->update([
            'level' => $newLevel,
            'levelHistory' => $history,
            'level_obtained_at' => $now,
            'statusChange' => $this->determineStatusChange($oldLevel, $newLevel),
            'statusChangeReason' => $reason ?? 'Manual override',
            'systemLevelUpdate' => false,
        ]);

        return $gratitude->fresh();
    }

    /**
     * Re-enable automatic level management after a manual override.
     */
    public function enableAutoLevelUpdate(Gratitude $gratitude): Gratitude
    {
        $gratitude->update(['systemLevelUpdate' => true]);

        return $gratitude->fresh();
    }

    /**
     * Flags an account as inactive if the member has not traveled in the last 2 years
     * and has no remaining bonus points.
     */
    public function checkInactivity($gratitudeNumber): bool
    {
        $twoYearsAgo = Carbon::today()->subYears(2);

        $recentJourneyCount = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNotNull('usable_date')
            ->where('usable_date', '>=', $twoYearsAgo)
            ->count();

        if ($recentJourneyCount > 0) {
            return false;
        }

        $bonusBalance = BonusPoint::where('gratitudeNumber', $gratitudeNumber)
            ->activeStatus()
            ->sum(BonusPoint::raw('CASE WHEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) - COALESCE(cancelled_points, 0) > 0 THEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) - COALESCE(cancelled_points, 0) ELSE 0 END'));

        if ($bonusBalance <= 0) {
            $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->first();
            if ($gratitude) {
                $gratitude->update(['status' => 'inactive']);
            }

            return true;
        }

        return false;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Walk levels highest→lowest and return the first one the member qualifies for.
     * Jetsetter also requires minimum qualifying journeys within the interval.
     */
    private function resolveLevel(
        int $earnedPoints,
        $gratitudeNumber,
        Carbon $intervalStart,
        Carbon $intervalEnd,
        $levels
    ): string {
        foreach ($levels->sortByDesc('min_points') as $level) {
            if ($earnedPoints < (int) $level->min_points) {
                continue;
            }

            // Jetsetter travel requirement
            if ($level->jetsetter_min_journeys && $level->jetsetter_min_journey_days) {
                if (! $this->meetsJetsetterTravelRequirement($gratitudeNumber, $intervalStart, $intervalEnd, $level)) {
                    continue;
                }
            }

            return $level->name;
        }

        // Default to the lowest level
        $lowestLevel = $levels->sortBy('min_points')->first();

        return $lowestLevel ? $lowestLevel->name : self::TIER_EXPLORER;
    }

    /**
     * Count qualifying journeys for Jetsetter within the interval.
     * A qualifying journey has a duration >= jetsetter_min_journey_days.
     * Duration = DATEDIFF(usable_date, date) — usable_date is return date, date is departure.
     */
    private function meetsJetsetterTravelRequirement($gratitudeNumber, Carbon $from, Carbon $to, $level): bool
    {
        $minDays = (int) $level->jetsetter_min_journey_days;

        $count = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNotNull('usable_date')
            ->whereNotNull('date')
            ->whereNull('cancel_id')
            ->whereNotNull('journey_id')
            ->where('usable_date', '>=', $from)
            ->where('usable_date', '<=', $to)
            ->whereRaw('DATEDIFF(usable_date, date) >= ?', [$minDays])
            ->count();

        return $count >= (int) $level->jetsetter_min_journeys;
    }

    /**
     * Apply a level change (or a no-change interval renewal), persist history, and save.
     *
     * @param  Carbon|null  $levelObtainedAt  Start of the new interval. Defaults to $now.
     *                                         Pass $intervalEnd when triggered by expiry so
     *                                         any activity after the window counts next time.
     */
    private function applyLevelChange(
        Gratitude $gratitude,
        string $oldLevel,
        string $newLevel,
        int $earnedPoints,
        string $changedBy,
        Carbon $now,
        ?Carbon $levelObtainedAt = null
    ): void {
        $changeType = $this->determineStatusChange($oldLevel, $newLevel);

        $history = $this->appendLevelHistory(
            $gratitude->levelHistory ?? [],
            $oldLevel,
            $newLevel,
            $now,
            $earnedPoints,
            $changedBy,
            $changeType
        );

        $gratitude->update([
            'level' => $newLevel,
            'levelHistory' => $history,
            'level_obtained_at' => $levelObtainedAt ?? $now,
            'statusChange' => $changeType,
            'statusChangeReason' => $this->buildChangeReason($changeType, $oldLevel, $newLevel),
        ]);
    }

    /**
     * Append a structured entry to the level history array.
     *
     * History entry shape:
     * {
     *   "fromLevel":     "Explorer",
     *   "toLevel":       "Globetrotter",
     *   "changeType":    "upgrade",   // upgrade | downgrade | maintained | initial
     *   "date":          "2026-04-02",
     *   "earnedPoints":  16500,
     *   "changedBy":     "system",
     *   "reason":        "Points threshold met"
     * }
     */
    private function appendLevelHistory(
        array $history,
        string $fromLevel,
        string $toLevel,
        Carbon $date,
        int $earnedPoints,
        string $changedBy,
        ?string $changeType,
        ?string $reason = null
    ): array {
        $history[] = [
            'fromLevel' => $fromLevel,
            'toLevel' => $toLevel,
            'changeType' => $changeType ?? 'maintained',
            'date' => $date->toDateString(),
            'earnedPoints' => $earnedPoints,
            'changedBy' => $changedBy,
            'reason' => $reason ?? $this->buildChangeReason($changeType, $fromLevel, $toLevel),
        ];

        return $history;
    }

    private function determineStatusChange(string $oldLevel, string $newLevel): ?string
    {
        if ($oldLevel === $newLevel) {
            return 'maintained';
        }

        $allLevels = GratitudeLevel::where('status', true)->get();
        $hierarchy = $this->buildHierarchy($allLevels);

        $oldRank = $hierarchy[$oldLevel] ?? 1;
        $newRank = $hierarchy[$newLevel] ?? 1;

        return $newRank > $oldRank ? 'upgrade' : 'downgrade';
    }

    private function buildHierarchy($levels): array
    {
        $sorted = $levels->sortBy('min_points')->values();
        $map = [];
        foreach ($sorted as $i => $level) {
            $map[$level->name] = $i + 1;
        }

        return $map;
    }

    private function buildChangeReason(?string $changeType, string $fromLevel, string $toLevel): string
    {
        return match ($changeType) {
            'upgrade' => "Upgraded from {$fromLevel} to {$toLevel} — points threshold met, 2-year window restarted",
            'downgrade' => "Downgraded from {$fromLevel} to {$toLevel} — 2-year window expired with insufficient qualifying activity",
            'maintained' => "Level {$fromLevel} maintained — 2-year window renewed",
            default => "Level set to {$toLevel}",
        };
    }
}

<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\GratitudeLevel;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class PointExpiryService
{
    public const DEFAULT_EXPIRE_DAYS = 730;

    public function resolveLevelForGratitude(?Gratitude $gratitude): ?GratitudeLevel
    {
        
        if (!$gratitude?->level) {
            return null;
        }

        return GratitudeLevel::where('name', $gratitude->level)->first();
    }

    // Gratitudes have no user_id column — look up by gratitudeNumber instead.
    public function resolveLevelForGratitudeNumber(?string $gratitudeNumber): ?GratitudeLevel
    {
        if (!$gratitudeNumber) {
            return null;
        }

        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->first();

        return $this->resolveLevelForGratitude($gratitude);
    }

    public function getEarnedExpireDays(?GratitudeLevel $level): int
    {
        return max(1, (int) ($level?->earned_expire_days ?: self::DEFAULT_EXPIRE_DAYS));
    }

    public function getBonusExpireDays(?GratitudeLevel $level): int
    {
        return max(1, (int) ($level?->bonus_expire_days ?: self::DEFAULT_EXPIRE_DAYS));
    }

    public function calculateEarnedExpiry(?CarbonInterface $baseDate, ?GratitudeLevel $level): ?Carbon
    {
        if (!$baseDate) {
            return null;
        }

        return Carbon::instance($baseDate)->copy()->addDays($this->getEarnedExpireDays($level));
    }

    public function calculateBonusExpiry(?CarbonInterface $baseDate, ?GratitudeLevel $level): ?Carbon
    {
        if (!$baseDate) {
            return null;
        }

        return Carbon::instance($baseDate)->copy()->addDays($this->getBonusExpireDays($level));
    }
}

<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\BonusPoint;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;

class PointService
{
    public function __construct(
        protected PointExpiryService $pointExpiryService
    ) {
    }

    /**
     * Posts pending tier points from a signed agreement.
     */
    public function addTierPoints($userId, $points, $usableDate, $journeyId = null, $amount = null, $description = null)
    {
        return EarnedPoint::create([
            'user_id' => $userId,
            'journey_id' => $journeyId,
            'points' => $points,
            'usable_date' => $usableDate,
            'status' => 'pending',
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    /**
     * Activates pending points where the usable_date has arrived.
     */
    public function activateTierPoints()
    {
        // Find all pending points where the usable_date is today or in the past
        $pointsToActivate = EarnedPoint::where('status', 'pending')
            ->whereDate('usable_date', '<=', Carbon::today())
            ->get();

        foreach ($pointsToActivate as $point) {
            $level = $this->resolveLevelForPoint($point->gratitudeNumber, $point->user_id);

            $point->update([
                'status' => 'active',
                'expires_at' => $this->pointExpiryService->calculateEarnedExpiry(
                    Carbon::parse($point->usable_date),
                    $level
                ),
            ]);
        }

        return $pointsToActivate->count();
    }

    /**
     * Immediately awards active bonus points.
     */
    public function addBonusPoints($userId, $points, $category = null, $description = null)
    {
        $level = $this->pointExpiryService->resolveLevelForUserId($userId);

        return BonusPoint::create([
            'user_id' => $userId,
            'points' => $points,
            'date' => Carbon::today(),
            'status' => true,
            'expires_at' => $this->pointExpiryService->calculateBonusExpiry(Carbon::today(), $level),
            'category' => $category,
            'description' => $description,
        ]);
    }

    /**
     * Redeems points utilizing a FIFO approach (oldest expiring points first).
     */
    public function redeemPoints($userId, $pointsToRedeem, $reason = null)
    {
        if ($pointsToRedeem <= 0) {
            throw new Exception("Points to redeem must be greater than zero.");
        }

        return DB::transaction(function () use ($userId, $pointsToRedeem, $reason) {
            $allPoints = $this->buildRedemptionQueue($userId);

            // Calculate total available to prevent partial redemptions if they don't have enough
            $totalAvailable = $allPoints->sum('remaining_points');

            if ($totalAvailable < $pointsToRedeem) {
                throw new Exception("Insufficient active points available for redemption.");
            }

            $remainingToRedeem = $pointsToRedeem;

            foreach ($allPoints as $pointRecord) {
                if ($remainingToRedeem <= 0) {
                    break;
                }

                $availableInRecord = $pointRecord->remaining_points;
                $deductAmount = min($availableInRecord, $remainingToRedeem);

                // Update the point record's redeemed_points sum
                $pointRecord->redeemed_points += $deductAmount;
                $pointRecord->save();

                // Log the redemption securely in the point_redemptions relational table
                $pointRecord->redemptions()->create([
                    'user_id' => $userId,
                    'points_redeemed' => $deductAmount,
                    'reason' => $reason
                ]);

                $remainingToRedeem -= $deductAmount;
            }

            return true;
        });
    }

    protected function buildRedemptionQueue(int $userId)
    {
        $now = Carbon::now();

        $earnedPoints = EarnedPoint::where('user_id', $userId)
            ->activeStatus()
            ->whereRaw('points - redeemed_points > 0')
            ->where('expires_at', '>', $now)
            ->lockForUpdate()
            ->get();

        $bonusPoints = BonusPoint::where('user_id', $userId)
            ->activeStatus()
            ->whereRaw('points - redeemed_points > 0')
            ->where('expires_at', '>', $now)
            ->lockForUpdate()
            ->get();

        return $earnedPoints
            ->concat($bonusPoints)
            ->sort(function ($left, $right) {
                $expiryComparison = $this->compareRedemptionDates($left->expires_at, $right->expires_at);
                if ($expiryComparison !== 0) {
                    return $expiryComparison;
                }

                $effectiveDateComparison = $this->compareRedemptionDates(
                    $this->getRedemptionEffectiveDate($left),
                    $this->getRedemptionEffectiveDate($right)
                );
                if ($effectiveDateComparison !== 0) {
                    return $effectiveDateComparison;
                }

                $typeComparison = $this->getRedemptionTypePriority($left) <=> $this->getRedemptionTypePriority($right);
                if ($typeComparison !== 0) {
                    return $typeComparison;
                }

                return ($left->id ?? 0) <=> ($right->id ?? 0);
            })
            ->values();
    }

    protected function compareRedemptionDates(null|CarbonInterface|string $leftDate, null|CarbonInterface|string $rightDate): int
    {
        $leftTimestamp = $leftDate ? Carbon::parse($leftDate)->startOfDay()->timestamp : PHP_INT_MAX;
        $rightTimestamp = $rightDate ? Carbon::parse($rightDate)->startOfDay()->timestamp : PHP_INT_MAX;

        return $leftTimestamp <=> $rightTimestamp;
    }

    protected function getRedemptionEffectiveDate($segment): ?CarbonInterface
    {
        return $segment->usable_date ?? $segment->date ?? $segment->created_at;
    }

    protected function getRedemptionTypePriority($segment): int
    {
        return $segment instanceof BonusPoint ? 0 : 1;
    }

    /**
     * Expire points that are past their expiration date.
     */
    public function expirePoints()
    {
        $earnedToExpire = EarnedPoint::activeStatus()
            ->where('expires_at', '<', Carbon::now())
            ->whereRaw('points - redeemed_points > 0')
            ->get(['id', 'gratitudeNumber']);

        $earnedExpired = 0;
        if ($earnedToExpire->isNotEmpty()) {
            $earnedExpired = EarnedPoint::whereIn('id', $earnedToExpire->pluck('id'))
                ->update(['status' => 'expired']);
        }

        $bonusToExpire = BonusPoint::activeStatus()
            ->where('expires_at', '<', Carbon::now())
            ->whereRaw('points - redeemed_points > 0')
            ->get(['id', 'gratitudeNumber']);

        $bonusExpired = 0;
        if ($bonusToExpire->isNotEmpty()) {
            $bonusExpired = BonusPoint::whereIn('id', $bonusToExpire->pluck('id'))
                ->update(['status' => false]);
        }

        $gratitudeNumbers = $earnedToExpire
            ->pluck('gratitudeNumber')
            ->merge($bonusToExpire->pluck('gratitudeNumber'))
            ->filter()
            ->unique();

        foreach ($gratitudeNumbers as $gratitudeNumber) {
            GratitudeService::syncAccountBalance($gratitudeNumber);
        }

        return ['earned' => $earnedExpired, 'bonus' => $bonusExpired];
    }

    protected function resolveLevelForPoint(?string $gratitudeNumber, ?int $userId)
    {
        $gratitude = null;

        if ($gratitudeNumber) {
            $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->first();
        }

        if (!$gratitude && $userId) {
            $gratitude = Gratitude::where('user_id', $userId)->first();
        }

        return $this->pointExpiryService->resolveLevelForGratitude($gratitude);
    }
}

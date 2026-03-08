<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\PointRedemption;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PointService
{
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
            $point->update([
                'status' => 'active',
                'expires_at' => Carbon::parse($point->usable_date)->addYears(2),
            ]);
        }

        return $pointsToActivate->count();
    }

    /**
     * Immediately awards active bonus points.
     */
    public function addBonusPoints($userId, $points, $category = null, $description = null)
    {
        return BonusPoint::create([
            'user_id' => $userId,
            'points' => $points,
            'date' => Carbon::today(),
            'status' => 'active',
            'expires_at' => Carbon::today()->addYears(2),
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
            // Get all ACTIVE earned and bonus points with remaining points, ordered by expiration ascending
            $earnedPoints = EarnedPoint::where('user_id', $userId)
                ->where('status', 'active')
                ->whereRaw('points - redeemed_points > 0')
                ->where('expires_at', '>', Carbon::now())
                ->orderBy('expires_at', 'asc')
                ->lockForUpdate()
                ->get();
                
            $bonusPoints = BonusPoint::where('user_id', $userId)
                ->where('status', 'active')
                ->whereRaw('points - redeemed_points > 0')
                ->where('expires_at', '>', Carbon::now())
                ->orderBy('expires_at', 'asc')
                ->lockForUpdate()
                ->get();

            // Merge and sort both sets by expiration date to ensure strict FIFO
            $allPoints = $earnedPoints->concat($bonusPoints)->sortBy('expires_at');

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

    /**
     * Expire points that are past their expiration date.
     */
    public function expirePoints()
    {
        $earnedExpired = EarnedPoint::where('status', 'active')
            ->where('expires_at', '<', Carbon::now())
            ->whereRaw('points - redeemed_points > 0')
            ->update(['status' => 'expired']);

        $bonusExpired = BonusPoint::where('status', 'active')
            ->where('expires_at', '<', Carbon::now())
            ->whereRaw('points - redeemed_points > 0')
            ->update(['status' => 'expired']);

        return ['earned' => $earnedExpired, 'bonus' => $bonusExpired];
    }
}

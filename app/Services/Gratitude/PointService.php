<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\RedeemPoints;
use App\Models\Gratitude\RedeemPointsDetails;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function __construct(
        protected PointExpiryService $pointExpiryService
    ) {}

    /**
     * Posts pending tier points for a gratitude account.
     * Identified by gratitudeNumber — there is no user_id on the gratitudes table.
     */
    public function addTierPoints(string $gratitudeNumber, $points, $usableDate, $journeyId = null, $amount = null, $description = null)
    {
        return EarnedPoint::create([
            'gratitudeNumber' => $gratitudeNumber,
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
        $pointsToActivate = EarnedPoint::where('status', 'pending')
            ->whereDate('usable_date', '<=', Carbon::today())
            ->get();

        foreach ($pointsToActivate as $point) {
            $level = $this->resolveLevelForPoint($point->gratitudeNumber);

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
     * Immediately awards active bonus points to a gratitude account.
     */
    public function addBonusPoints(string $gratitudeNumber, $points, $category = null, $description = null)
    {
        $level = $this->pointExpiryService->resolveLevelForGratitudeNumber($gratitudeNumber);

        return BonusPoint::create([
            'gratitudeNumber' => $gratitudeNumber,
            'points' => $points,
            'date' => Carbon::today(),
            'status' => true,
            'expires_at' => $this->pointExpiryService->calculateBonusExpiry(Carbon::today(), $level),
            'category' => $category,
            'description' => $description,
        ]);
    }

    /**
     * Redeems points from a gratitude account using FIFO (oldest-expiring first).
     * Creates a RedeemPoints master record and per-segment RedeemPointsDetails for full history.
     */
    public function redeemPoints(string $gratitudeNumber, $pointsToRedeem, $reason = null, $userId = null)
    {
        if ($pointsToRedeem <= 0) {
            throw new Exception('Points to redeem must be greater than zero.');
        }

        return DB::transaction(function () use ($gratitudeNumber, $pointsToRedeem, $reason, $userId) {
            $allPoints = $this->buildRedemptionQueue($gratitudeNumber);

            $totalAvailable = $allPoints->sum('remaining_points');

            if ($totalAvailable < $pointsToRedeem) {
                throw new Exception('Insufficient active points available for redemption.');
            }

            // Create the master redemption record first
            $redemption = RedeemPoints::create([
                'gratitudeNumber' => $gratitudeNumber,
                'user_id' => $userId,
                'points' => $pointsToRedeem,
                'amount' => 0, // caller can update after
                'reason' => $reason ?? 'Point Redemption',
                'status' => 'approved',
            ]);

            $remainingToRedeem = $pointsToRedeem;

            foreach ($allPoints as $pointRecord) {
                if ($remainingToRedeem <= 0) {
                    break;
                }

                $available = $pointRecord->remaining_points;
                $deductAmount = min($available, $remainingToRedeem);

                // Append to the segment's redemption history JSON
                $history = is_array($pointRecord->redemption_history) ? $pointRecord->redemption_history : [];
                $history[] = [
                    'redemption_id' => $redemption->id,
                    'date' => Carbon::now()->toDateString(),
                    'points' => $deductAmount,
                    'reason' => $reason ?? 'Point Redemption',
                ];

                $pointRecord->getConnection()
                    ->table($pointRecord->getTable())
                    ->where('id', $pointRecord->id)
                    ->update([
                        'redeemed_points' => $pointRecord->redeemed_points + $deductAmount,
                        'redemption_history' => json_encode($history),
                        'updated_at' => Carbon::now(),
                    ]);

                // Polymorphic detail record for full audit trail
                RedeemPointsDetails::create([
                    'redeem_id' => $redemption->id,
                    'source_id' => $pointRecord->id,
                    'source_type' => get_class($pointRecord),
                    'user_id' => $userId,
                    'points' => $deductAmount,
                ]);

                $remainingToRedeem -= $deductAmount;
            }

            GratitudeService::syncAccountBalance($gratitudeNumber);

            return $redemption;
        });
    }

    /**
     * Expire points that are past their expiration date.
     */
    public function expirePoints()
    {
        $now = Carbon::now();

        $earnedToExpire = EarnedPoint::activeStatus()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->whereRaw('COALESCE(points, 0) > COALESCE(redeemed_points, 0) + COALESCE(cancelled_points, 0)')
            ->get(['id', 'gratitudeNumber']);

        $earnedExpired = 0;
        if ($earnedToExpire->isNotEmpty()) {
            $earnedExpired = EarnedPoint::whereIn('id', $earnedToExpire->pluck('id'))
                ->update(['status' => 'expired']);
        }

        $bonusToExpire = BonusPoint::activeStatus()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->whereRaw('COALESCE(points, 0) > COALESCE(redeemed_points, 0) + COALESCE(cancelled_points, 0)')
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

    protected function buildRedemptionQueue(string $gratitudeNumber)
    {
        $now = Carbon::now();

        $applyFilters = function ($query) use ($gratitudeNumber, $now) {
            return $query
                ->where('gratitudeNumber', $gratitudeNumber)
                ->activeStatus()
                ->whereNull('cancel_id')
                ->where(function ($q) use ($now) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('usable_date')->orWhere('usable_date', '<=', $now);
                })
                ->whereRaw('COALESCE(points, 0) > COALESCE(redeemed_points, 0) + COALESCE(cancelled_points, 0)')
                ->lockForUpdate();
        };

        $earnedPoints = $applyFilters(EarnedPoint::query())->get();
        $bonusPoints = $applyFilters(BonusPoint::query())->get();

        return $earnedPoints
            ->concat($bonusPoints)
            ->map(function ($point) {
                $point->remaining_points = max(
                    0,
                    (int) $point->points - (int) $point->redeemed_points - (int) $point->cancelled_points
                );

                return $point;
            })
            ->filter(fn ($p) => $p->remaining_points > 0)
            ->sort(function ($left, $right) {
                // 1. Soonest-expiring first (nulls last — no expiry = expire last)
                $leftExpiry = $left->expires_at ? Carbon::parse($left->expires_at)->timestamp : PHP_INT_MAX;
                $rightExpiry = $right->expires_at ? Carbon::parse($right->expires_at)->timestamp : PHP_INT_MAX;
                if ($leftExpiry !== $rightExpiry) {
                    return $leftExpiry <=> $rightExpiry;
                }

                // 2. Earliest effective date (usable_date > date > created_at)
                $leftDate = Carbon::parse($left->usable_date ?? $left->date ?? $left->created_at)->timestamp;
                $rightDate = Carbon::parse($right->usable_date ?? $right->date ?? $right->created_at)->timestamp;
                if ($leftDate !== $rightDate) {
                    return $leftDate <=> $rightDate;
                }

                // 3. Earned before bonus (earned = 1, bonus = 2)
                $leftType = $left  instanceof BonusPoint ? 2 : 1;
                $rightType = $right instanceof BonusPoint ? 2 : 1;
                if ($leftType !== $rightType) {
                    return $leftType <=> $rightType;
                }

                // 4. ID tiebreaker
                return ((int) $left->id) <=> ((int) $right->id);
            })
            ->values();
    }

    /**
     * Resolve the GratitudeLevel for a gratitude account by gratitudeNumber.
     */
    protected function resolveLevelForPoint(?string $gratitudeNumber)
    {
        if (! $gratitudeNumber) {
            return null;
        }

        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->first();

        return $this->pointExpiryService->resolveLevelForGratitude($gratitude);
    }
}

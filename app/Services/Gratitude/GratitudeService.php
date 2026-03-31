<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\GratitudeLevel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GratitudeService
{
    public function __construct()
    {

    }

    /**
     * Redeem points from a gratitude account using FIFO expiry logic.
     * Auto-calculates monetary value from the level's redeemation_points_per_dollar rate.
     */
    public function redeemPoints($gratitude_number, $data, $points)
    {
        try {
            return DB::transaction(function () use ($gratitude_number, $data, $points) {
                $getGratitude = Gratitude::where('gratitudeNumber', $gratitude_number)
                    ->lockForUpdate()
                    ->first();

                if (!$getGratitude) {
                    return false;
                }

                $level = GratitudeLevel::where('name', $getGratitude->level)->first();
                $pointsPerDollar = $level ? (float) $level->redemption_points_per_dollar : 35;
                $monetaryValue = round($points / $pointsPerDollar, 2);
                $now = Carbon::now();

                $allPoints = $this->buildRedemptionQueue($gratitude_number, $now);

                $availableSum = $allPoints->sum(function ($segment) {
                    return (float) $segment->points - (float) $segment->redeemed_points;
                });

                if ($availableSum < $points) {
                    return false;
                }

                $redemption = \App\Models\Gratitude\RedeemPoints::create([
                    'user_id' => 5,
                    'gratitudeNumber' => $gratitude_number,
                    'points' => $points,
                    'amount' => $data['amount'] ?? $monetaryValue,
                    'reason' => $data['reason'] ?? 'Point Redemption',
                    'status' => 'approved',
                ]);


                $pointsRemaining = $points;

                foreach ($allPoints as $segment) {

                    if ($pointsRemaining <= 0) {
                        break;
                    }

                    $available = (float) $segment->points - (float) $segment->redeemed_points;
                    if ($available <= 0) {
                        continue;
                    }

                    $toDeduct = min($available, $pointsRemaining);

                    $segmentMonetaryValue = round($toDeduct / $pointsPerDollar, 2);
                    $existingHistory = is_array($segment->redemption_history) ? $segment->redemption_history : [];
                    $existingHistory[] = [
                        'redemption_id' => $redemption->id,
                        'date' => Carbon::now()->toDateString(),
                        'points' => $toDeduct,
                        'amount' => $segmentMonetaryValue,
                        'reason' => $data['reason'] ?? 'Point Redemption',
                        'level_at_redemption' => $getGratitude->level,
                        'points_per_dollar' => $pointsPerDollar,
                        'journey_data' => $segment->project_data ?? null,
                    ];

                    $segment->getConnection()
                        ->table($segment->getTable())
                        ->where('id', $segment->id)
                        ->update([
                            'redeemed_points' => $segment->redeemed_points + $toDeduct,
                            'redemption_history' => json_encode($existingHistory),
                            'updated_at' => Carbon::now(),
                        ]);

                    $segment->redeemed_points += $toDeduct;

                    \App\Models\Gratitude\RedeemPointsDetails::create([
                        'user_id' => $getGratitude->user_id,
                        'redeem_id' => $redemption->id,
                        'source_id' => $segment->id,
                        'source_type' => get_class($segment),
                        'points' => $toDeduct,
                    ]);

                    $pointsRemaining -= $toDeduct;
                }

                self::syncAccountBalance($gratitude_number);

                return $redemption;
            });
        } catch (\Throwable $e) {
            dd($e->getMessage());
            return false;
        }
    }



    protected function buildRedemptionQueue(string $gratitudeNumber, Carbon $now): Collection
    {
        $applyFilters = function ($query) use ($gratitudeNumber, $now) {
            return $query
                ->where('gratitudeNumber', $gratitudeNumber)
                ->activeStatus()
                ->whereNull('cancel_id')
                ->where(function ($q) use ($now) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('usable_date')
                        ->orWhere('usable_date', '<=', $now);
                })
                ->whereRaw('COALESCE(points, 0) > COALESCE(redeemed_points, 0)')
                ->lockForUpdate();
        };

        $earnedPoints = $applyFilters(EarnedPoint::query())->get();
        $bonusPoints = $applyFilters(BonusPoint::query())->get();

        $result = $earnedPoints
            ->concat($bonusPoints)
            ->map(function ($point) {
                $point->available_points = max(0, (int) $point->points - (int) $point->redeemed_points);
                $point->type = $point instanceof BonusPoint ? 'bonus' : 'earned';

                return $point;
            })
            ->filter(fn($point) => $point->available_points > 0)
            ->sort(function ($left, $right) {
                $leftExpiry = $left->expires_at ? Carbon::parse($left->expires_at) : null;
                $rightExpiry = $right->expires_at ? Carbon::parse($right->expires_at) : null;

                if ($leftExpiry && $rightExpiry) {
                    $cmp = $leftExpiry->timestamp <=> $rightExpiry->timestamp;
                    if ($cmp !== 0) {
                        return $cmp;
                    }
                } elseif ($leftExpiry || $rightExpiry) {
                    return $leftExpiry ? -1 : 1;
                }

                $leftEffectiveDate = Carbon::parse($left->usable_date ?? $left->date ?? $left->created_at);
                $rightEffectiveDate = Carbon::parse($right->usable_date ?? $right->date ?? $right->created_at);

                $cmp = $leftEffectiveDate->timestamp <=> $rightEffectiveDate->timestamp;
                if ($cmp !== 0) {
                    return $cmp;
                }

                $leftTypePriority = $left->type === 'earned' ? 1 : 2;
                $rightTypePriority = $right->type === 'earned' ? 1 : 2;

                $cmp = $leftTypePriority <=> $rightTypePriority;
                if ($cmp !== 0) {
                    return $cmp;
                }

                return ((int) $left->id) <=> ((int) $right->id);
            })
            ->values();

        // dd($result->toArray());
        return $result;
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

    public static function updateRedemption($id, $data)
    {
        $redemption = \App\Models\Gratitude\RedeemPoints::findOrFail($id);
        $redemption->update([
            'reason' => $data['reason'] ?? $redemption->reason,
            'amount' => $data['amount'] ?? $redemption->amount,
        ]);
        return $redemption;
    }

    public static function deleteRedemption($id)
    {
        $redemption = \App\Models\Gratitude\RedeemPoints::with('details')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Restore points to original sources and remove history entry
            foreach ($redemption->details as $detail) {
                $source = $detail->source; // EarnedPoint or BonusPoint
                if ($source) {
                    $source->redeemed_points = max(0, $source->redeemed_points - $detail->points);

                    // Strip this redemption's history entry from the segment
                    $history = $source->redemption_history ?? [];
                    $source->redemption_history = array_values(
                        array_filter($history, fn($entry) => ($entry['redemption_id'] ?? null) != $id)
                    );

                    $source->save();
                }
                $detail->delete();
            }

            $gratitudeNumber = $redemption->gratitudeNumber;
            $redemption->delete();

            self::syncAccountBalance($gratitudeNumber);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public static function syncAccountBalance($gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->first();
        if (!$gratitude)
            return;

        $now = Carbon::now();

        // Total lifetime points: all uncancelled earned + bonus
        $earnedTotal = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)->whereNull('cancel_id')->sum('points');
        $bonusTotal = BonusPoint::where('gratitudeNumber', $gratitudeNumber)->whereNull('cancel_id')->sum('points');
        $totalPoints = $earnedTotal + $bonusTotal;

        // Useable: uncancelled, unexpired, status=true (bit 1), usable_date has passed
        $earnedUseable = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNull('cancel_id')
            ->activeStatus()
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('usable_date')->orWhere('usable_date', '<=', $now);
            })
            ->sum(DB::raw('points - redeemed_points'));

        $bonusUseable = BonusPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNull('cancel_id')
            ->activeStatus()
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('usable_date')->orWhere('usable_date', '<=', $now);
            })
            ->sum(DB::raw('points - redeemed_points'));

        $gratitude->totalPoints = max(0, $totalPoints);
        $gratitude->useablePoints = max(0, $earnedUseable + $bonusUseable);
        $gratitude->save();

        return $gratitude;
    }
}

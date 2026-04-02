<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Cancellation;
use App\Models\Gratitude\RedeemPoints;
use App\Models\Gratitude\GratitudeLevel;
use App\Services\Gratitude\PointExpiryService;
use App\Services\Gratitude\TierService;
use App\Services\Gratitude\GratitudeBenefitsService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GratitudeService
{
    public function __construct(protected PointExpiryService $pointExpiryService)
    {
    }

    public function import(array $data, array $journeysMap = []): void
    {
        foreach ($data as $record) {
            $gratitude = Gratitude::updateOrCreate(
                ['old_id' => $record['id']],
                [
                    'gratitudeNumber' => $record['gratitudeNumber'] ?? null,
                    'totalPoints'     => $record['totalPoints'] ?? 0,
                    'useablePoints'   => $record['useablePoints'] ?? 0,
                    'level'           => $record['level'] ?? 'Explorer',
                    'status'          => $record['status'] ?? null,
                    'statusChange'    => $record['statusChange'] ?? null,
                    'importStatus'    => $record['importStatus'] ?? 1,
                    'expires_at'      => !empty($record['expires_at']) ? Carbon::parse($record['expires_at']) : null,
                    'created_at'      => !empty($record['created_at']) ? Carbon::parse($record['created_at']) : null,
                    'updated_at'      => !empty($record['updated_at']) ? Carbon::parse($record['updated_at']) : null,
                ]
            );

            $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);

            // Cancellation Points (processed before earned/bonus so cancel_id can be resolved)
            if (isset($record['cancellationPoints']) && is_array($record['cancellationPoints'])) {
                foreach ($record['cancellationPoints'] as $cp) {
                    $fallback_date = $this->parseFallbackDate($cp);

                    Cancellation::updateOrCreate(
                        ['old_id' => $cp['id']],
                        [
                            'user_id'          => $cp['user_id'] ?? null,
                            'points'           => $cp['points'] ?? 0,
                            'reason'           => $cp['reason'] ?? null,
                            'amount'           => $cp['amount'] ?? 0,
                            'category'         => $cp['category'] ?? null,
                            'description'      => $cp['description'] ?? null,
                            'date'             => $fallback_date,
                            'gratitudeNumber'  => $cp['gratitudeNumber'] ?? null,
                            'points_breakdown' => $cp['points_breakdown'] ?? null,
                            'status'           => $cp['status'] ?? null,
                            'created_at'       => !empty($cp['created_at']) ? Carbon::parse($cp['created_at']) : null,
                            'updated_at'       => !empty($cp['updated_at']) ? Carbon::parse($cp['updated_at']) : null,
                        ]
                    );
                }
            }

            // Earned Points
            if (isset($record['earnedPoints']) && is_array($record['earnedPoints'])) {
                foreach ($record['earnedPoints'] as $ep) {
                    $cancel_id    = $this->resolveCancelId($ep['cancel_id'] ?? null);
                    $fallback_date = $this->parseFallbackDate($ep);

                    $usable_date   = null;
                    $journeyToSave = null;
                    if (!empty($ep['journey_id']) && isset($journeysMap[$ep['journey_id']])) {
                        $journey       = $journeysMap[$ep['journey_id']];
                        $journeyToSave = $journey;
                        if (!empty($journey['endDate'])) {
                            $parsedDate = Carbon::parse($journey['endDate']);
                            if ($parsedDate->year > 1970) {
                                $usable_date = $parsedDate;
                            }
                        }
                    }

                    if (!$usable_date && $fallback_date) {
                        $usable_date = $fallback_date->copy();
                    }

                    EarnedPoint::updateOrCreate(
                        ['old_id' => $ep['id']],
                        [
                            'user_id'            => $ep['user_id'] ?? null,
                            'journey_id'         => $ep['journey_id'] ?? null,
                            'cancel_id'          => $cancel_id,
                            'gratitudeNumber'    => $ep['gratitudeNumber'] ?? null,
                            'points'             => $ep['points'] ?? 0,
                            'redeemed_points'    => $ep['redeemed_points'] ?? 0,
                            'redemption_history' => $ep['redemption_history'] ?? null,
                            'amount'             => $ep['amount'] ?? null,
                            'date'               => $fallback_date,
                            'description'        => $ep['description'] ?? null,
                            'category'           => $ep['category'] ?? null,
                            'status'             => $this->normalizeImportedEarnedStatus($ep['status'] ?? null, $usable_date),
                            'usable_date'        => $usable_date,
                            'expires_at'         => $this->pointExpiryService->calculateEarnedExpiry($usable_date, $level),
                            'project_data'       => $journeyToSave,
                            'created_at'         => !empty($ep['created_at']) ? Carbon::parse($ep['created_at']) : null,
                            'updated_at'         => !empty($ep['updated_at']) ? Carbon::parse($ep['updated_at']) : null,
                        ]
                    );
                }
            }

            // Bonus Points
            if (isset($record['bonusPoints']) && is_array($record['bonusPoints'])) {
                foreach ($record['bonusPoints'] as $bp) {
                    $cancel_id     = $this->resolveCancelId($bp['cancel_id'] ?? null);
                    $fallback_date = $this->parseFallbackDate($bp);
                    $usable_date   = $fallback_date ? $fallback_date->copy() : null;

                    BonusPoint::updateOrCreate(
                        ['old_id' => $bp['id']],
                        [
                            'user_id'            => $bp['user_id'] ?? null,
                            'journey_id'         => $bp['journey_id'] ?? null,
                            'cancel_id'          => $cancel_id,
                            'gratitudeNumber'    => $bp['gratitudeNumber'] ?? null,
                            'points'             => $bp['points'] ?? 0,
                            'redeemed_points'    => $bp['redeemed_points'] ?? 0,
                            'redemption_history' => $bp['redemption_history'] ?? null,
                            'amount'             => $bp['amount'] ?? null,
                            'date'               => $fallback_date,
                            'description'        => $bp['description'] ?? null,
                            'category'           => $bp['category'] ?? null,
                            'type'               => $bp['type'] ?? null,
                            'status'             => $this->normalizeImportedBonusStatus($bp['status'] ?? null),
                            'expires_at'         => $this->pointExpiryService->calculateBonusExpiry($usable_date, $level),
                            'created_at'         => !empty($bp['created_at']) ? Carbon::parse($bp['created_at']) : null,
                            'updated_at'         => !empty($bp['updated_at']) ? Carbon::parse($bp['updated_at']) : null,
                        ]
                    );
                }
            }

            // Redeem Points
            if (isset($record['redeemPoints']) && is_array($record['redeemPoints'])) {
                foreach ($record['redeemPoints'] as $rp) {
                    $cancel_id = $this->resolveCancelId($rp['cancel_id'] ?? null);

                    RedeemPoints::updateOrCreate(
                        ['old_id' => $rp['id']],
                        [
                            'user_id'         => $rp['user_id'] ?? null,
                            'journey_id'      => $rp['journey_id'] ?? null,
                            'cancel_id'       => $cancel_id,
                            'gratitudeNumber' => $rp['gratitudeNumber'] ?? null,
                            'points'          => $rp['points'] ?? 0,
                            'amount'          => $rp['amount'] ?? 0,
                            'roomStatus'      => $rp['roomStatus'] ?? null,
                            'reason'          => $rp['description'] ?? 'Imported Redemption',
                            'status'          => $rp['status'] ?? null,
                            'created_at'      => !empty($rp['created_at']) ? Carbon::parse($rp['created_at']) : null,
                            'updated_at'      => !empty($rp['updated_at']) ? Carbon::parse($rp['updated_at']) : null,
                        ]
                    );
                }
            }

            if ($gratitude->gratitudeNumber) {
                self::syncAccountBalance($gratitude->gratitudeNumber);
            }
        }
    }

    private function parseFallbackDate(array $row): ?Carbon
    {
        foreach (['date', 'created_at'] as $field) {
            if (!empty($row[$field])) {
                $parsed = Carbon::parse($row[$field]);
                if ($parsed->year > 1970) {
                    return $parsed;
                }
            }
        }
        return null;
    }

    private function resolveCancelId(mixed $oldId): ?int
    {
        if (!$oldId) {
            return null;
        }
        $cancel = Cancellation::where('old_id', $oldId)->first();
        return $cancel?->id;
    }

    protected function normalizeImportedEarnedStatus(mixed $status, ?Carbon $usableDate): string
    {
        if (in_array($status, ['expired', false, 0, '0'], true)) {
            return 'expired';
        }

        if ($usableDate && $usableDate->isFuture()) {
            return 'pending';
        }

        return 'active';
    }

    protected function normalizeImportedBonusStatus(mixed $status): bool
    {
        return !in_array($status, ['expired', false, 0, '0'], true);
    }

    /**
     * Redeem points from a gratitude account using FIFO expiry logic.
     * Auto-calculates monetary value from the level's redemption_points_per_dollar rate.
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

                // Benefit gate: verify the member's level allows this redemption type
                $redemptionType = $data['redemption_type'] ?? null;
                if ($redemptionType && !(new GratitudeBenefitsService())->levelHasBenefit($getGratitude->level, $redemptionType)) {
                    return ['error' => "Your {$getGratitude->level} membership does not include the '{$redemptionType}' benefit."];
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
                    'user_id' => $data['user_id'] ?? null,
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
                        'user_id' => $data['user_id'] ?? null,
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
        } catch (\Exception) {
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

        // Earned totals (uncancelled)
        $totalEarned = (int) EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->sum('points');

        // Bonus totals (uncancelled)
        $totalBonus = (int) BonusPoint::where('gratitudeNumber', $gratitudeNumber)
            ->sum('points');

        // Cancelled points (from cancellations table)
        $totalCancelled = (int) Cancellation::where('gratitudeNumber', $gratitudeNumber)
            ->sum('points');

        // Redeemed points (all approved redemptions)
        $totalRedeemed = (int) RedeemPoints::where('gratitudeNumber', $gratitudeNumber)
            ->sum('points');

        // Expired: uncancelled points whose expires_at has passed
        $earnedExpired = (int) EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNull('cancel_id')
            ->where('expires_at', '<=', $now)
            ->sum(DB::raw('points - redeemed_points'));

        $bonusExpired = (int) BonusPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNull('cancel_id')
            ->where('expires_at', '<=', $now)
            ->sum(DB::raw('points - redeemed_points'));

        $totalExpired = max(0, $earnedExpired + $bonusExpired);

        // Total lifetime points: earned + bonus 
        $totalPoints = $totalEarned + $totalBonus;

        // Useable: uncancelled, active status, unexpired, usable_date passed
        $earnedUseable = (int) EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNull('cancel_id')
            ->activeStatus()
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('usable_date')->orWhere('usable_date', '<=', $now);
            })
            ->sum(DB::raw('points - redeemed_points'));

        $bonusUseable = (int) BonusPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNull('cancel_id')
            ->activeStatus()
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('usable_date')->orWhere('usable_date', '<=', $now);
            })
            ->sum(DB::raw('points - redeemed_points'));

        $useablePoints   = max(0, $earnedUseable + $bonusUseable);
        $remainingPoints = max(0, $totalPoints - $totalRedeemed - $totalCancelled - $totalExpired);
        $nonUseablePoints = max(0, $totalPoints - $useablePoints);

        $gratitude->totalPoints          = max(0, $totalPoints);
        $gratitude->totalEarnedPoints    = max(0, $totalEarned);
        $gratitude->totalBonusPoints     = max(0, $totalBonus);
        $gratitude->totalExpiredPoints   = $totalExpired;
        $gratitude->totalCancelledPoints = max(0, $totalCancelled);
        $gratitude->totalRedeemedPoints  = max(0, $totalRedeemed);
        $gratitude->totalRemainingPoints = $remainingPoints;
        $gratitude->useablePoints        = $useablePoints;
        $gratitude->nonUseablePoints     = $nonUseablePoints;
        $gratitude->last_activity_at     = Carbon::now();
        $gratitude->save();

        // Recalculate tier from earned points — skipped when systemLevelUpdate = false (manual override)
        if ($gratitude->gratitudeNumber && $gratitude->systemLevelUpdate) {
            (new TierService())->recalculateTier($gratitude->gratitudeNumber);
        }

        return $gratitude->fresh();
    }

    public function gratitudeDataByNumber(string $gratitudeNumber): ?array
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->first();

        if (!$gratitude) {
            return null;
        }

        $level = GratitudeLevel::where('name', $gratitude->level)->first();

        $earnedPoints  = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->with(['cancellation', 'redemptions'])
            ->get();

        $bonusPoints   = BonusPoint::where('gratitudeNumber', $gratitudeNumber)
            ->with(['cancellation', 'redemptions'])
            ->get();

        $cancellations = Cancellation::where('gratitudeNumber', $gratitudeNumber)->get();

        $redemptions   = RedeemPoints::where('gratitudeNumber', $gratitudeNumber)
            ->with('details')
            ->get();

        return [
            'gratitude'          => $gratitude,
            'level_info'         => $level,
            'earned_points'      => $earnedPoints,
            'bonus_points'       => $bonusPoints,
            'cancellations'      => $cancellations,
            'redemptions'        => $redemptions,
        ];
    }
}

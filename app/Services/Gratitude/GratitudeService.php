<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Cancellation;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\GratitudeLevel;
use App\Models\Gratitude\RedeemPoints;
use App\Models\Gratitude\RedeemPointsDetails;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GratitudeService
{
    public function __construct(protected PointExpiryService $pointExpiryService) {}

    public function import(array $data, array $journeysMap = []): void
    {
        foreach ($data as $record) {
            $gratitude = Gratitude::updateOrCreate(
                ['old_id' => $record['id']],
                [
                    'gratitudeNumber' => $record['gratitudeNumber'] ?? null,
                    'totalPoints' => $record['totalPoints'] ?? 0,
                    'useablePoints' => $record['useablePoints'] ?? 0,
                    'level' => $record['level'] ?? 'Explorer',
                    'status' => $record['status'] ?? null,
                    'statusChange' => $record['statusChange'] ?? null,
                    'importStatus' => $record['importStatus'] ?? 1,
                    'expires_at' => ! empty($record['expires_at']) ? Carbon::parse($record['expires_at']) : null,
                    'created_at' => ! empty($record['created_at']) ? Carbon::parse($record['created_at']) : null,
                    'updated_at' => ! empty($record['updated_at']) ? Carbon::parse($record['updated_at']) : null,
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
                            'user_id' => $cp['user_id'] ?? null,
                            'points' => $cp['points'] ?? 0,
                            'reason' => $cp['reason'] ?? null,
                            'amount' => $cp['amount'] ?? 0,
                            'category' => $cp['category'] ?? null,
                            'description' => $cp['description'] ?? null,
                            'date' => $fallback_date,
                            'gratitudeNumber' => $cp['gratitudeNumber'] ?? null,
                            'points_breakdown' => $cp['points_breakdown'] ?? null,
                            'status' => $cp['status'] ?? null,
                            'created_at' => ! empty($cp['created_at']) ? Carbon::parse($cp['created_at']) : null,
                            'updated_at' => ! empty($cp['updated_at']) ? Carbon::parse($cp['updated_at']) : null,
                        ]
                    );
                }
            }

            // Earned Points
            if (isset($record['earnedPoints']) && is_array($record['earnedPoints'])) {
                foreach ($record['earnedPoints'] as $ep) {
                    $cancel_id = $this->resolveCancelId($ep['cancel_id'] ?? null);
                    $fallback_date = $this->parseFallbackDate($ep);

                    if ((int) ($ep['points'] ?? 0) < 0) {
                        $this->importNegativePointAdjustment($ep, $gratitude->gratitudeNumber, 'earned');

                        continue;
                    }

                    $usable_date = null;
                    $journeyToSave = null;
                    if (! empty($ep['journey_id']) && isset($journeysMap[$ep['journey_id']])) {
                        $journey = $journeysMap[$ep['journey_id']];
                        $journeyToSave = $journey;
                        if (! empty($journey['endDate'])) {
                            $parsedDate = Carbon::parse($journey['endDate']);
                            if ($parsedDate->year > 1970) {
                                $usable_date = $parsedDate;
                            }
                        }
                    }

                    if (! $usable_date && $fallback_date) {
                        $usable_date = $fallback_date->copy();
                    }

                    EarnedPoint::updateOrCreate(
                        ['old_id' => $ep['id']],
                        [
                            'user_id' => $ep['user_id'] ?? null,
                            'journey_id' => $ep['journey_id'] ?? null,
                            'cancel_id' => $cancel_id,
                            'gratitudeNumber' => $ep['gratitudeNumber'] ?? null,
                            'points' => $ep['points'] ?? 0,
                            'redeemed_points' => $ep['redeemed_points'] ?? 0,
                            'cancelled_points' => $ep['cancelled_points'] ?? ($cancel_id ? max(0, (int) ($ep['points'] ?? 0) - (int) ($ep['redeemed_points'] ?? 0)) : 0),
                            'redemption_history' => $ep['redemption_history'] ?? null,
                            'amount' => $ep['amount'] ?? null,
                            'date' => $fallback_date,
                            'description' => $ep['description'] ?? null,
                            'category' => $ep['category'] ?? null,
                            'status' => $this->normalizeImportedEarnedStatus($ep['status'] ?? null, $usable_date),
                            'usable_date' => $usable_date,
                            'expires_at' => $this->pointExpiryService->calculateEarnedExpiry($usable_date, $level),
                            'project_data' => $journeyToSave,
                            'created_at' => ! empty($ep['created_at']) ? Carbon::parse($ep['created_at']) : null,
                            'updated_at' => ! empty($ep['updated_at']) ? Carbon::parse($ep['updated_at']) : null,
                        ]
                    );
                }
            }

            // Bonus Points
            if (isset($record['bonusPoints']) && is_array($record['bonusPoints'])) {
                foreach ($record['bonusPoints'] as $bp) {
                    $cancel_id = $this->resolveCancelId($bp['cancel_id'] ?? null);
                    $fallback_date = $this->parseFallbackDate($bp);

                    if ((int) ($bp['points'] ?? 0) < 0) {
                        $this->importNegativePointAdjustment($bp, $gratitude->gratitudeNumber, 'bonus');

                        continue;
                    }

                    $usable_date = $fallback_date ? $fallback_date->copy() : null;

                    BonusPoint::updateOrCreate(
                        ['old_id' => $bp['id']],
                        [
                            'user_id' => $bp['user_id'] ?? null,
                            'journey_id' => $bp['journey_id'] ?? null,
                            'cancel_id' => $cancel_id,
                            'gratitudeNumber' => $bp['gratitudeNumber'] ?? null,
                            'points' => $bp['points'] ?? 0,
                            'redeemed_points' => $bp['redeemed_points'] ?? 0,
                            'cancelled_points' => $bp['cancelled_points'] ?? ($cancel_id ? max(0, (int) ($bp['points'] ?? 0) - (int) ($bp['redeemed_points'] ?? 0)) : 0),
                            'redemption_history' => $bp['redemption_history'] ?? null,
                            'amount' => $bp['amount'] ?? null,
                            'date' => $fallback_date,
                            'description' => $bp['description'] ?? null,
                            'category' => $bp['category'] ?? null,
                            'type' => $bp['type'] ?? null,
                            'status' => $this->normalizeImportedBonusStatus($bp['status'] ?? null),
                            'expires_at' => $this->pointExpiryService->calculateBonusExpiry($usable_date, $level),
                            'created_at' => ! empty($bp['created_at']) ? Carbon::parse($bp['created_at']) : null,
                            'updated_at' => ! empty($bp['updated_at']) ? Carbon::parse($bp['updated_at']) : null,
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
                            'user_id' => $rp['user_id'] ?? null,
                            'journey_id' => $rp['journey_id'] ?? null,
                            'cancel_id' => $cancel_id,
                            'gratitudeNumber' => $rp['gratitudeNumber'] ?? null,
                            'points' => $rp['points'] ?? 0,
                            'amount' => $rp['amount'] ?? 0,
                            'roomStatus' => $rp['roomStatus'] ?? null,
                            'reason' => $rp['description'] ?? 'Imported Redemption',
                            'status' => $rp['status'] ?? null,
                            'created_at' => ! empty($rp['created_at']) ? Carbon::parse($rp['created_at']) : null,
                            'updated_at' => ! empty($rp['updated_at']) ? Carbon::parse($rp['updated_at']) : null,
                        ]
                    );
                }
            }

            if ($gratitude->gratitudeNumber) {
                self::syncAccountBalance($gratitude->gratitudeNumber);
            }
        }
    }

    public function allGratitudes(): Collection
    {
        return Gratitude::all();
    }

    private function parseFallbackDate(array $row): ?Carbon
    {
        foreach (['date', 'created_at'] as $field) {
            if (! empty($row[$field])) {
                $parsed = Carbon::parse($row[$field]);
                if ($parsed->year > 1970) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    private function importNegativePointAdjustment(array $row, ?string $gratitudeNumber, string $source): void
    {
        $points = abs((int) ($row['points'] ?? 0));
        if ($points === 0 || $this->isImportedExpirationAdjustment($row)) {
            return;
        }

        $amount = $row['amount'] ?? null;
        if (is_numeric($amount)) {
            $amount = abs((float) $amount);
        }

        $values = [
            'user_id' => $row['user_id'] ?? null,
            'journey_id' => $row['journey_id'] ?? null,
            'points' => $points,
            'amount' => $amount,
            'category' => $row['category'] ?? "{$source}_adjustment",
            'description' => $row['description'] ?? "Imported {$source} point adjustment",
            'date' => $this->parseFallbackDate($row),
            'gratitudeNumber' => $row['gratitudeNumber'] ?? $gratitudeNumber,
            'points_breakdown' => [
                'import_source' => "{$source}_points",
                'imported_from_old_id' => $row['id'] ?? null,
                'original_points' => $row['points'] ?? null,
                'original_points_breakdown' => $row['points_breakdown'] ?? null,
            ],
            'status' => 'imported_adjustment',
            'created_at' => ! empty($row['created_at']) ? Carbon::parse($row['created_at']) : null,
            'updated_at' => ! empty($row['updated_at']) ? Carbon::parse($row['updated_at']) : null,
        ];

        $oldId = $this->negativeAdjustmentOldId($row['id'] ?? null, $source);

        if ($oldId === null) {
            Cancellation::create($values);

            return;
        }

        Cancellation::updateOrCreate(['old_id' => $oldId], $values);
    }

    private function isImportedExpirationAdjustment(array $row): bool
    {
        $text = strtolower(implode(' ', array_filter([
            $row['description'] ?? null,
            $row['category'] ?? null,
            $row['type'] ?? null,
        ], fn ($value) => is_scalar($value) && $value !== '')));

        return str_contains($text, 'expir');
    }

    private function negativeAdjustmentOldId(mixed $oldId, string $source): ?int
    {
        if ($oldId === null || $oldId === '') {
            return null;
        }

        $offset = $source === 'bonus' ? 2_000_000_000 : 1_000_000_000;

        return -1 * ($offset + abs((int) $oldId));
    }

    private function resolveCancelId(mixed $oldId): ?int
    {
        if (! $oldId) {
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
        return ! in_array($status, ['expired', false, 0, '0'], true);
    }

    /**
     * Redeem points from a gratitude account using soonest-expiring FIFO logic.
     * Journey redemptions use the level redemption rate; partner redemptions use the partner rate.
     */
    public function redeemPoints($gratitude_number, $data, $points)
    {
        try {
            return DB::transaction(function () use ($gratitude_number, $data, $points) {
                $getGratitude = Gratitude::where('gratitudeNumber', $gratitude_number)
                    ->lockForUpdate()
                    ->first();

                if (! $getGratitude) {
                    return false;
                }

                $redemptionType = $data['redemption_type'] ?? $data['category'] ?? 'partner';

                if (! empty($data['benefit_key']) && ! (new GratitudeBenefitsService)->levelHasBenefit($getGratitude->level, $data['benefit_key'])) {
                    return ['error' => "Your {$getGratitude->level} membership does not include the '{$data['benefit_key']}' benefit."];
                }

                $level = GratitudeLevel::where('name', $getGratitude->level)->first();
                $pointsPerDollar = $this->pointsPerDollarForRedemption($level, $redemptionType);
                $monetaryValue = round($points / $pointsPerDollar, 2);
                $now = Carbon::now();

                $allPoints = $this->buildRedemptionQueue($gratitude_number, $now);

                $availableSum = $allPoints->sum(function ($segment) {
                    return (float) $segment->available_points;
                });

                if ($availableSum < $points) {
                    return false;
                }

                $redemption = RedeemPoints::create([
                    'user_id' => $data['user_id'] ?? null,
                    'gratitudeNumber' => $gratitude_number,
                    'points' => $points,
                    'amount' => $data['amount'] ?? $monetaryValue,
                    'reason' => $data['reason'] ?? 'Point Redemption',
                    'category' => $redemptionType,
                    'journey_id' => $data['journey_id'] ?? null,
                    'points_breakdown' => [
                        'redemption_type' => $redemptionType,
                        'level_at_redemption' => $getGratitude->level,
                        'points_per_dollar' => $pointsPerDollar,
                        'calculated_amount' => $monetaryValue,
                    ],
                    'status' => 'approved',
                ]);

                $pointsRemaining = $points;

                foreach ($allPoints as $segment) {

                    if ($pointsRemaining <= 0) {
                        break;
                    }

                    $available = (float) $segment->available_points;
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
                        'redemption_type' => $redemptionType,
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

                    RedeemPointsDetails::create([
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
                ->whereRaw('COALESCE(points, 0) > COALESCE(redeemed_points, 0) + COALESCE(cancelled_points, 0)')
                ->lockForUpdate();
        };

        $earnedPoints = $applyFilters(EarnedPoint::query())->get();
        $bonusPoints = $applyFilters(BonusPoint::query())->get();

        $result = $earnedPoints
            ->concat($bonusPoints)
            ->map(function ($point) {
                $point->available_points = max(
                    0,
                    (int) $point->points - (int) $point->redeemed_points - (int) $point->cancelled_points
                );
                $point->type = $point instanceof BonusPoint ? 'bonus' : 'earned';

                return $point;
            })
            ->filter(fn ($point) => $point->available_points > 0)
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

    protected function pointsPerDollarForRedemption(?GratitudeLevel $level, ?string $redemptionType): float
    {
        $type = strtolower((string) ($redemptionType ?: 'journey'));

        $rate = $type === 'partner'
            ? ($level?->partner_points_per_dollar ?: $level?->redemption_points_per_dollar)
            : $level?->redemption_points_per_dollar;

        return max(1, (float) ($rate ?: 35));
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
        $redemption = RedeemPoints::findOrFail($id);
        $redemption->update([
            'reason' => $data['reason'] ?? $redemption->reason,
            'amount' => $data['amount'] ?? $redemption->amount,
        ]);

        return $redemption;
    }

    public static function deleteRedemption($id)
    {
        $redemption = RedeemPoints::with('details')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Restore points to original sources and remove history entry
            foreach ($redemption->details as $detail) {
                $source = $detail->source; // EarnedPoint or BonusPoint
                if ($source) {
                    $source->redeemed_points = max(0, $source->redeemed_points - $detail->points);
                    if (((int) $source->points - (int) $source->redeemed_points - (int) $source->cancelled_points) > 0) {
                        $source->cancel_id = null;
                    }

                    // Strip this redemption's history entry from the segment
                    $history = $source->redemption_history ?? [];
                    $source->redemption_history = array_values(
                        array_filter($history, fn ($entry) => ($entry['redemption_id'] ?? null) != $id)
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
        if (! $gratitude) {
            return;
        }

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

        $remainingExpression = 'CASE WHEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) - COALESCE(cancelled_points, 0) > 0 THEN COALESCE(points, 0) - COALESCE(redeemed_points, 0) - COALESCE(cancelled_points, 0) ELSE 0 END';

        // Expired: only the remaining part of each point batch can expire.
        $earnedExpired = (int) EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNull('cancel_id')
            ->where('expires_at', '<=', $now)
            ->sum(DB::raw($remainingExpression));

        $bonusExpired = (int) BonusPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNull('cancel_id')
            ->where('expires_at', '<=', $now)
            ->sum(DB::raw($remainingExpression));

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
            ->sum(DB::raw($remainingExpression));

        $bonusUseable = (int) BonusPoint::where('gratitudeNumber', $gratitudeNumber)
            ->whereNull('cancel_id')
            ->activeStatus()
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('usable_date')->orWhere('usable_date', '<=', $now);
            })
            ->sum(DB::raw($remainingExpression));

        $useablePoints = max(0, $earnedUseable + $bonusUseable);
        $remainingPoints = max(0, $totalPoints - $totalRedeemed - $totalCancelled - $totalExpired);
        $nonUseablePoints = max(0, $totalPoints - $useablePoints);

        $gratitude->totalPoints = max(0, $totalPoints);
        $gratitude->totalEarnedPoints = max(0, $totalEarned);
        $gratitude->totalBonusPoints = max(0, $totalBonus);
        $gratitude->totalExpiredPoints = $totalExpired;
        $gratitude->totalCancelledPoints = max(0, $totalCancelled);
        $gratitude->totalRedeemedPoints = max(0, $totalRedeemed);
        $gratitude->totalRemainingPoints = $remainingPoints;
        $gratitude->useablePoints = $useablePoints;
        $gratitude->nonUseablePoints = $nonUseablePoints;
        $gratitude->last_activity_at = Carbon::now();
        $gratitude->save();

        // Recalculate tier from earned points — skipped when systemLevelUpdate = false (manual override)
        if ($gratitude->gratitudeNumber && $gratitude->systemLevelUpdate) {
            (new TierService)->recalculateTier($gratitude->gratitudeNumber);
        }

        return $gratitude->fresh();
    }

    public function gratitudeDataByNumber(string $gratitudeNumber): ?array
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->first();

        if (! $gratitude) {
            return null;
        }

        $level = GratitudeLevel::where('name', $gratitude->level)->first();

        $earnedPoints = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->with(['cancellation', 'redemptions'])
            ->get();

        $bonusPoints = BonusPoint::where('gratitudeNumber', $gratitudeNumber)
            ->with(['cancellation', 'redemptions'])
            ->get();

        $cancellations = Cancellation::where('gratitudeNumber', $gratitudeNumber)->get();

        $redemptions = RedeemPoints::where('gratitudeNumber', $gratitudeNumber)
            ->with('details')
            ->get();

        return [
            'gratitude' => $gratitude,
            'level_info' => $level,
            'earned_points' => $earnedPoints,
            'bonus_points' => $bonusPoints,
            'cancellations' => $cancellations,
            'redemptions' => $redemptions,
        ];
    }
}

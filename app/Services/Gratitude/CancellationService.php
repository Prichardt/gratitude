<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Cancellation;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancellationService
{
    public function cancel(Gratitude $gratitude, array $data, ?int $earnedPointId = null, ?int $bonusPointId = null): Cancellation
    {
        return DB::transaction(function () use ($gratitude, $data, $earnedPointId, $bonusPointId) {
            $pointsToCancel = (int) $data['cancellation_points'];

            $cancel = Cancellation::create([
                'user_id' => $gratitude->user_id,
                'gratitudeNumber' => $gratitude->gratitudeNumber,
                'date' => $data['date'],
                'description' => $data['cancellation_reason'],
                'points' => $pointsToCancel,
                'status' => 'approved',
            ]);

            if ($earnedPointId && $bonusPointId) {
                throw ValidationException::withMessages([
                    'earned_point_id' => 'Cancel one point source at a time.',
                ]);
            }

            if ($earnedPointId || $bonusPointId) {
                $source = $earnedPointId
                    ? EarnedPoint::where('gratitudeNumber', $gratitude->gratitudeNumber)->lockForUpdate()->findOrFail($earnedPointId)
                    : BonusPoint::where('gratitudeNumber', $gratitude->gratitudeNumber)->lockForUpdate()->findOrFail($bonusPointId);

                $available = $this->remainingPoints($source);

                if ($pointsToCancel > $available) {
                    throw ValidationException::withMessages([
                        'cancellation_points' => "Only {$available} points remain available to cancel for this entry.",
                    ]);
                }

                $allocations = [$this->applyCancellationToSource($source, $pointsToCancel, $cancel)];
            } else {
                $allocations = $this->cancelFromQueue($gratitude->gratitudeNumber, $pointsToCancel, $cancel);
            }

            $cancel->update(['points_breakdown' => $allocations]);

            GratitudeService::syncAccountBalance($gratitude->gratitudeNumber);

            return $cancel->fresh();
        });
    }

    public function expire(Gratitude $gratitude, array $data): Cancellation
    {
        return $this->cancel($gratitude, [
            'date' => $data['date'],
            'cancellation_reason' => 'Manual points expiration',
            'cancellation_points' => $data['points'],
        ]);
    }

    public function delete(Cancellation $cancel): void
    {
        $gratitudeNumber = $cancel->gratitudeNumber;

        DB::transaction(function () use ($cancel) {
            $allocations = $cancel->points_breakdown ?? [];

            if ($allocations) {
                foreach ($allocations as $allocation) {
                    $source = $this->findSource($allocation['source_type'] ?? null, $allocation['source_id'] ?? null);
                    if (! $source) {
                        continue;
                    }

                    $source->cancelled_points = max(0, (int) $source->cancelled_points - (int) ($allocation['points'] ?? 0));
                    if ((int) $source->cancel_id === (int) $cancel->id) {
                        $source->cancel_id = null;
                    }
                    $source->save();
                }
            } else {
                EarnedPoint::where('cancel_id', $cancel->id)->update(['cancel_id' => null, 'cancelled_points' => 0]);
                BonusPoint::where('cancel_id', $cancel->id)->update(['cancel_id' => null, 'cancelled_points' => 0]);
            }

            $cancel->delete();
        });

        GratitudeService::syncAccountBalance($gratitudeNumber);
    }

    private function cancelFromQueue(string $gratitudeNumber, int $pointsToCancel, Cancellation $cancel): array
    {
        $remaining = $pointsToCancel;
        $allocations = [];

        foreach ($this->buildCancellationQueue($gratitudeNumber) as $source) {
            if ($remaining <= 0) {
                break;
            }

            $available = $this->remainingPoints($source);
            if ($available <= 0) {
                continue;
            }

            $points = min($available, $remaining);
            $allocations[] = $this->applyCancellationToSource($source, $points, $cancel);
            $remaining -= $points;
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'cancellation_points' => 'Only '.($pointsToCancel - $remaining).' points are available to cancel.',
            ]);
        }

        return $allocations;
    }

    private function buildCancellationQueue(string $gratitudeNumber): Collection
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

        return $applyFilters(EarnedPoint::query())->get()
            ->concat($applyFilters(BonusPoint::query())->get())
            ->sort(function ($left, $right) {
                $leftExpiry = $left->expires_at ? Carbon::parse($left->expires_at)->timestamp : PHP_INT_MAX;
                $rightExpiry = $right->expires_at ? Carbon::parse($right->expires_at)->timestamp : PHP_INT_MAX;
                if ($leftExpiry !== $rightExpiry) {
                    return $leftExpiry <=> $rightExpiry;
                }

                $leftDate = Carbon::parse($left->usable_date ?? $left->date ?? $left->created_at)->timestamp;
                $rightDate = Carbon::parse($right->usable_date ?? $right->date ?? $right->created_at)->timestamp;
                if ($leftDate !== $rightDate) {
                    return $leftDate <=> $rightDate;
                }

                $leftType = $left instanceof BonusPoint ? 2 : 1;
                $rightType = $right instanceof BonusPoint ? 2 : 1;

                return $leftType === $rightType
                    ? ((int) $left->id) <=> ((int) $right->id)
                    : $leftType <=> $rightType;
            })
            ->values();
    }

    private function applyCancellationToSource(Model $source, int $points, Cancellation $cancel): array
    {
        $source->cancelled_points = (int) $source->cancelled_points + $points;

        if ($this->remainingPoints($source) <= 0) {
            $source->cancel_id = $cancel->id;
        }

        $source->save();

        return [
            'source_type' => get_class($source),
            'source_id' => $source->id,
            'points' => $points,
            'remaining_after' => $this->remainingPoints($source),
        ];
    }

    private function remainingPoints(Model $source): int
    {
        return max(
            0,
            (int) $source->points - (int) $source->redeemed_points - (int) $source->cancelled_points
        );
    }

    private function findSource(?string $sourceType, mixed $sourceId): ?Model
    {
        if (! $sourceType || ! $sourceId || ! in_array($sourceType, [EarnedPoint::class, BonusPoint::class], true)) {
            return null;
        }

        return $sourceType::query()->find($sourceId);
    }
}

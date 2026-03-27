<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use Carbon\Carbon;

class EarnedPointService
{
    public function __construct(protected PointExpiryService $pointExpiryService) {}

    public function add(Gratitude $gratitude, array $data): EarnedPoint
    {
        $usableDate = Carbon::parse($data['date']);
        $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);

        $point = EarnedPoint::create([
            'user_id'       => $gratitude->user_id,
            'gratitudeNumber' => $gratitude->gratitudeNumber,
            'date'          => $usableDate,
            'category'      => $data['category'],
            'points'        => $data['points'],
            'amount'        => $data['amount'],
            'description'   => $data['description'],
            'journey_id'    => $data['journey_id'] ?? null,
            'status'        => 'active',
            'usable_date'   => $usableDate,
            'expires_at'    => $this->pointExpiryService->calculateEarnedExpiry($usableDate, $level),
        ]);

        GratitudeService::syncAccountBalance($gratitude->gratitudeNumber);

        return $point;
    }

    public function update(EarnedPoint $point, Gratitude $gratitude, array $data): EarnedPoint
    {
        $usableDate = Carbon::parse($data['date']);
        $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);

        // Determine expiry: if manually provided → use it.
        // If already manually set and not provided → keep existing.
        // Otherwise → recalculate from level.
        if (!empty($data['expires_at'])) {
            $expiresAt = Carbon::parse($data['expires_at']);
            $isManual  = true;
        } elseif ($point->expires_at_manual) {
            $expiresAt = $point->expires_at;
            $isManual  = true;
        } else {
            $expiresAt = $this->pointExpiryService->calculateEarnedExpiry($usableDate, $level);
            $isManual  = false;
        }

        $point->update([
            'date'           => $usableDate,
            'category'       => $data['category'],
            'points'         => $data['points'],
            'amount'         => $data['amount'],
            'description'    => $data['description'],
            'usable_date'    => $usableDate,
            'expires_at'     => $expiresAt,
            'expires_at_manual' => $isManual,
            'status'         => $point->status === 'pending' && $usableDate->isFuture() ? 'pending' : 'active',
        ]);

        GratitudeService::syncAccountBalance($gratitude->gratitudeNumber);

        return $point->fresh();
    }

    public function delete(EarnedPoint $point): void
    {
        $gratitudeNumber = $point->gratitudeNumber;

        if ($point->cancel_id) {
            \App\Models\Gratitude\Cancellation::where('id', $point->cancel_id)->delete();
        }

        $point->delete();

        GratitudeService::syncAccountBalance($gratitudeNumber);
    }
}

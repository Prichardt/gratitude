<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Gratitude;
use Carbon\Carbon;

class BonusPointService
{
    public function __construct(protected PointExpiryService $pointExpiryService) {}

    public function add(Gratitude $gratitude, array $data): BonusPoint
    {
        $effectiveDate = Carbon::parse($data['date']);
        $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);

        $point = BonusPoint::create([
            'user_id'        => $gratitude->user_id,
            'gratitudeNumber' => $gratitude->gratitudeNumber,
            'date'           => $effectiveDate,
            'description'    => $data['description'],
            'points'         => $data['points'],
            'expires_at'     => $this->pointExpiryService->calculateBonusExpiry($effectiveDate, $level),
            'status'         => true,
        ]);

        GratitudeService::syncAccountBalance($gratitude->gratitudeNumber);

        return $point;
    }

    public function update(BonusPoint $point, Gratitude $gratitude, array $data): BonusPoint
    {
        $effectiveDate = Carbon::parse($data['date']);
        $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);

        if (!empty($data['expires_at'])) {
            $expiresAt = Carbon::parse($data['expires_at']);
            $isManual  = true;
        } elseif ($point->expires_at_manual) {
            $expiresAt = $point->expires_at;
            $isManual  = true;
        } else {
            $expiresAt = $this->pointExpiryService->calculateBonusExpiry($effectiveDate, $level);
            $isManual  = false;
        }

        $point->update([
            'date'           => $effectiveDate,
            'description'    => $data['description'],
            'points'         => $data['points'],
            'expires_at'     => $expiresAt,
            'expires_at_manual' => $isManual,
            'status'         => true,
        ]);

        GratitudeService::syncAccountBalance($gratitude->gratitudeNumber);

        return $point->fresh();
    }

    public function delete(BonusPoint $point): void
    {
        $gratitudeNumber = $point->gratitudeNumber;

        if ($point->cancel_id) {
            \App\Models\Gratitude\Cancellation::where('id', $point->cancel_id)->delete();
        }

        $point->delete();

        GratitudeService::syncAccountBalance($gratitudeNumber);
    }
}

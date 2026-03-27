<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Cancellation;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use Carbon\Carbon;

class CancellationService
{
    public function cancel(Gratitude $gratitude, array $data, ?int $earnedPointId = null, ?int $bonusPointId = null): Cancellation
    {
        $cancel = Cancellation::create([
            'user_id'              => $gratitude->user_id,
            'gratitudeNumber'      => $gratitude->gratitudeNumber,
            'date'                 => $data['date'],
            'cancellation_reason'  => $data['cancellation_reason'],
            'cancellation_points'  => $data['cancellation_points'],
        ]);

        if ($earnedPointId) {
            EarnedPoint::where('id', $earnedPointId)->update(['cancel_id' => $cancel->id]);
        }

        if ($bonusPointId) {
            BonusPoint::where('id', $bonusPointId)->update(['cancel_id' => $cancel->id]);
        }

        GratitudeService::syncAccountBalance($gratitude->gratitudeNumber);

        return $cancel;
    }

    public function expire(Gratitude $gratitude, array $data): Cancellation
    {
        $cancel = Cancellation::create([
            'user_id'              => $gratitude->user_id,
            'gratitudeNumber'      => $gratitude->gratitudeNumber,
            'date'                 => $data['date'],
            'cancellation_reason'  => 'Points Expiration',
            'cancellation_points'  => $data['points'],
        ]);

        GratitudeService::syncAccountBalance($gratitude->gratitudeNumber);

        return $cancel;
    }

    public function delete(Cancellation $cancel): void
    {
        $gratitudeNumber = $cancel->gratitudeNumber;

        EarnedPoint::where('cancel_id', $cancel->id)->update(['cancel_id' => null]);
        BonusPoint::where('cancel_id', $cancel->id)->update(['cancel_id' => null]);

        $cancel->delete();

        GratitudeService::syncAccountBalance($gratitudeNumber);
    }
}

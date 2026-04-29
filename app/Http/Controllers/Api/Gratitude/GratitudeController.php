<?php

namespace App\Http\Controllers\Api\Gratitude;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gratitude\CancelPointRequest;
use App\Http\Requests\Gratitude\StoreBonusPointRequest;
use App\Http\Requests\Gratitude\StoreEarnedPointRequest;
use App\Http\Requests\Gratitude\StoreRedemptionRequest;
use App\Http\Requests\Gratitude\UpdateBonusPointRequest;
use App\Http\Requests\Gratitude\UpdateEarnedPointRequest;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Cancellation;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use App\Services\Gratitude\BonusPointService;
use App\Services\Gratitude\CancellationService;
use App\Services\Gratitude\EarnedPointService;
use App\Services\Gratitude\GratitudeService;
use Illuminate\Http\Request;

class GratitudeController extends Controller
{
    public function __construct(
        protected EarnedPointService $earnedPointService,
        protected BonusPointService $bonusPointService,
        protected CancellationService $cancellationService,
        protected GratitudeService $gratitudeService,
    ) {}

    public function index()
    {
        $gratitudes = $this->gratitudeService->allGratitudes();
        return response()->json($gratitudes);
    }

    public function show(string $gratitudeNumber)
    {
        $data = $this->gratitudeService->gratitudeDataByNumber($gratitudeNumber);

        return response()->json($data);
    }

    // Earned Points
    public function storeEarned(StoreEarnedPointRequest $request, string $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $point = $this->earnedPointService->add($gratitude, $request->validated());
        return response()->json(['message' => 'Points added', 'point' => $point], 201);
    }

    public function updateEarned(UpdateEarnedPointRequest $request, string $gratitudeNumber, int $id)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $point = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $updated = $this->earnedPointService->update($point, $gratitude, $request->validated());
        return response()->json(['message' => 'Points updated', 'point' => $updated]);
    }

    public function destroyEarned(string $gratitudeNumber, int $id)
    {
        $point = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $this->earnedPointService->delete($point);
        return response()->json(['message' => 'Earned point deleted']);
    }

    // Bonus Points
    public function storeBonus(StoreBonusPointRequest $request, string $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $point = $this->bonusPointService->add($gratitude, $request->validated());
        return response()->json(['message' => 'Bonus points added', 'point' => $point], 201);
    }

    public function updateBonus(UpdateBonusPointRequest $request, string $gratitudeNumber, int $id)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $point = BonusPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $updated = $this->bonusPointService->update($point, $gratitude, $request->validated());
        return response()->json(['message' => 'Bonus points updated', 'point' => $updated]);
    }

    public function destroyBonus(string $gratitudeNumber, int $id)
    {
        $point = BonusPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $this->bonusPointService->delete($point);
        return response()->json(['message' => 'Bonus point deleted']);
    }

    // Cancellations
    public function storeCancel(CancelPointRequest $request, string $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $cancel = $this->cancellationService->cancel(
            $gratitude,
            $request->validated(),
            $request->integer('earned_point_id') ?: null,
            $request->integer('bonus_point_id') ?: null,
        );
        return response()->json(['message' => 'Points cancelled', 'cancellation' => $cancel], 201);
    }

    public function destroyCancel(string $gratitudeNumber, int $id)
    {
        $cancel = Cancellation::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $this->cancellationService->delete($cancel);
        return response()->json(['message' => 'Cancellation deleted']);
    }

    // Redemptions
    public function storeRedemption(StoreRedemptionRequest $request, string $gratitudeNumber)
    {
        $result = $this->gratitudeService->redeemPoints($gratitudeNumber, $request->validated(), $request->points);
        if (!$result) {
            return response()->json(['message' => 'Insufficient points or invalid request.'], 422);
        }
        return response()->json(['message' => 'Points redeemed successfully', 'redemption' => $result], 201);
    }

    public function updateRedemption(Request $request, string $gratitudeNumber, int $id)
    {
        $request->validate(['amount' => 'nullable|numeric', 'reason' => 'nullable|string']);
        $redemption = GratitudeService::updateRedemption($id, $request->all());
        GratitudeService::syncAccountBalance($gratitudeNumber);
        return response()->json(['message' => 'Redemption updated', 'redemption' => $redemption]);
    }

    public function destroyRedemption(string $gratitudeNumber, int $id)
    {
        $success = GratitudeService::deleteRedemption($id);
        if (!$success) {
            return response()->json(['message' => 'Failed to delete redemption'], 500);
        }
        return response()->json(['message' => 'Redemption deleted']);
    }
}

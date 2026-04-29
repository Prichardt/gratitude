<?php

namespace App\Http\Controllers\InternalApi\Gratitude;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gratitude\CancelPointRequest;
use App\Http\Requests\Gratitude\StoreBonusPointRequest;
use App\Http\Requests\Gratitude\StoreEarnedPointRequest;
use App\Http\Requests\Gratitude\UpdateBonusPointRequest;
use App\Http\Requests\Gratitude\UpdateEarnedPointRequest;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Cancellation;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\GratitudeBenefit;
use App\Models\Gratitude\GratitudeReserve;
use App\Models\Gratitude\GratitudeLevel;
use App\Models\Gratitude\RedeemPoints;
use App\Services\Gratitude\BonusPointService;
use App\Services\Gratitude\CancellationService;
use App\Services\Gratitude\EarnedPointService;
use App\Services\Gratitude\GratitudeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GratitudeController extends Controller
{
    public function __construct(
        protected GratitudeService $gratitudeService,
        protected EarnedPointService $earnedPointService,
        protected BonusPointService $bonusPointService,
        protected CancellationService $cancellationService,
    ) {}

    private function aivteamHttp(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withoutVerifying()
            ->withToken(config('services.aivteam.access_token'))
            ->timeout(600);
    }

    public function import()
    {
        $getResponse     = $this->aivteamHttp()->get('https://aivteam.com/api/gratitude/get/gratitude-data-all');
        $getJourneysData = $this->aivteamHttp()->get('https://aivteam.com/api/get/all/journeys');

        if (!$getResponse->successful()) {
            return response()->json(['message' => 'Failed to fetch data from remote API Testing', 'status' => $getResponse->status()], 500);
        }

        $data = $getResponse->json();

        if (empty($data) || !is_array($data)) {
            return response()->json(['message' => 'Invalid data format or empty payload'], 400);
        }

        $journeysMap = [];
        if ($getJourneysData->successful()) {
            foreach ($getJourneysData->json() as $j) {
                if (isset($j['id'])) {
                    $journeysMap[$j['id']] = $j;
                }
            }
        }

        DB::beginTransaction();

        try {
            $this->gratitudeService->import($data, $journeysMap);

            DB::commit();
            return response()->json(['message' => 'Data imported successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    public function apiIndex()
    {
        $gratitudes = Gratitude::select(
                'id', 'gratitudeNumber', 'level', 'level_obtained_at',
                'totalPoints', 'useablePoints', 'totalExpiredPoints',
                'status', 'is_active', 'last_activity_at', 'created_at', 'updated_at'
            )
            ->selectSub(
                EarnedPoint::selectRaw('COALESCE(SUM(points), 0)')
                    ->whereColumn('gratitudeNumber', 'gratitudes.gratitudeNumber')
                    ->where('status', 'pending'),
                'pending_points'
            )
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'points' => $gratitudes,
        ]);
    }

    public function apiOverview()
    {
        $totalAccounts = Gratitude::count();
        $totalUsable = Gratitude::sum('useablePoints');
        $totalPending = EarnedPoint::where('status', 'pending')->sum('points');
        $totalReserved = GratitudeReserve::sum('amount');
        $totalUsedMoney = RedeemPoints::sum('amount') > 0 ? RedeemPoints::sum('amount') : RedeemPoints::sum('points'); // Approximating used money as amount or redeemed points

        return response()->json([
            'total_accounts' => $totalAccounts,
            'total_point_balance' => Gratitude::sum('totalPoints'),
            'total_usable_points' => $totalUsable,
            'total_pending_points' => $totalPending,
            'total_reserved' => $totalReserved,
            'total_used_money' => $totalUsedMoney,
        ]);
    }

    public function apiReserve()
    {
        $reserves = GratitudeReserve::orderBy('created_at', 'desc')->get();
        return response()->json([
            'reserves' => $reserves
        ]);
    }

    /**
     * Get the paginated point history (earned, bonus, redemptions).
     */
    public function apiHistory(Request $request)
    {
        $user = $request->user();

        // We can union EarnedPoint and BonusPoint or fetch them separately and merge
        // For simplicity, let's return them as distinct arrays, or the client can specify a type.

        $earned = EarnedPoint::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $bonus = BonusPoint::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'earned_points' => $earned,
            'bonus_points' => $bonus
        ]);
    }

    public function apiShow($gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        // Fetch guest info from aivteam API
        $guestsResponse = $this->aivteamHttp()->get(
            config('services.aivteam.base_url') . '/api/gratitude/get/gratitude-by-number/' . $gratitudeNumber
        );
        $guests = $guestsResponse->successful() ? $guestsResponse->json() : [];

        $level = GratitudeLevel::where('name', $gratitude->level)->first();
        $benefits = [];
        if ($level) {
            $benefits = GratitudeBenefit::whereHas('levels', function ($q) use ($level) {
                $q->where('benefit_gratitude_level.gratitude_level_id', $level->id)
                    ->where('benefit_gratitude_level.is_active', true);
            })->with([
                        'levels' => function ($q) use ($level) {
                            $q->where('benefit_gratitude_level.gratitude_level_id', $level->id);
                        }
                    ])->get()->map(function ($benefit) {
                        // Simplify the output for the frontend
                        $levelPivot = $benefit->levels->first();
                        return [
                            'id' => $benefit->id,
                            'name' => $benefit->name,
                            'benefit_description' => $benefit->description,
                            'value' => $levelPivot ? $levelPivot->pivot->value : null,
                            'level_description' => $levelPivot ? $levelPivot->pivot->description : null,
                        ];
                    });
        }

        $earnedPoints = EarnedPoint::with(['cancellation', 'redemptions.redeemPoint'])->where('gratitudeNumber', $gratitudeNumber)->get();
        $bonusPoints = BonusPoint::with(['cancellation', 'redemptions.redeemPoint'])->where('gratitudeNumber', $gratitudeNumber)->get();
        $cancellations = Cancellation::where('gratitudeNumber', $gratitudeNumber)->get();
        $redemptions = RedeemPoints::with('details')->where('gratitudeNumber', $gratitudeNumber)->get();

        // Rolling 2-year evaluation window (mirrors TierService logic exactly)
        $intervalYears = $level ? (int) ($level->level_interval_years ?? 2) : 2;
        $now           = Carbon::now();

        $intervalStart = $gratitude->level_obtained_at
            ? Carbon::parse($gratitude->level_obtained_at)
            : $now->copy()->subYears($intervalYears);

        $intervalEnd     = $intervalStart->copy()->addYears($intervalYears);
        $intervalExpired = $now->greaterThan($intervalEnd);
        $evalStart       = $intervalExpired ? $now->copy()->subYears($intervalYears) : $intervalStart;

        // Net earned (tier) points usable within the evaluation window
        $rollingTotalActive = (int) EarnedPoint::where('gratitudeNumber', $gratitudeNumber)
            ->activeStatus()
            ->whereNull('cancel_id')
            ->whereNotNull('usable_date')
            ->where('usable_date', '>=', $evalStart)
            ->where('usable_date', '<=', $now)
            ->sum(DB::raw('points - redeemed_points'));

        // Determine next level dynamically from the levels table (ordered lowest → highest).
        $allLevels = GratitudeLevel::where('status', true)->orderBy('min_points')->get();

        $nextLevel = null;
        $pointsToNextLevel = 0;
        $currentLevelMinPoints = $level ? (int) $level->min_points : 0;

        foreach ($allLevels as $candidateLevel) {
            if ($rollingTotalActive < (int) $candidateLevel->min_points) {
                $nextLevel = $candidateLevel->name;
                $pointsToNextLevel = (int) $candidateLevel->min_points - $rollingTotalActive;
                break;
            }
        }

        $data = [
            'gratitude'             => $gratitude,
            'guests'                => $guests,
            'level_info'            => $level,
            'earned_points'         => $earnedPoints,
            'bonus_points'          => $bonusPoints,
            'cancellations'         => $cancellations,
            'redemptions'           => $redemptions,
            'next_level'            => $nextLevel,
            'points_to_next_level'  => $pointsToNextLevel,
            'rolling_tier_points'   => $rollingTotalActive,
            'level_benefits'        => $benefits,
            'points_per_dollar'     => $level ? (float) $level->redemption_points_per_dollar : 35,
            'interval_start'        => $evalStart->toDateString(),
            'interval_end'          => $evalStart->copy()->addYears($intervalYears)->toDateString(),
            'interval_years'        => $intervalYears,
            'current_level_min'     => $currentLevelMinPoints,
        ];

        return response()->json($data);
    }

    public function apiAddEarned(StoreEarnedPointRequest $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $point = $this->earnedPointService->add($gratitude, $request->validated());
        return response()->json(['message' => 'Points added', 'point' => $point]);
    }

    public function apiUpdateEarned(UpdateEarnedPointRequest $request, $gratitudeNumber, $id)
    {
        $point = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $updated = $this->earnedPointService->update($point, $gratitude, $request->validated());
        return response()->json(['message' => 'Points updated', 'point' => $updated]);
    }

    public function apiAddBonus(StoreBonusPointRequest $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $point = $this->bonusPointService->add($gratitude, $request->validated());
        return response()->json(['message' => 'Bonus points added', 'point' => $point]);
    }

    public function apiUpdateBonus(UpdateBonusPointRequest $request, $gratitudeNumber, $id)
    {
        $point = BonusPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $updated = $this->bonusPointService->update($point, $gratitude, $request->validated());
        return response()->json(['message' => 'Bonus points updated', 'point' => $updated]);
    }

    public function apiCancelPoints(CancelPointRequest $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $cancel = $this->cancellationService->cancel(
            $gratitude,
            $request->validated(),
            $request->integer('earned_point_id') ?: null,
            $request->integer('bonus_point_id') ?: null,
        );
        return response()->json(['message' => 'Points cancelled', 'cancellation' => $cancel]);
    }

    public function apiDeleteEarned($gratitudeNumber, $id)
    {
        $point = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $this->earnedPointService->delete($point);
        return response()->json(['message' => 'Earned point deleted']);
    }

    public function apiDeleteBonus($gratitudeNumber, $id)
    {
        $point = BonusPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $this->bonusPointService->delete($point);
        return response()->json(['message' => 'Bonus point deleted']);
    }

    public function apiDeleteCancellation($gratitudeNumber, $id)
    {
        $cancel = Cancellation::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $this->cancellationService->delete($cancel);
        return response()->json(['message' => 'Cancellation deleted']);
    }

    public function apiExpirePoints(Request $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $request->validate(['date' => 'required', 'points' => 'required|numeric']);

        $cancel = $this->cancellationService->expire($gratitude, $request->all());

        return response()->json(['message' => 'Points expired', 'cancellation' => $cancel]);
    }

    public function apiAddRedeem(Request $request, $gratitudeNumber)
    {
        $request->validate([
            'points' => 'required|numeric|min:1',
            'amount' => 'nullable|numeric',
            'reason' => 'nullable|string'
        ]);



        $result = $this->gratitudeService->redeemPoints($gratitudeNumber, $request->all(), $request->points);

        if (!$result) {
            return response()->json(['message' => 'Insufficient points or invalid request.'], 400);
        }

        // syncAccountBalance is already called inside redeemPoints(); no second call needed.
        return response()->json(['message' => 'Points redeemed successfully!', 'redemption' => $result]);
    }

    public function apiShowRedemption($gratitudeNumber, $id)
    {
        $redemption = \App\Models\Gratitude\RedeemPoints::with('details.source')->findOrFail($id);
        return response()->json($redemption);
    }

    public function apiUpdateRedemption(Request $request, $gratitudeNumber, $id)
    {
        $request->validate([
            'amount' => 'nullable|numeric',
            'reason' => 'nullable|string'
        ]);

        $redemption = GratitudeService::updateRedemption($id, $request->all());
        GratitudeService::syncAccountBalance($gratitudeNumber);
        return response()->json(['message' => 'Redemption updated', 'redemption' => $redemption]);
    }

    public function apiDeleteRedemption($gratitudeNumber, $id)
    {
        $success = GratitudeService::deleteRedemption($id);
        if (!$success) {
            return response()->json(['message' => 'Failed to delete redemption'], 500);
        }
        return response()->json(['message' => 'Redemption removed securely']);
    }

    public function apiSyncBalance($gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        GratitudeService::syncAccountBalance($gratitude->gratitudeNumber);
        $gratitude->refresh();
        return response()->json([
            'message'        => 'Balance synced successfully.',
            'useablePoints'  => $gratitude->useablePoints,
            'totalPoints'    => $gratitude->totalPoints,
        ]);
    }

}

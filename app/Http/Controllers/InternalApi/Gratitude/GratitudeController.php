<?php

namespace App\Http\Controllers\InternalApi\Gratitude;

use App\Http\Controllers\Controller;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Cancellation;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\GratitudeBenefit;
use App\Models\Gratitude\GratitudeReserve;
use App\Models\Gratitude\RedeemPoints;
use App\Services\Gratitude\PointExpiryService;
use App\Services\Gratitude\GratitudeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GratitudeController extends Controller
{
    protected $gratitudeService;
    protected $pointExpiryService;

    public function __construct(GratitudeService $gratitudeService, PointExpiryService $pointExpiryService)
    {
        $this->gratitudeService = $gratitudeService;
        $this->pointExpiryService = $pointExpiryService;
    }
    public function import()
    {
        // Added withoutVerifying() to fix the cURL 60 SSL certificate error on local WAMP
        $getResponse = Http::withoutVerifying()->timeout(600)->get('http://aivteam.local/api/get/graitude-data-all/open/gratitude');

        $getJourneysData = Http::withoutVerifying()->timeout(600)->get('http://aivteam.local/api/get/all/journeys');

        if (!$getResponse->successful()) {
            return response()->json(['message' => 'Failed to fetch data from remote API', 'status' => $getResponse->status()], 500);
        }

        // Use Laravel's built in json parser
        $data = $getResponse->json();

        if (empty($data) || !is_array($data)) {
            return response()->json(['message' => 'Invalid data format or empty payload'], 400);
        }

        $journeysData = $getJourneysData->successful() ? $getJourneysData->json() : [];
        $journeysMap = [];
        if (!empty($journeysData) && is_array($journeysData)) {
            foreach ($journeysData as $j) {
                if (isset($j['id'])) {
                    $journeysMap[$j['id']] = $j;
                }
            }
        }

        DB::beginTransaction();

        try {
            foreach ($data as $record) {
                // Map Gratitude Model
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
                        'expires_at' => !empty($record['expires_at']) ? Carbon::parse($record['expires_at']) : null,
                        'created_at' => !empty($record['created_at']) ? Carbon::parse($record['created_at']) : null,
                        'updated_at' => !empty($record['updated_at']) ? Carbon::parse($record['updated_at']) : null,
                    ]
                );

                $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);


                // Cancellation Points
                if (isset($record['cancellationPoints']) && is_array($record['cancellationPoints'])) {
                    foreach ($record['cancellationPoints'] as $cp) {
                        $fallback_date = null;
                        if (!empty($cp['date'])) {
                            $parsedDate = Carbon::parse($cp['date']);
                            if ($parsedDate->year > 1970) {
                                $fallback_date = $parsedDate;
                            }
                        }
                        if (!$fallback_date && !empty($cp['created_at'])) {
                            $parsedDate = Carbon::parse($cp['created_at']);
                            if ($parsedDate->year > 1970) {
                                $fallback_date = $parsedDate;
                            }
                        }

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
                                'created_at' => !empty($cp['created_at']) ? Carbon::parse($cp['created_at']) : null,
                                'updated_at' => !empty($cp['updated_at']) ? Carbon::parse($cp['updated_at']) : null,
                            ]
                        );
                    }
                }

                // Earned Points
                if (isset($record['earnedPoints']) && is_array($record['earnedPoints'])) {
                    foreach ($record['earnedPoints'] as $ep) {

                        $cancel_id = null;
                        $cancel_old_id = $ep['cancel_id'] ?? null;
                        if ($cancel_old_id) {
                            $cancel = Cancellation::where('old_id', $cancel_old_id)->first();
                            if ($cancel) {
                                $cancel_id = $cancel->id;
                            }
                        }

                        $fallback_date = null;
                        if (!empty($ep['date'])) {
                            $parsedDate = Carbon::parse($ep['date']);
                            if ($parsedDate->year > 1970) {
                                $fallback_date = $parsedDate;
                            }
                        }
                        if (!$fallback_date && !empty($ep['created_at'])) {
                            $parsedDate = Carbon::parse($ep['created_at']);
                            if ($parsedDate->year > 1970) {
                                $fallback_date = $parsedDate;
                            }
                        }

                        $usable_date = null;
                        $journeyToSave = null;
                        if (!empty($ep['journey_id']) && isset($journeysMap[$ep['journey_id']])) {
                            $journey = $journeysMap[$ep['journey_id']];
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

                        $expires_at = $this->pointExpiryService->calculateEarnedExpiry($usable_date, $level);

                        EarnedPoint::updateOrCreate(
                            ['old_id' => $ep['id']],
                            [
                                'user_id' => $ep['user_id'] ?? null,
                                'journey_id' => $ep['journey_id'] ?? null,
                                'cancel_id' => $cancel_id,
                                'gratitudeNumber' => $ep['gratitudeNumber'] ?? null,
                                'points' => $ep['points'] ?? 0,
                                'redeemed_points' => $ep['redeemed_points'] ?? 0,
                                'redemption_history' => $ep['redemption_history'] ?? null,
                                'amount' => $ep['amount'] ?? null,
                                'date' => $fallback_date,
                                'description' => $ep['description'] ?? null,
                                'category' => $ep['category'] ?? null,
                                'status' => $this->normalizeImportedEarnedStatus($ep['status'] ?? null, $usable_date),
                                'usable_date' => $usable_date,
                                'expires_at' => $expires_at,
                                'project_data' => $journeyToSave,
                                'created_at' => !empty($ep['created_at']) ? Carbon::parse($ep['created_at']) : null,
                                'updated_at' => !empty($ep['updated_at']) ? Carbon::parse($ep['updated_at']) : null,
                            ]
                        );
                    }
                }

                // Bonus Points
                if (isset($record['bonusPoints']) && is_array($record['bonusPoints'])) {
                    foreach ($record['bonusPoints'] as $bp) {

                        $cancel_id = null;
                        $cancel_old_id = $bp['cancel_id'] ?? null;
                        if ($cancel_old_id) {
                            $cancel = Cancellation::where('old_id', $cancel_old_id)->first();
                            if ($cancel) {
                                $cancel_id = $cancel->id;
                            }
                        }

                        $fallback_date = null;
                        if (!empty($bp['date'])) {
                            $parsedDate = Carbon::parse($bp['date']);
                            if ($parsedDate->year > 1970) {
                                $fallback_date = $parsedDate;
                            }
                        }
                        if (!$fallback_date && !empty($bp['created_at'])) {
                            $parsedDate = Carbon::parse($bp['created_at']);
                            if ($parsedDate->year > 1970) {
                                $fallback_date = $parsedDate;
                            }
                        }

                        $usable_date = $fallback_date ? $fallback_date->copy() : null;

                        $expires_at = $this->pointExpiryService->calculateBonusExpiry($usable_date, $level);

                        BonusPoint::updateOrCreate(
                            ['old_id' => $bp['id']],
                            [
                                'user_id' => $bp['user_id'] ?? null,
                                'journey_id' => $bp['journey_id'] ?? null,
                                'cancel_id' => $cancel_id ?? null,
                                'gratitudeNumber' => $bp['gratitudeNumber'] ?? null,
                                'points' => $bp['points'] ?? 0,
                                'redeemed_points' => $bp['redeemed_points'] ?? 0,
                                'redemption_history' => $bp['redemption_history'] ?? null,
                                'amount' => $bp['amount'] ?? null,
                                'date' => $fallback_date,
                                'description' => $bp['description'] ?? null,
                                'category' => $bp['category'] ?? null,
                                'type' => $bp['type'] ?? null,
                                'status' => $this->normalizeImportedBonusStatus($bp['status'] ?? null),
                                'expires_at' => $expires_at,
                                'created_at' => !empty($bp['created_at']) ? Carbon::parse($bp['created_at']) : null,
                                'updated_at' => !empty($bp['updated_at']) ? Carbon::parse($bp['updated_at']) : null,
                            ]
                        );
                    }
                }

                // Redeem Points (mapping to PointRedemption)
                if (isset($record['redeemPoints']) && is_array($record['redeemPoints'])) {
                    foreach ($record['redeemPoints'] as $rp) {

                        $cancel_id = null;
                        $cancel_old_id = $rp['cancel_id'] ?? null;
                        if ($cancel_old_id) {
                            $cancel = Cancellation::where('old_id', $cancel_old_id)->first();
                            if ($cancel) {
                                $cancel_id = $cancel->id;
                            }
                        }

                        RedeemPoints::updateOrCreate(
                            ['old_id' => $rp['id']],
                            [
                                'user_id' => $rp['user_id'] ?? null,
                                'journey_id' => $rp['journey_id'] ?? null,
                                'cancel_id' => $cancel_id ?? null,
                                'gratitudeNumber' => $rp['gratitudeNumber'] ?? null,
                                'points' => $rp['points'] ?? 0,
                                'amount' => $rp['amount'] ?? 0,
                                'roomStatus' => $rp['roomStatus'] ?? null,
                                'reason' => $rp['description'] ?? 'Imported Redemption',
                                'status' => $rp['status'] ?? null,
                                'created_at' => !empty($rp['created_at']) ? Carbon::parse($rp['created_at']) : null,
                                'updated_at' => !empty($rp['updated_at']) ? Carbon::parse($rp['updated_at']) : null,
                            ]
                        );
                    }
                }


            }

            DB::commit();
            return response()->json(['message' => 'Data imported successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    public function apiIndex()
    {
        // Admin view returning all gratitudes and benefits
        $gratitudes = Gratitude::with('user')->get()->map(function ($g) {
            $pending = EarnedPoint::where('user_id', $g->user_id)->where('status', 'pending')->sum('points');
            $expired = Cancellation::where('user_id', $g->user_id)->sum('points');
            return [
                'id' => $g->id,
                'gratitudeNumber' => $g->gratitudeNumber,
                'level' => $g->level,
                'totalPoints' => $g->totalPoints,
                'usablePoints' => $g->useablePoints,
                'pendingPoints' => $pending,
                'expiredPoints' => $expired,
                'status' => $g->status,
                'createdAt' => $g->created_at,
            ];
        });

        $benefits = GratitudeBenefit::with('levels')->get()->map(function ($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'type' => $b->type,
                'description' => $b->description,
                'is_active' => $b->is_active ? 'Active' : 'Inactive',
            ];
        });

        return response()->json([
            'points' => $gratitudes,
            'benefits' => $benefits,
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

        $level = \App\Models\Gratitude\GratitudeLevel::where('name', $gratitude->level)->first();
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
        $redemptions = RedeemPoints::where('gratitudeNumber', $gratitudeNumber)->get();

        $twoYearsAgo = Carbon::today()->subYears(2);

        // Sum the TOTAL remaining tier points that became usable in the last 2 years
        $rollingTotalActive = EarnedPoint::where('user_id', $gratitude->user_id)
            ->activeStatus()
            ->where(function ($query) use ($twoYearsAgo) {
                // If the table doesn't have usable_date, fallback to date or created_at
                $query->where('date', '>=', $twoYearsAgo)
                    ->orWhere('created_at', '>=', $twoYearsAgo);
            })
            ->sum(EarnedPoint::raw('points - redeemed_points'));

        $nextLevel = null;
        $pointsToNextLevel = 0;

        if ($rollingTotalActive < 15001) {
            $nextLevel = 'Globetrotter';
            $pointsToNextLevel = 15001 - $rollingTotalActive;
        } elseif ($rollingTotalActive < 30001) {
            $nextLevel = 'Jetsetter';
            $pointsToNextLevel = 30001 - $rollingTotalActive;
        }

        $data = [
            'gratitude' => $gratitude,
            'level_info' => $level,
            'earned_points' => $earnedPoints,
            'bonus_points' => $bonusPoints,
            'cancellations' => $cancellations,
            'redemptions' => $redemptions,
            'next_level' => $nextLevel,
            'points_to_next_level' => $pointsToNextLevel,
            'rolling_tier_points' => $rollingTotalActive,
            'level_benefits' => $benefits,
            'points_per_dollar' => $level ? (float) $level->redeemation_points_per_dollar : 35,
        ];

        return response()->json($data);
    }

    public function apiAddEarned(Request $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $request->validate([
            'date' => 'required|date',
            'category' => 'required|string|max:255',
            'points' => 'required|numeric|min:1',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
        ]);

        $usableDate = Carbon::parse($request->date);
        $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);

        $point = EarnedPoint::create([
            'user_id' => $gratitude->user_id,
            'gratitudeNumber' => $gratitudeNumber,
            'date' => $usableDate,
            'category' => $request->category,
            'points' => $request->points,
            'amount' => $request->amount,
            'description' => $request->description,
            'status' => 'active',
            'usable_date' => $usableDate,
            'expires_at' => $this->pointExpiryService->calculateEarnedExpiry($usableDate, $level),
        ]);

        GratitudeService::syncAccountBalance($gratitudeNumber);

        return response()->json(['message' => 'Points added', 'point' => $point]);
    }

    public function apiUpdateEarned(Request $request, $gratitudeNumber, $id)
    {
        $point = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $request->validate([
            'date' => 'required|date',
            'category' => 'required|string|max:255',
            'points' => 'required|numeric|min:1',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'expires_at' => 'nullable|date',
        ]);

        $usableDate = Carbon::parse($request->date);
        $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);
        $isManual = $request->filled('expires_at');
        $expiresAt = $isManual
            ? Carbon::parse($request->expires_at)
            : $this->pointExpiryService->calculateEarnedExpiry($usableDate, $level);

        $point->update([
            'date' => $usableDate,
            'category' => $request->category,
            'points' => $request->points,
            'amount' => $request->amount,
            'description' => $request->description,
            'usable_date' => $usableDate,
            'expires_at' => $expiresAt,
            'expires_at_manual' => $isManual,
            'status' => $point->status === 'pending' && $usableDate->isFuture() ? 'pending' : 'active',
        ]);

        GratitudeService::syncAccountBalance($gratitudeNumber);

        return response()->json(['message' => 'Points updated', 'point' => $point]);
    }

    public function apiAddBonus(Request $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'points' => 'required|numeric|min:1',
        ]);

        $effectiveDate = Carbon::parse($request->date);
        $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);

        $point = BonusPoint::create([
            'user_id' => $gratitude->user_id,
            'gratitudeNumber' => $gratitudeNumber,
            'date' => $effectiveDate,
            'description' => $request->description,
            'points' => $request->points,
            'expires_at' => $this->pointExpiryService->calculateBonusExpiry($effectiveDate, $level),
            'status' => true,
        ]);

        GratitudeService::syncAccountBalance($gratitudeNumber);

        return response()->json(['message' => 'Bonus points added', 'point' => $point]);
    }

    public function apiUpdateBonus(Request $request, $gratitudeNumber, $id)
    {
        $point = BonusPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'points' => 'required|numeric|min:1',
            'expires_at' => 'nullable|date',
        ]);

        $effectiveDate = Carbon::parse($request->date);
        $level = $this->pointExpiryService->resolveLevelForGratitude($gratitude);
        $isManual = $request->filled('expires_at');
        $expiresAt = $isManual
            ? Carbon::parse($request->expires_at)
            : $this->pointExpiryService->calculateBonusExpiry($effectiveDate, $level);

        $point->update([
            'date' => $effectiveDate,
            'description' => $request->description,
            'points' => $request->points,
            'expires_at' => $expiresAt,
            'expires_at_manual' => $isManual,
            'status' => true,
        ]);

        GratitudeService::syncAccountBalance($gratitudeNumber);

        return response()->json(['message' => 'Bonus points updated', 'point' => $point]);
    }

    public function apiCancelPoints(Request $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $request->validate(['date' => 'required', 'cancellation_reason' => 'required', 'cancellation_points' => 'required|numeric']);

        $cancel = Cancellation::create([
            'user_id' => $gratitude->user_id,
            'gratitudeNumber' => $gratitudeNumber,
            'date' => $request->date,
            'cancellation_reason' => $request->cancellation_reason,
            'cancellation_points' => $request->cancellation_points,
        ]);

        // Let's also mark a specific EarnedPoint as cancelled if passed
        if ($request->filled('earned_point_id')) {
            EarnedPoint::where('id', $request->earned_point_id)->update(['cancel_id' => $cancel->id]);
        }
        if ($request->filled('bonus_point_id')) {
            BonusPoint::where('id', $request->bonus_point_id)->update(['cancel_id' => $cancel->id]);
        }

        GratitudeService::syncAccountBalance($gratitudeNumber);

        return response()->json(['message' => 'Points cancelled', 'cancellation' => $cancel]);
    }

    public function apiDeleteEarned($gratitudeNumber, $id)
    {
        $point = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);

        if ($point->cancel_id) {
            Cancellation::where('id', $point->cancel_id)->delete();
        }

        $point->delete();

        GratitudeService::syncAccountBalance($gratitudeNumber);

        return response()->json(['message' => 'Earned point deleted']);
    }

    public function apiDeleteBonus($gratitudeNumber, $id)
    {
        $point = BonusPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);

        if ($point->cancel_id) {
            Cancellation::where('id', $point->cancel_id)->delete();
        }

        $point->delete();

        GratitudeService::syncAccountBalance($gratitudeNumber);

        return response()->json(['message' => 'Bonus point deleted']);
    }

    public function apiDeleteCancellation($gratitudeNumber, $id)
    {
        $cancel = Cancellation::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);

        EarnedPoint::where('cancel_id', $id)->update(['cancel_id' => null]);
        BonusPoint::where('cancel_id', $id)->update(['cancel_id' => null]);

        $cancel->delete();

        GratitudeService::syncAccountBalance($gratitudeNumber);

        return response()->json(['message' => 'Cancellation deleted']);
    }

    public function apiExpirePoints(Request $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $request->validate(['date' => 'required', 'points' => 'required|numeric']);

        $cancel = Cancellation::create([
            'user_id' => $gratitude->user_id,
            'gratitudeNumber' => $gratitudeNumber,
            'date' => $request->date,
            'cancellation_reason' => 'Points Expiration',
            'cancellation_points' => $request->points,
        ]);

        GratitudeService::syncAccountBalance($gratitudeNumber);

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

        GratitudeService::syncAccountBalance($gratitudeNumber);

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
}

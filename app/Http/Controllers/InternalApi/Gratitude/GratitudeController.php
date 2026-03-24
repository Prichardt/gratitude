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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GratitudeController extends Controller
{
    public function import()
    {
        $data = [];
        $getResponse = Http::get('http://aivteam.test/api/gratitude/get-all/data');
        if ($getResponse) {
            $data = json_decode($getResponse->body(), true);
        }

        if (empty($data) || !is_array($data)) {
            return response()->json(['message' => 'Invalid data format or empty payload'], 400);
        }

        // dd($data);

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
                        'expires_at' => isset($record['expires_at']) ? Carbon::parse($record['expires_at']) : null,
                        'created_at' => isset($record['created_at']) ? Carbon::parse($record['created_at']) : null,
                        'updated_at' => isset($record['updated_at']) ? Carbon::parse($record['updated_at']) : null,
                    ]
                );


                // Cancellation Points
                if (isset($record['cancellationPoints']) && is_array($record['cancellationPoints'])) {
                    foreach ($record['cancellationPoints'] as $cp) {
                        Cancellation::updateOrCreate(
                            ['old_id' => $cp['id']],
                            [
                                'user_id' => $cp['user_id'] ?? null,
                                'points' => $cp['points'] ?? 0,
                                'reason' => $cp['reason'] ?? null,
                                'amount' => $cp['amount'] ?? 0,
                                'category' => $cp['category'] ?? null,
                                'description' => $cp['description'] ?? null,
                                'date' => isset($cp['date']) ? Carbon::parse($cp['date']) : null,
                                'gratitudeNumber' => $cp['gratitudeNumber'] ?? null,
                                'points_breakdown' => $cp['points_breakdown'] ?? null,
                                'status' => $cp['status'] ?? null,
                                'created_at' => isset($cp['created_at']) ? Carbon::parse($cp['created_at']) : null,
                                'updated_at' => isset($cp['updated_at']) ? Carbon::parse($cp['updated_at']) : null,
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
                                'date' => isset($ep['date']) ? Carbon::parse($ep['date']) : null,
                                'description' => $ep['description'] ?? null,
                                'category' => $ep['category'] ?? null,
                                'status' => $ep['status'] ?? null,
                                'expires_at' => isset($ep['expires_at']) ? Carbon::parse($ep['expires_at']) : null,
                                'created_at' => isset($ep['created_at']) ? Carbon::parse($ep['created_at']) : null,
                                'updated_at' => isset($ep['updated_at']) ? Carbon::parse($ep['updated_at']) : null,
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
                                'date' => isset($bp['date']) ? Carbon::parse($bp['date']) : null,
                                'description' => $bp['description'] ?? null,
                                'category' => $bp['category'] ?? null,
                                'type' => $bp['type'] ?? null,
                                'status' => $bp['status'] ?? null,
                                'expires_at' => isset($bp['expires_at']) ? Carbon::parse($bp['expires_at']) : null,
                                'created_at' => isset($bp['created_at']) ? Carbon::parse($bp['created_at']) : null,
                                'updated_at' => isset($bp['updated_at']) ? Carbon::parse($bp['updated_at']) : null,
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
                                'created_at' => isset($rp['created_at']) ? Carbon::parse($rp['created_at']) : null,
                                'updated_at' => isset($rp['updated_at']) ? Carbon::parse($rp['updated_at']) : null,
                            ]
                        );
                    }
                }



                // Reserve Points
                // if (isset($record['reservePoints']) && is_array($record['reservePoints'])) {
                //     foreach ($record['reservePoints'] as $res) {
                //         GratitudeReserve::updateOrCreate(
                //             ['old_id' => $res['id']],
                //             [
                //                 'user_id'         => $res['user_id'] ?? null,
                //                 'points'          => $res['points'] ?? 0,
                //                 'reason'          => $res['reason'] ?? null,
                //                 'date'            => isset($res['date']) ? Carbon::parse($res['date']) : null,
                //                 'source_type'     => Gratitude::class,
                //                 'source_id'       => $gratitude->id,
                //             ]
                //         );
                //     }
                // }
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

        $earnedPoints = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)->get();
        $bonusPoints = BonusPoint::where('gratitudeNumber', $gratitudeNumber)->get();
        $cancellations = Cancellation::where('gratitudeNumber', $gratitudeNumber)->get();
        $redemptions = RedeemPoints::where('gratitudeNumber', $gratitudeNumber)->get();

        $twoYearsAgo = Carbon::today()->subYears(2);

        // Sum the TOTAL remaining tier points that became usable in the last 2 years
        $rollingTotalActive = EarnedPoint::where('user_id', $gratitude->user_id)
            ->where('status', 'active')
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
            'earned_points' => $earnedPoints,
            'bonus_points' => $bonusPoints,
            'cancellations' => $cancellations,
            'redemptions' => $redemptions,
            'next_level' => $nextLevel,
            'points_to_next_level' => $pointsToNextLevel,
            'rolling_tier_points' => $rollingTotalActive
        ];

        // dd($data);

        return response()->json($data);
    }

    public function apiAddEarned(Request $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $request->validate([
            'date' => 'required',
            'category' => 'required',
            'points' => 'required|numeric',
            'amount' => 'required|numeric',
            'description' => 'required',
        ]);

        $point = EarnedPoint::create([
            'user_id' => $gratitude->user_id,
            'gratitudeNumber' => $gratitudeNumber,
            'date' => $request->date,
            'category' => $request->category,
            'points' => $request->points,
            'amount' => $request->amount,
            'description' => $request->description,
            'status' => 'approved',
        ]);

        $gratitude->increment('useablePoints', $request->points);
        $gratitude->increment('totalPoints', $request->points);

        return response()->json(['message' => 'Points added', 'point' => $point]);
    }

    public function apiUpdateEarned(Request $request, $gratitudeNumber, $id)
    {
        $point = EarnedPoint::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);

        $request->validate([
            'date' => 'required',
            'category' => 'required',
            'points' => 'required|numeric',
            'amount' => 'required|numeric',
            'description' => 'required',
        ]);

        // Adjust gratitude totals
        $diff = $request->points - $point->points;
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();
        $gratitude->increment('useablePoints', $diff);
        $gratitude->increment('totalPoints', $diff);

        $point->update($request->only('date', 'category', 'points', 'amount', 'description'));

        return response()->json(['message' => 'Points updated', 'point' => $point]);
    }

    public function apiAddBonus(Request $request, $gratitudeNumber)
    {
        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $request->validate([
            'date' => 'required',
            'description' => 'required',
            'points' => 'required|numeric',
        ]);

        $point = BonusPoint::create([
            'user_id' => $gratitude->user_id,
            'gratitudeNumber' => $gratitudeNumber,
            'date' => $request->date,
            'description' => $request->description,
            'points' => $request->points,
        ]);

        $gratitude->increment('useablePoints', $request->points);
        $gratitude->increment('totalPoints', $request->points);

        return response()->json(['message' => 'Bonus points added', 'point' => $point]);
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

        $gratitude->decrement('useablePoints', $request->cancellation_points);
        $gratitude->decrement('totalPoints', $request->cancellation_points);

        return response()->json(['message' => 'Points cancelled', 'cancellation' => $cancel]);
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

        $gratitude->decrement('useablePoints', $request->points);
        // Expiration might not decrement totalPoints depending on rules, but we'll decrement useable.

        return response()->json(['message' => 'Points expired', 'cancellation' => $cancel]);
    }
}

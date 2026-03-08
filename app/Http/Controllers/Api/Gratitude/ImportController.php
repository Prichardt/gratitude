<?php

namespace App\Http\Controllers\Api\Gratitude;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\PointRedemption;
use App\Models\Gratitude\Cancellation;
use Carbon\Carbon;

class ImportController extends Controller
{
    public function import(Request $request)
    {

        $data = $request->json()->all();

        dd($data);

        if (empty($data) || !is_array($data)) {
            return response()->json(['message' => 'Invalid data format or empty payload'], 400);
        }

        DB::beginTransaction();

        try {
            foreach ($data as $record) {
                // Map Gratitude Model
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
                        'expires_at'      => isset($record['expires_at']) ? Carbon::parse($record['expires_at']) : null,
                    ]
                );

                // Earned Points
                if (isset($record['earnedPoints']) && is_array($record['earnedPoints'])) {
                    foreach ($record['earnedPoints'] as $ep) {
                        EarnedPoint::updateOrCreate(
                            ['old_id' => $ep['id']],
                            [
                                'user_id'            => $ep['user_id'] ?? null,
                                'journey_id'         => $ep['journey_id'] ?? null,
                                'cancel_id'          => $ep['cancel_id'] ?? null,
                                'gratitudeNumber'    => $ep['gratitudeNumber'] ?? null,
                                'points'             => $ep['points'] ?? 0,
                                'redeemed_points'    => $ep['redeemed_points'] ?? 0,
                                'redemption_history' => $ep['redemption_history'] ?? null,
                                'amount'             => $ep['amount'] ?? null,
                                'date'               => isset($ep['date']) ? Carbon::parse($ep['date']) : null,
                                'description'        => $ep['description'] ?? null,
                                'category'           => $ep['category'] ?? null,
                                'status'             => $ep['status'] ?? null,
                                'expires_at'         => isset($ep['expires_at']) ? Carbon::parse($ep['expires_at']) : null,
                            ]
                        );
                    }
                }

                // Bonus Points
                if (isset($record['bonusPoints']) && is_array($record['bonusPoints'])) {
                    foreach ($record['bonusPoints'] as $bp) {
                        BonusPoint::updateOrCreate(
                            ['old_id' => $bp['id']],
                            [
                                'user_id'            => $bp['user_id'] ?? null,
                                'journey_id'         => $bp['journey_id'] ?? null,
                                'cancel_id'          => $bp['cancel_id'] ?? null,
                                'gratitudeNumber'    => $bp['gratitudeNumber'] ?? null,
                                'points'             => $bp['points'] ?? 0,
                                'redeemed_points'    => $bp['redeemed_points'] ?? 0,
                                'redemption_history' => $bp['redemption_history'] ?? null,
                                'amount'             => $bp['amount'] ?? null,
                                'date'               => isset($bp['date']) ? Carbon::parse($bp['date']) : null,
                                'description'        => $bp['description'] ?? null,
                                'category'           => $bp['category'] ?? null,
                                'type'               => $bp['type'] ?? null,
                                'status'             => $bp['status'] ?? null,
                                'expires_at'         => isset($bp['expires_at']) ? Carbon::parse($bp['expires_at']) : null,
                            ]
                        );
                    }
                }

                // Redeem Points (mapping to PointRedemption)
                if (isset($record['redeemPoints']) && is_array($record['redeemPoints'])) {
                    foreach ($record['redeemPoints'] as $rp) {
                        PointRedemption::create([
                            'old_id'          => $rp['id'],
                            'user_id'         => $rp['user_id'] ?? null,
                            'points_redeemed' => $rp['points'] ?? 0,
                            'reason'          => $rp['description'] ?? 'Imported Redemption',
                            'source_type'     => Gratitude::class,
                            'source_id'       => $gratitude->id,
                        ]);
                    }
                }

                // Cancellation Points
                if (isset($record['cancellationPoints']) && is_array($record['cancellationPoints'])) {
                    foreach ($record['cancellationPoints'] as $cp) {
                        Cancellation::updateOrCreate(
                            ['old_id' => $cp['id']],
                            [
                                'user_id'         => $cp['user_id'] ?? null,
                                'journey_id'      => $cp['journey_id'] ?? null,
                                'date'            => isset($cp['date']) ? Carbon::parse($cp['date']) : null,
                                'category'        => $cp['category'] ?? null,
                                'gratitudeNumber' => $cp['gratitudeNumber'] ?? null,
                                'points'          => $cp['points'] ?? 0,
                                'amount'          => $cp['amount'] ?? null,
                                'description'     => $cp['description'] ?? null,
                                'bonus_points_id' => $cp['bonus_points_id'] ?? null,
                                'status'          => $cp['status'] ?? null,
                            ]
                        );
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Data imported successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to import data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Gratitude;

use App\Http\Controllers\Controller;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\Cancellation;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\RedeemPoints;
use App\Services\Gratitude\PointExpiryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function import(Request $request, PointExpiryService $pointExpiryService)
    {
        $data = $request->json()->all();

        if (empty($data) || !is_array($data)) {
            return response()->json(['message' => 'Invalid data format or empty payload'], 400);
        }

        DB::beginTransaction();

        try {
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
                        'expires_at' => isset($record['expires_at']) ? Carbon::parse($record['expires_at']) : null,
                    ]
                );

                $level = $pointExpiryService->resolveLevelForGratitude($gratitude);

                if (isset($record['earnedPoints']) && is_array($record['earnedPoints'])) {
                    foreach ($record['earnedPoints'] as $ep) {
                        $usableDate = isset($ep['usable_date'])
                            ? Carbon::parse($ep['usable_date'])
                            : (isset($ep['date']) ? Carbon::parse($ep['date']) : null);

                        EarnedPoint::updateOrCreate(
                            ['old_id' => $ep['id']],
                            [
                                'user_id' => $ep['user_id'] ?? null,
                                'journey_id' => $ep['journey_id'] ?? null,
                                'cancel_id' => $ep['cancel_id'] ?? null,
                                'gratitudeNumber' => $ep['gratitudeNumber'] ?? null,
                                'points' => $ep['points'] ?? 0,
                                'redeemed_points' => $ep['redeemed_points'] ?? 0,
                                'redemption_history' => $ep['redemption_history'] ?? null,
                                'amount' => $ep['amount'] ?? null,
                                'date' => isset($ep['date']) ? Carbon::parse($ep['date']) : null,
                                'description' => $ep['description'] ?? null,
                                'category' => $ep['category'] ?? null,
                                'status' => $this->normalizeImportedEarnedStatus($ep['status'] ?? null, $usableDate),
                                'usable_date' => $usableDate,
                                'expires_at' => $pointExpiryService->calculateEarnedExpiry($usableDate, $level),
                            ]
                        );
                    }
                }

                if (isset($record['bonusPoints']) && is_array($record['bonusPoints'])) {
                    foreach ($record['bonusPoints'] as $bp) {
                        $effectiveDate = isset($bp['date']) ? Carbon::parse($bp['date']) : null;

                        BonusPoint::updateOrCreate(
                            ['old_id' => $bp['id']],
                            [
                                'user_id' => $bp['user_id'] ?? null,
                                'journey_id' => $bp['journey_id'] ?? null,
                                'cancel_id' => $bp['cancel_id'] ?? null,
                                'gratitudeNumber' => $bp['gratitudeNumber'] ?? null,
                                'points' => $bp['points'] ?? 0,
                                'redeemed_points' => $bp['redeemed_points'] ?? 0,
                                'redemption_history' => $bp['redemption_history'] ?? null,
                                'amount' => $bp['amount'] ?? null,
                                'date' => $effectiveDate,
                                'description' => $bp['description'] ?? null,
                                'category' => $bp['category'] ?? null,
                                'type' => $bp['type'] ?? null,
                                'status' => $this->normalizeImportedBonusStatus($bp['status'] ?? null),
                                'expires_at' => $pointExpiryService->calculateBonusExpiry($effectiveDate, $level),
                            ]
                        );
                    }
                }

                if (isset($record['redeemPoints']) && is_array($record['redeemPoints'])) {
                    foreach ($record['redeemPoints'] as $rp) {
                        RedeemPoints::updateOrCreate(
                            ['old_id' => $rp['id']],
                            [
                                'user_id' => $rp['user_id'] ?? null,
                                'journey_id' => $rp['journey_id'] ?? null,
                                'cancel_id' => $rp['cancel_id'] ?? null,
                                'gratitudeNumber' => $rp['gratitudeNumber'] ?? ($record['gratitudeNumber'] ?? null),
                                'points' => $rp['points'] ?? 0,
                                'amount' => $rp['amount'] ?? null,
                                'roomStatus' => $rp['roomStatus'] ?? null,
                                'status' => $rp['status'] ?? null,
                                'category' => $rp['category'] ?? null,
                                'reason' => $rp['description'] ?? 'Imported Redemption',
                                'points_breakdown' => $rp['points_breakdown'] ?? null,
                            ]
                        );
                    }
                }

                if (isset($record['cancellationPoints']) && is_array($record['cancellationPoints'])) {
                    foreach ($record['cancellationPoints'] as $cp) {
                        Cancellation::updateOrCreate(
                            ['old_id' => $cp['id']],
                            [
                                'user_id' => $cp['user_id'] ?? null,
                                'journey_id' => $cp['journey_id'] ?? null,
                                'date' => isset($cp['date']) ? Carbon::parse($cp['date']) : null,
                                'category' => $cp['category'] ?? null,
                                'gratitudeNumber' => $cp['gratitudeNumber'] ?? null,
                                'points' => $cp['points'] ?? 0,
                                'amount' => $cp['amount'] ?? null,
                                'description' => $cp['description'] ?? null,
                                'status' => $cp['status'] ?? null,
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
                'error' => $e->getMessage(),
            ], 500);
        }
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

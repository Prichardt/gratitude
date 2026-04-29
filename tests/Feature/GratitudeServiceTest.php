<?php

namespace Tests\Feature;

use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\GratitudeLevel;
use App\Models\User;
use App\Services\Gratitude\CancellationService;
use App\Services\Gratitude\GratitudeService;
use App\Services\Gratitude\PointService;
use App\Services\Gratitude\TierService;
use Carbon\Carbon;
use Database\Seeders\GratitudeLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GratitudeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $pointService;

    protected $tierService;

    protected $gratitudeService;

    protected $gratitudeNumber = 'G-TEST-1001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GratitudeLevelSeeder::class);
        $this->user = User::factory()->create();
        $this->pointService = app(PointService::class);
        $this->tierService = new TierService;
        $this->gratitudeService = app(GratitudeService::class);

        Gratitude::create([
            'gratitudeNumber' => $this->gratitudeNumber,
            'totalPoints' => 0,
            'useablePoints' => 0,
            'level' => 'Explorer',
            'level_obtained_at' => Carbon::today(),
            'systemLevelUpdate' => true,
        ]);
    }

    public function test_pending_points_are_activated_on_usable_date()
    {
        // Add pending points usable in the future
        $this->pointService->addTierPoints($this->gratitudeNumber, 1000, Carbon::today()->addDays(5), 501);

        $activated = $this->pointService->activateTierPoints();
        $this->assertEquals(0, $activated); // Should not activate

        // Add points usable today
        $this->pointService->addTierPoints($this->gratitudeNumber, 2000, Carbon::today(), 502);

        $activatedNow = $this->pointService->activateTierPoints();
        $this->assertEquals(1, $activatedNow); // Should activate the one usable today

        $point = EarnedPoint::where('points', 2000)->first();
        $this->assertEquals('active', $point->status);
        $this->assertNotNull($point->expires_at);
    }

    public function test_fifo_point_redemption()
    {
        // Create 3 batches of points
        $this->pointService->addBonusPoints($this->gratitudeNumber, 500); // Expires in 2 years

        $p2 = new EarnedPoint(['gratitudeNumber' => $this->gratitudeNumber, 'journey_id' => 601, 'date' => Carbon::today(), 'usable_date' => Carbon::today(), 'points' => 1000, 'status' => 'active', 'expires_at' => Carbon::today()->addDays(30)]);
        $p2->save();

        $p3 = new EarnedPoint(['gratitudeNumber' => $this->gratitudeNumber, 'journey_id' => 602, 'date' => Carbon::today(), 'usable_date' => Carbon::today(), 'points' => 800, 'status' => 'active', 'expires_at' => Carbon::today()->addDays(10)]);
        $p3->save();

        // Points available: 500 (2 yrs), 1000 (30 days), 800 (10 days)
        // Order of expiration: p3 (10 days) -> p2 (30 days) -> Bonus (2 yrs)

        // Attempt to redeem 1000 points
        $result = $this->pointService->redeemPoints($this->gratitudeNumber, 1000, 'Hotel stay');
        $this->assertNotNull($result);

        $p3->refresh();
        $p2->refresh();

        // p3 should be completely drained (800 redeemed)
        $this->assertEquals(800, $p3->redeemed_points);
        $this->assertEquals(0, $p3->remaining_points);

        // p2 should have 200 redeemed
        $this->assertEquals(200, $p2->redeemed_points);
        $this->assertEquals(800, $p2->remaining_points);
    }

    public function test_tier_upgrades_and_waits_until_interval_expiry_to_downgrade()
    {
        // Initially Explorer
        $gratitude = $this->tierService->recalculateTier($this->gratitudeNumber);
        $this->assertEquals('Explorer', $gratitude->level);

        // Add 16000 usable tier points (within 2 years) -> Globetrotter
        $p1 = EarnedPoint::create([
            'gratitudeNumber' => $this->gratitudeNumber,
            'journey_id' => 701,
            'date' => Carbon::today()->subDays(7),
            'points' => 16000,
            'status' => 'active',
            'usable_date' => Carbon::today(),
        ]);

        $gratitude = $this->tierService->recalculateTier($this->gratitudeNumber);
        $this->assertEquals('Globetrotter', $gratitude->level);
        $this->assertEquals('upgrade', $gratitude->statusChange);

        $this->gratitudeService->redeemPoints($this->gratitudeNumber, ['reason' => 'Partner spend', 'redemption_type' => 'partner'], 15000);
        $gratitude = $this->tierService->recalculateTier($this->gratitudeNumber);
        $this->assertEquals('Globetrotter', $gratitude->level, 'Redeeming points must not downgrade the level inside the 2-year interval.');

        $p1->usable_date = Carbon::today()->subYears(3);
        $p1->save();
        $gratitude->update(['level_obtained_at' => Carbon::today()->subYears(2)->subDay()]);

        $gratitude = $this->tierService->recalculateTier($this->gratitudeNumber);
        $this->assertEquals('Explorer', $gratitude->level);
        $this->assertEquals('downgrade', $gratitude->statusChange);
    }

    public function test_partial_cancellation_only_removes_remaining_points()
    {
        $point = EarnedPoint::create([
            'gratitudeNumber' => $this->gratitudeNumber,
            'journey_id' => 801,
            'date' => Carbon::today(),
            'usable_date' => Carbon::today(),
            'points' => 1000,
            'redeemed_points' => 400,
            'status' => 'active',
            'expires_at' => Carbon::today()->addYear(),
        ]);

        app(CancellationService::class)->cancel(
            Gratitude::where('gratitudeNumber', $this->gratitudeNumber)->first(),
            [
                'date' => Carbon::today()->toDateString(),
                'cancellation_reason' => 'Journey adjustment',
                'cancellation_points' => 250,
            ],
            $point->id,
        );

        $point->refresh();

        $this->assertEquals(250, $point->cancelled_points);
        $this->assertEquals(350, $point->remaining_points);
        $this->assertNull($point->cancel_id);
    }

    public function test_partner_redemption_uses_partner_points_per_dollar_rate()
    {
        GratitudeLevel::where('name', 'Explorer')->update([
            'redemption_points_per_dollar' => 35,
            'partner_points_per_dollar' => 50,
        ]);

        BonusPoint::create([
            'gratitudeNumber' => $this->gratitudeNumber,
            'date' => Carbon::today(),
            'points' => 100,
            'status' => true,
            'description' => 'Partner spend balance',
            'expires_at' => Carbon::today()->addYear(),
        ]);

        $redemption = $this->gratitudeService->redeemPoints($this->gratitudeNumber, [
            'reason' => 'Partner purchase',
            'redemption_type' => 'partner',
        ], 50);

        $this->assertEquals('partner', $redemption->category);
        $this->assertEquals('1.00', (string) $redemption->amount);
        $this->assertEquals(50, $redemption->points_breakdown['points_per_dollar']);
    }

    public function test_redeem_points_consumes_soonest_expiring_points_first()
    {
        $gratitudeNumber = 'G-1001';

        Gratitude::create([
            'gratitudeNumber' => $gratitudeNumber,
            'totalPoints' => 800,
            'useablePoints' => 800,
            'level' => 'Explorer',
            'level_obtained_at' => Carbon::today(),
        ]);

        $bonus = BonusPoint::create([
            'user_id' => $this->user->id,
            'gratitudeNumber' => $gratitudeNumber,
            'date' => Carbon::parse('2026-03-01'),
            'points' => 300,
            'status' => true,
            'description' => 'Bonus batch',
            'expires_at' => Carbon::today()->addDays(15)->endOfDay(),
        ]);

        $earned = EarnedPoint::create([
            'user_id' => $this->user->id,
            'gratitudeNumber' => $gratitudeNumber,
            'journey_id' => 901,
            'date' => Carbon::parse('2026-03-01'),
            'usable_date' => Carbon::parse('2026-03-01'),
            'points' => 500,
            'status' => 'active',
            'description' => 'Earned batch',
            'expires_at' => Carbon::today()->addDays(15)->startOfDay(),
        ]);

        $redemption = $this->gratitudeService->redeemPoints($gratitudeNumber, ['reason' => 'Test redeem'], 350);

        $this->assertNotFalse($redemption);

        $bonus->refresh();
        $earned->refresh();

        $this->assertEquals(0, $bonus->redeemed_points);
        $this->assertEquals(350, $earned->redeemed_points);
    }

    public function test_import_skips_legacy_negative_expiration_rows()
    {
        $gratitudeNumber = 'G-IMPORT-EXPIRY';

        $this->gratitudeService->import([
            [
                'id' => 987,
                'gratitudeNumber' => $gratitudeNumber,
                'level' => 'Explorer',
                'earnedPoints' => [
                    [
                        'id' => 401,
                        'gratitudeNumber' => $gratitudeNumber,
                        'journey_id' => 9001,
                        'points' => 1699,
                        'date' => Carbon::today()->subYears(3)->toDateTimeString(),
                        'description' => 'Journey points',
                        'status' => 'active',
                    ],
                    [
                        'id' => 402,
                        'gratitudeNumber' => $gratitudeNumber,
                        'points' => -1699,
                        'date' => Carbon::today()->subYears(3)->toDateTimeString(),
                        'description' => 'Points Expired (2+ years)',
                        'status' => 'active',
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseMissing('earned_points', [
            'old_id' => 402,
            'points' => -1699,
        ]);

        $this->assertDatabaseMissing('cancellations', [
            'gratitudeNumber' => $gratitudeNumber,
            'points' => 1699,
            'description' => 'Points Expired (2+ years)',
        ]);

        $gratitude = Gratitude::where('gratitudeNumber', $gratitudeNumber)->first();
        $this->assertEquals(1699, $gratitude->totalExpiredPoints);
        $this->assertEquals(0, $gratitude->totalRemainingPoints);
    }

    public function test_import_turns_legacy_negative_non_expiry_rows_into_cancellations()
    {
        $gratitudeNumber = 'G-IMPORT-CANCEL';

        $this->gratitudeService->import([
            [
                'id' => 988,
                'gratitudeNumber' => $gratitudeNumber,
                'level' => 'Explorer',
                'earnedPoints' => [
                    [
                        'id' => 501,
                        'gratitudeNumber' => $gratitudeNumber,
                        'points' => -250,
                        'date' => Carbon::today()->toDateTimeString(),
                        'description' => 'Manual point correction',
                        'category' => 'guest',
                        'status' => 'active',
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseMissing('earned_points', [
            'old_id' => 501,
            'points' => -250,
        ]);

        $this->assertDatabaseHas('cancellations', [
            'old_id' => -1000000501,
            'gratitudeNumber' => $gratitudeNumber,
            'points' => 250,
            'description' => 'Manual point correction',
        ]);
    }
}

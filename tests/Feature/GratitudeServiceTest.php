<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Gratitude\BonusPoint;
use App\Models\Gratitude\EarnedPoint;
use App\Models\Gratitude\Gratitude;
use App\Services\Gratitude\GratitudeService;
use App\Services\Gratitude\PointService;
use App\Services\Gratitude\TierService;
use Carbon\Carbon;

class GratitudeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $pointService;
    protected $tierService;
    protected $gratitudeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->pointService = app(PointService::class);
        $this->tierService = new TierService();
        $this->gratitudeService = app(GratitudeService::class);
    }

    public function test_pending_points_are_activated_on_usable_date()
    {
        // Add pending points usable in the future
        $this->pointService->addTierPoints($this->user->id, 1000, Carbon::today()->addDays(5));
        
        $activated = $this->pointService->activateTierPoints();
        $this->assertEquals(0, $activated); // Should not activate
        
        // Add points usable today
        $this->pointService->addTierPoints($this->user->id, 2000, Carbon::today());
        
        $activatedNow = $this->pointService->activateTierPoints();
        $this->assertEquals(1, $activatedNow); // Should activate the one usable today

        $point = EarnedPoint::where('points', 2000)->first();
        $this->assertEquals('active', $point->status);
        $this->assertNotNull($point->expires_at);
    }

    public function test_fifo_point_redemption()
    {
        // Create 3 batches of points
        $this->pointService->addBonusPoints($this->user->id, 500); // Expires in 2 years
        
        $p2 = new EarnedPoint(['user_id' => $this->user->id, 'points' => 1000, 'status' => 'active', 'expires_at' => Carbon::today()->addDays(30)]);
        $p2->save();
        
        $p3 = new EarnedPoint(['user_id' => $this->user->id, 'points' => 800, 'status' => 'active', 'expires_at' => Carbon::today()->addDays(10)]);
        $p3->save();

        // Points available: 500 (2 yrs), 1000 (30 days), 800 (10 days)
        // Order of expiration: p3 (10 days) -> p2 (30 days) -> Bonus (2 yrs)

        // Attempt to redeem 1000 points
        $result = $this->pointService->redeemPoints($this->user->id, 1000, "Hotel stay");
        $this->assertTrue($result);

        $p3->refresh();
        $p2->refresh();
        
        // p3 should be completely drained (800 redeemed)
        $this->assertEquals(800, $p3->redeemed_points);
        $this->assertEquals(0, $p3->remaining_points);
        
        // p2 should have 200 redeemed
        $this->assertEquals(200, $p2->redeemed_points);
        $this->assertEquals(800, $p2->remaining_points);
    }

    public function test_tier_upgrades_and_downgrades()
    {
        // Initially Explorer
        $gratitude = $this->tierService->recalculateTier($this->user->id);
        $this->assertEquals('Explorer', $gratitude->level);

        // Add 16000 usable tier points (within 2 years) -> Globetrotter
        $p1 = new EarnedPoint([
            'user_id' => $this->user->id, 
            'points' => 16000, 
            'status' => 'active', 
            'usable_date' => Carbon::today()
        ]);
        $p1->save();

        $gratitude = $this->tierService->recalculateTier($this->user->id);
        $this->assertEquals('Globetrotter', $gratitude->level);
        $this->assertEquals('upgrade', $gratitude->statusChange);

        // Add 15000 more usable tier points -> Jetsetter (Total 31000)
        $p2 = new EarnedPoint([
            'user_id' => $this->user->id, 
            'points' => 15000, 
            'status' => 'active', 
            'usable_date' => Carbon::today()
        ]);
        $p2->save();

        $gratitude = $this->tierService->recalculateTier($this->user->id);
        $this->assertEquals('Jetsetter', $gratitude->level);
        $this->assertEquals('upgrade', $gratitude->statusChange);

        // Simulate 2 years passing by changing the old points to be older than 2 years
        $p1->usable_date = Carbon::today()->subYears(3);
        $p1->save();

        // Now rolling total is only 15000 -> Should downgrade to Explorer
        $gratitude = $this->tierService->recalculateTier($this->user->id);
        $this->assertEquals('Explorer', $gratitude->level);
        $this->assertEquals('downgrade', $gratitude->statusChange);
    }

    public function test_redeem_points_consumes_bonus_first_when_expiry_day_matches()
    {
        Gratitude::create([
            'gratitudeNumber' => 'G-1001',
            'totalPoints' => 800,
            'useablePoints' => 800,
            'level' => 'Explorer',
        ]);

        $bonus = BonusPoint::create([
            'user_id' => $this->user->id,
            'gratitudeNumber' => 'G-1001',
            'date' => Carbon::parse('2026-03-01'),
            'points' => 300,
            'status' => true,
            'description' => 'Bonus batch',
            'expires_at' => Carbon::parse('2026-04-15 23:59:59'),
        ]);

        $earned = EarnedPoint::create([
            'user_id' => $this->user->id,
            'gratitudeNumber' => 'G-1001',
            'date' => Carbon::parse('2026-03-01'),
            'usable_date' => Carbon::parse('2026-03-01'),
            'points' => 500,
            'status' => 'active',
            'description' => 'Earned batch',
            'expires_at' => Carbon::parse('2026-04-15 00:00:01'),
        ]);

        $redemption = $this->gratitudeService->redeemPoints('G-1001', ['reason' => 'Test redeem'], 350);

        $this->assertNotFalse($redemption);

        $bonus->refresh();
        $earned->refresh();

        $this->assertEquals(300, $bonus->redeemed_points);
        $this->assertEquals(50, $earned->redeemed_points);
    }
}

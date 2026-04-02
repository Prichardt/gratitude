<?php

namespace Database\Seeders;

use App\Models\Gratitude\GratitudeLevel;
use Illuminate\Database\Seeder;

class GratitudeLevelSeeder extends Seeder
{
    public function run(): void
    {
        // ── Levels ────────────────────────────────────────────────────────────
        $levels = [
            [
                'name'                      => 'Explorer',
                'min_points'                => 0,
                'max_points'                => 15000,
                'status'                    => true,
                'redemption_points_per_dollar' => 35,
                'earned_expire_days'        => 730,
                'bonus_expire_days'         => 730,
                'level_interval_years'      => 2,
                'jetsetter_min_journeys'    => null,
                'jetsetter_min_journey_days'=> null,
                'stay_active_rules'         => 'Accumulate at least 1 point within your 2-year membership interval to retain Explorer status.',
                'level_rules'               => [
                    ['rule' => 'Earn 1 point per $1 spent on eligible experiences'],
                    ['rule' => 'Points may be redeemed for experiences only — journey payment is not available at this level'],
                    ['rule' => 'Access to member-only offers'],
                ],
                'terms_conditions'          => 'Gratitude Rewards is subject to program terms. Points have no cash value outside the program. The program operator reserves the right to modify or terminate the program with reasonable notice. Points are non-transferable.',
                'level_terms_conditions'    => 'Explorer members earn and redeem points on eligible experiences. Journey payment using points is not available at the Explorer level. To access journey payment benefits, accumulate enough earned points to reach Globetrotter status.',
                'level_image'               => null,
                'level_icon'                => null,
            ],
            [
                'name'                      => 'Globetrotter',
                'min_points'                => 15001,
                'max_points'                => 30000,
                'status'                    => true,
                'redemption_points_per_dollar' => 30,
                'earned_expire_days'        => 730,
                'bonus_expire_days'         => 730,
                'level_interval_years'      => 2,
                'jetsetter_min_journeys'    => null,
                'jetsetter_min_journey_days'=> null,
                'stay_active_rules'         => 'Earn at least 15,001 points within your 2-year membership interval to retain Globetrotter status.',
                'level_rules'               => [
                    ['rule' => 'Earn 1 point per $1 spent'],
                    ['rule' => 'Redeem points for journeys and experiences'],
                    ['rule' => 'Complimentary room upgrades when available'],
                ],
                'terms_conditions'          => 'Gratitude Rewards is subject to program terms. Points have no cash value outside the program. The program operator reserves the right to modify or terminate the program with reasonable notice. Points are non-transferable.',
                'level_terms_conditions'    => 'Globetrotter members may use points to pay for journeys and experiences. Complimentary upgrades are subject to availability at the time of check-in and are not guaranteed. Failure to accumulate the required points within the membership interval will result in downgrade to Explorer.',
                'level_image'               => null,
                'level_icon'                => null,
            ],
            [
                'name'                      => 'Jetsetter',
                'min_points'                => 30001,
                'max_points'                => null,
                'status'                    => true,
                'redemption_points_per_dollar' => 25,
                'earned_expire_days'        => 730,
                'bonus_expire_days'         => 730,
                'level_interval_years'      => 2,
                // Must complete at least 2 journeys of more than 5 days to retain Jetsetter
                'jetsetter_min_journeys'    => 2,
                'jetsetter_min_journey_days'=> 5,
                'stay_active_rules'         => 'Earn at least 30,001 points AND complete at least 2 journeys of more than 5 days within your 2-year membership interval to retain Jetsetter status.',
                'level_rules'               => [
                    ['rule' => 'Earn 1 point per $1 spent'],
                    ['rule' => 'Redeem points for journeys and experiences at the best rate (25 pts/$1)'],
                    ['rule' => '10% discount on selected services'],
                    ['rule' => 'Priority customer support'],
                    ['rule' => 'Must complete at least 2 journeys longer than 5 days per membership interval'],
                ],
                'terms_conditions'          => 'Gratitude Rewards is subject to program terms. Points have no cash value outside the program. The program operator reserves the right to modify or terminate the program with reasonable notice. Points are non-transferable.',
                'level_terms_conditions'    => 'Jetsetter status requires both a points threshold (30,001+ earned points) AND a travel activity threshold (minimum 2 journeys of more than 5 days) within the membership interval. Failure to meet either condition at interval expiry will result in downgrade to Globetrotter. Discount applies to selected services as published on the program website.',
                'level_image'               => null,
                'level_icon'                => null,
            ],
        ];

        foreach ($levels as $levelData) {
            GratitudeLevel::updateOrCreate(
                ['name' => $levelData['name']],
                $levelData
            );
        }
       
        
    }
}

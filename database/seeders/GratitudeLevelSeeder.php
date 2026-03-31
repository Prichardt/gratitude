<?php

namespace Database\Seeders;

use App\Models\Gratitude\GratitudeLevel;
use Illuminate\Database\Seeder;

class GratitudeLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Explorer',
                'min_points' => 0,
                'max_points' => 15000,
                'status' => true,
                'redemption_points_per_dollar' => 35,
                'earned_expire_days' => 730,
                'bonus_expire_days' => 730,
                'stay_active_rules' => null,
                'level_rules' => [
                    ['rule' => 'Earn 1 point per $1 spent'],
                    ['rule' => 'Access to member-only offers'],
                ],
                'level_image' => null,
                'level_icon' => null,
            ],
            [
                'name' => 'Jetsetter',
                'min_points' => 30001,
                'max_points' => null,
                'status' => true,
                'redemption_points_per_dollar' => 25,
                'earned_expire_days' => 730,
                'bonus_expire_days' => 730,
                'stay_active_rules' => 'Earn at least 30,001 points per year to maintain Jetsetter status.',
                'level_rules' => [
                    ['rule' => 'Earn 1 points per $1 spent'],
                    ['rule' => '10% discount on selected services'],
                    ['rule' => 'Priority customer support'],
                ],
                'level_image' => null,
                'level_icon' => null,
            ],
            [
                'name' => 'Globetrotter',
                'min_points' => 15001,
                'max_points' => 30000,
                'status' => true,
                'redemption_points_per_dollar' => 30,
                'earned_expire_days' => 730,
                'bonus_expire_days' => 730,
                'stay_active_rules' => 'Earn at least 15,001 points per year to maintain Globetrotter status.',
                'level_rules' => [
                    ['rule' => 'Earn 1 points per $1 spent'],
                    ['rule' => 'Complimentary room upgrades when available'],
                ],
                'level_image' => null,
                'level_icon' => null,
            ],
        ];

        foreach ($levels as $levelData) {
            GratitudeLevel::firstOrCreate(
                ['name' => $levelData['name']],
                $levelData
            );
        }
    }
}

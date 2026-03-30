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
                'max_points' => 4999,
                'status' => true,
                'redeemation_points_per_dollar' => 100,
                'earned_expire_days' => 730,
                'bonus_expire_days' => 365,
                'stay_active_rules' => 'Earn at least 500 points per year to maintain Explorer status.',
                'level_rules' => [
                    ['rule' => 'Earn 1 point per $1 spent'],
                    ['rule' => 'Access to member-only offers'],
                ],
                'level_image' => null,
                'level_icon' => null,
            ],
            [
                'name' => 'Jetsetter',
                'min_points' => 5000,
                'max_points' => 14999,
                'status' => true,
                'redeemation_points_per_dollar' => 80,
                'earned_expire_days' => 730,
                'bonus_expire_days' => 365,
                'stay_active_rules' => 'Earn at least 2,000 points per year to maintain Jetsetter status.',
                'level_rules' => [
                    ['rule' => 'Earn 1.25 points per $1 spent'],
                    ['rule' => '10% discount on selected services'],
                    ['rule' => 'Priority customer support'],
                ],
                'level_image' => null,
                'level_icon' => null,
            ],
            [
                'name' => 'Globetrotter',
                'min_points' => 15000,
                'max_points' => null,
                'status' => true,
                'redeemation_points_per_dollar' => 60,
                'earned_expire_days' => 730,
                'bonus_expire_days' => 365,
                'stay_active_rules' => 'Earn at least 5,000 points per year to maintain Globetrotter status.',
                'level_rules' => [
                    ['rule' => 'Earn 1.5 points per $1 spent'],
                    ['rule' => '15% discount on selected services'],
                    ['rule' => 'Dedicated account manager'],
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

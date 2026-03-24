<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\GratitudeBenefit;
use App\Models\Gratitude\GratitudeLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class GratitudeBenefitsService
{
    /**
     * Get all gratitude levels.
     */
    public function getAllLevels()
    {
        return GratitudeLevel::orderBy('min_points')->get();
    }

    /**
     * Get all benefits.
     */
    public function getAllBenefits()
    {
        return GratitudeBenefit::with('levels')->get();
    }

    /**
     * Assign a benefit to a level with specific pivot values.
     */
    public function assignBenefitToLevel(int $benefitId, int $levelId, array $pivotData = [])
    {
        $benefit = GratitudeBenefit::findOrFail($benefitId);
        $benefit->levels()->syncWithoutDetaching([
            $levelId => $pivotData
        ]);

        return true;
    }

    /**
     * Get formatted benefit grid.
     */
    public function getBenefitsGrid()
    {
        $levels = $this->getAllLevels();
        $benefits = $this->getAllBenefits();

        $grid = [];

        foreach ($benefits as $benefit) {
            $row = [
                'id' => $benefit->id,
                'name' => $benefit->name,
                'description' => $benefit->description,
                'levels' => []
            ];

            foreach ($levels as $level) {
                $levelPivot = $benefit->levels->firstWhere('id', $level->id);
                $row['levels'][$level->id] = [
                    'id' => $level->id,
                    'name' => $level->name,
                    'has_benefit' => $levelPivot !== null,
                    'value' => $levelPivot ? $levelPivot->pivot->value : null,
                    'description' => $levelPivot ? $levelPivot->pivot->description : null,
                    'value_type' => $levelPivot ? $levelPivot->pivot->value_type : null,
                    'calculation' => $levelPivot ? json_decode($levelPivot->pivot->calculation, true) : null,
                    'is_active' => $levelPivot ? $levelPivot->pivot->is_active : null,
                ];
            }

            $grid[] = $row;
        }

        return [
            'levels' => $levels,
            'grid' => $grid
        ];
    }

    public function getGratitudeBenefits(): RedirectResponse
    {
        $getResponse = Http::post('https://artinvoyage.com/wp-json/api/all-gratitude-benefits');
        if ($getResponse) {
            $data = json_decode($getResponse->body(), true);
            // dd($data);
            if (!empty($data['benefits'])) {
                foreach ($data['benefits'] as $key => $value) {
                    $getBenefit = GratitudeBenefit::where('name', $value['gratitude_benefit']['benefit_name'])->first();
                    
                    if (!$getBenefit) {
                        $getBenefit = new GratitudeBenefit;
                    }

                    $getBenefit->name = $value['gratitude_benefit']['benefit_name'];
                    $getBenefit->description = $value['gratitude_benefit']['benefit_description'];
                    $getBenefit->status = 1;
                    $getBenefit->save();

                    // Sync levels dynamically
                    $levelsData = [
                        'Explorer' => collect($value)->get('gratitude_explorer', ''),
                        'Globetrotter' => collect($value)->get('gratitude_globetrotter', ''),
                        'Jetsetter' => collect($value)->get('gratitude_jetsetter', ''),
                    ];

                    foreach ($levelsData as $levelName => $levelValue) {
                        // Create the level if not found, check by level name
                        $level = GratitudeLevel::firstOrCreate(
                            ['name' => $levelName],
                            ['status' => 1]
                        );

                        // Link the benefits with the level. For name use name (BenefitGratitudeLevel)
                        \App\Models\Gratitude\BenefitGratitudeLevel::updateOrCreate(
                            [
                                'gratitude_benefit_id' => $getBenefit->id,
                                'gratitude_level_id' => $level->id,
                            ],
                            [
                                'value' => $levelValue,
                                'web_status' => 1,
                                'is_active' => 1,
                            ]
                        );
                    }
                }
            }
        }

        return Redirect::back();
    }

}

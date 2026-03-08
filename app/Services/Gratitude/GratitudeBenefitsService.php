<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\GratitudeBenefit;
use App\Models\Gratitude\GratitudeLevel;

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
}

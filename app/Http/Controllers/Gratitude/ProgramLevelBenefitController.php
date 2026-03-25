<?php

namespace App\Http\Controllers\Gratitude;

use App\Http\Controllers\Controller;
use App\Models\Gratitude\GratitudeBenefit;
use App\Services\Gratitude\GratitudeBenefitsService;
use Illuminate\Http\Request;

class ProgramLevelBenefitController extends Controller
{
    protected $benefitsService;

    public function __construct(GratitudeBenefitsService $benefitsService)
    {
        $this->benefitsService = $benefitsService;
    }

    public function index()
    {
        return response()->json($this->benefitsService->getBenefitsGrid());
    }

    public function update(Request $request, GratitudeBenefit $benefit)
    {
        $validated = $request->validate([
            'level_mappings' => 'required|array',
            'level_mappings.*.enabled' => 'boolean',
            'level_mappings.*.value' => 'nullable|string',
            'level_mappings.*.description' => 'nullable|string',
            'level_mappings.*.value_type' => 'nullable|string',
            'level_mappings.*.is_active' => 'boolean',
            'level_mappings.*.web_status' => 'boolean',
        ]);

        $syncData = [];
        foreach ($validated['level_mappings'] as $levelId => $mapping) {
            if (isset($mapping['enabled']) && $mapping['enabled']) {
                
                $isActive = $mapping['is_active'] ?? true;
                $webStatus = $isActive ? ($mapping['web_status'] ?? true) : false;

                $syncData[$levelId] = [
                    'value' => $mapping['value'] ?? null,
                    'description' => $mapping['description'] ?? null,
                    'value_type' => $mapping['value_type'] ?? 'fixed',
                    'calculation' => null,
                    'is_active' => $isActive,
                    'web_status' => $webStatus,
                ];
            }
        }

        // We use sync to ensure only the selected ones remain.
        $benefit->levels()->sync($syncData);

        return response()->json(['message' => 'Program level benefits updated successfully.']);
    }
}

<?php

namespace App\Http\Controllers\Gratitude;

use App\Http\Controllers\Controller;
use App\Models\Gratitude\GratitudeBenefit;
use App\Services\Gratitude\GratitudeBenefitsService;
use Illuminate\Http\Request;

class GratitudeBenefitController extends Controller
{
    protected $benefitsService;

    public function __construct(GratitudeBenefitsService $benefitsService)
    {
        $this->benefitsService = $benefitsService;
    }

    public function index()
    {
        $benefits = GratitudeBenefit::orderBy('name')->get();
        return response()->json($benefits);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'benefit_key' => 'nullable|string|max:100|unique:gratitude_benefits,benefit_key',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'level_mappings' => 'nullable|array',
            'level_mappings.*.enabled' => 'boolean',
            'level_mappings.*.value' => 'nullable|string',
            'level_mappings.*.description' => 'nullable|string',
            'level_mappings.*.value_type' => 'nullable|string',
            'level_mappings.*.is_active' => 'boolean',
            'level_mappings.*.web_status' => 'boolean',
        ]);

        $benefit = GratitudeBenefit::create([
            'name' => $validated['name'],
            'benefit_key' => $validated['benefit_key'] ?? null,
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'base',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (isset($validated['level_mappings'])) {
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
            $benefit->levels()->sync($syncData);
        }

        return response()->json(['message' => 'Benefit created successfully.', 'benefit' => $benefit], 201);
    }

    public function update(Request $request, GratitudeBenefit $benefit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'benefit_key' => 'nullable|string|max:100|unique:gratitude_benefits,benefit_key,' . $benefit->id,
            'description' => 'nullable|string',
            'type' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $benefit->update($validated);

        return response()->json(['message' => 'Benefit updated successfully.', 'benefit' => $benefit]);
    }

    public function destroy(GratitudeBenefit $benefit)
    {
        $benefit->delete();

        return response()->json(['message' => 'Benefit deleted successfully.']);
    }
}

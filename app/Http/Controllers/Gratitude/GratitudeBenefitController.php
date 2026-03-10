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
            'description' => 'nullable|string',
            'type' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $benefit = GratitudeBenefit::create($validated);

        return response()->json(['message' => 'Benefit created successfully.', 'benefit' => $benefit], 201);
    }

    public function update(Request $request, GratitudeBenefit $benefit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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

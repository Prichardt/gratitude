<?php

namespace App\Http\Controllers\InternalApi\Gratitude;

use App\Http\Controllers\Controller;
use App\Models\Gratitude\Gratitude;
use App\Models\Gratitude\GratitudeBenefit;
use App\Models\Gratitude\GratitudeEarnedBenefit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EarnedBenefitController extends Controller
{
    public function index(Request $request, string $gratitudeNumber)
    {
        Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $benefits = GratitudeEarnedBenefit::where('gratitudeNumber', $gratitudeNumber)
            ->with('benefit')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json(['earned_benefits' => $benefits]);
    }

    public function store(Request $request, string $gratitudeNumber)
    {
        Gratitude::where('gratitudeNumber', $gratitudeNumber)->firstOrFail();

        $validated = $request->validate([
            'benefit_id'    => 'nullable|exists:gratitude_benefits,id',
            'journey_id'    => 'nullable|integer',
            'benefit_name'  => 'required|string|max:255',
            'benefit_key'   => 'nullable|string|max:255',
            'description'   => 'required|string',
            'benefit_value' => 'nullable|string|max:255',
            'value_type'    => 'nullable|string|max:255',
            'project_data'  => 'nullable|array',
            'date'          => 'required|date',
            'status'        => 'nullable|string|max:50',
            'notes'         => 'nullable|string',
        ]);

        // Auto-fill benefit_name / benefit_key from the linked benefit when not supplied
        if (! empty($validated['benefit_id'])) {
            $benefit = GratitudeBenefit::find($validated['benefit_id']);
            if ($benefit) {
                $validated['benefit_name'] = $validated['benefit_name'] ?: $benefit->name;
                $validated['benefit_key']  = $validated['benefit_key']  ?: $benefit->benefit_key;
            }
        }

        $entry = GratitudeEarnedBenefit::create(array_merge($validated, [
            'gratitudeNumber' => $gratitudeNumber,
            'status'          => $validated['status'] ?? 'active',
        ]));

        return response()->json(['message' => 'Benefit recorded', 'earned_benefit' => $entry->load('benefit')], 201);
    }

    public function update(Request $request, string $gratitudeNumber, int $id)
    {
        $entry = GratitudeEarnedBenefit::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);

        $validated = $request->validate([
            'benefit_id'    => 'nullable|exists:gratitude_benefits,id',
            'journey_id'    => 'nullable|integer',
            'benefit_name'  => 'sometimes|required|string|max:255',
            'benefit_key'   => 'nullable|string|max:255',
            'description'   => 'sometimes|required|string',
            'benefit_value' => 'nullable|string|max:255',
            'value_type'    => 'nullable|string|max:255',
            'project_data'  => 'nullable|array',
            'date'          => 'sometimes|required|date',
            'status'        => 'nullable|string|max:50',
            'notes'         => 'nullable|string',
        ]);

        $entry->update($validated);

        return response()->json(['message' => 'Benefit updated', 'earned_benefit' => $entry->fresh()->load('benefit')]);
    }

    public function destroy(string $gratitudeNumber, int $id)
    {
        $entry = GratitudeEarnedBenefit::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);
        $entry->delete();

        return response()->json(['message' => 'Benefit deleted']);
    }

    public function activityLog(string $gratitudeNumber, int $id)
    {
        $entry = GratitudeEarnedBenefit::where('gratitudeNumber', $gratitudeNumber)->findOrFail($id);

        $log = $entry->activities()
            ->latest()
            ->get()
            ->map(fn ($activity) => [
                'id'          => $activity->id,
                'event'       => $activity->event,
                'description' => $activity->description,
                'properties'  => $activity->properties,
                'causer_type' => $activity->causer_type,
                'causer_id'   => $activity->causer_id,
                'created_at'  => Carbon::parse($activity->created_at)->toDateTimeString(),
            ]);

        return response()->json(['log' => $log]);
    }
}

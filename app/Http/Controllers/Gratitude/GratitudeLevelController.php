<?php

namespace App\Http\Controllers\Gratitude;

use App\Http\Controllers\Controller;
use App\Models\Gratitude\GratitudeLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GratitudeLevelController extends Controller
{
    public function index()
    {
        $levels = GratitudeLevel::orderBy('min_points')->get();

        return response()->json($levels);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'min_points' => 'required|numeric|min:0',
            'max_points' => 'nullable|numeric|gte:min_points',
            'redemption_points_per_dollar' => 'nullable|numeric|min:1',
            'partner_points_per_dollar' => 'nullable|numeric|min:1',
            'earned_expire_days' => 'nullable|integer|min:1',
            'bonus_expire_days' => 'nullable|integer|min:1',
            'level_rules' => 'nullable|string', // JSON string from FormData
            'level_image' => 'nullable|image|max:2048',
            'level_icon' => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name', 'min_points', 'max_points', 'earned_expire_days', 'bonus_expire_days');
        $data['status'] = filter_var($request->input('status', true), FILTER_VALIDATE_BOOLEAN);
        $data['redemption_points_per_dollar'] = $request->input('redemption_points_per_dollar', 35);
        $data['partner_points_per_dollar'] = $request->input('partner_points_per_dollar', $data['redemption_points_per_dollar']);
        $data['earned_expire_days'] = (int) $request->input('earned_expire_days', 730);
        $data['bonus_expire_days'] = (int) $request->input('bonus_expire_days', 730);

        if ($request->filled('level_rules')) {
            $data['level_rules'] = json_decode($request->input('level_rules'), true);
        }

        if ($request->hasFile('level_image')) {
            $data['level_image'] = $request->file('level_image')->store('gratitude-levels', 'public');
        }

        if ($request->hasFile('level_icon')) {
            $data['level_icon'] = $request->file('level_icon')->store('gratitude-levels', 'public');
        }

        $level = GratitudeLevel::create($data);

        return response()->json(['message' => 'Gratitude Level created successfully.', 'level' => $level], 201);
    }

    public function update(Request $request, GratitudeLevel $level)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'min_points' => 'required|numeric|min:0',
            'max_points' => 'nullable|numeric|gte:min_points',
            'redemption_points_per_dollar' => 'nullable|numeric|min:1',
            'partner_points_per_dollar' => 'nullable|numeric|min:1',
            'earned_expire_days' => 'nullable|integer|min:1',
            'bonus_expire_days' => 'nullable|integer|min:1',
            'level_rules' => 'nullable|string',
            'level_image' => 'nullable|image|max:2048',
            'level_icon' => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name', 'min_points', 'max_points', 'earned_expire_days', 'bonus_expire_days');
        // Handle vue sending boolean as string "true" / "false" in FormData
        $data['status'] = filter_var($request->input('status', true), FILTER_VALIDATE_BOOLEAN);
        if ($request->filled('redemption_points_per_dollar')) {
            $data['redemption_points_per_dollar'] = $request->input('redemption_points_per_dollar');
        }
        if ($request->filled('partner_points_per_dollar')) {
            $data['partner_points_per_dollar'] = $request->input('partner_points_per_dollar');
        }
        $data['earned_expire_days'] = (int) $request->input('earned_expire_days', $level->earned_expire_days ?: 730);
        $data['bonus_expire_days'] = (int) $request->input('bonus_expire_days', $level->bonus_expire_days ?: 730);

        if ($request->has('level_rules')) {
            $rawRules = $request->input('level_rules');
            $data['level_rules'] = ($rawRules !== '' && $rawRules !== null)
                ? json_decode($rawRules, true)
                : null;
        }

        if ($request->hasFile('level_image')) {
            if ($level->level_image) {
                Storage::disk('public')->delete($level->level_image);
            }
            $data['level_image'] = $request->file('level_image')->store('gratitude-levels', 'public');
        }

        if ($request->hasFile('level_icon')) {
            if ($level->level_icon) {
                Storage::disk('public')->delete($level->level_icon);
            }
            $data['level_icon'] = $request->file('level_icon')->store('gratitude-levels', 'public');
        }

        $level->update($data);

        return response()->json(['message' => 'Gratitude Level updated successfully.', 'level' => $level]);
    }

    public function destroy(GratitudeLevel $level)
    {
        if ($level->level_image) {
            Storage::disk('public')->delete($level->level_image);
        }
        if ($level->level_icon) {
            Storage::disk('public')->delete($level->level_icon);
        }

        $level->delete();

        return response()->json(['message' => 'Gratitude Level deleted successfully.']);
    }
}

<?php

namespace App\Services\Gratitude;

use App\Models\Gratitude\GratitudeBenefit;
use App\Models\Gratitude\GratitudeLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

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
                'benefit_key' => $benefit->benefit_key,
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

    /**
     * Check whether a given level (by name) has an active benefit identified by its benefit_key.
     *
     * Usage: levelHasBenefit('Explorer', 'journey_payment')  → false
     *        levelHasBenefit('Globetrotter', 'journey_payment') → true
     */
    public function levelHasBenefit(string $levelName, string $benefitKey): bool
    {
        $level = GratitudeLevel::where('name', $levelName)->first();
        if (!$level) {
            return false;
        }

        return $level->benefits()
            ->where('benefit_key', $benefitKey)
            ->wherePivot('is_active', true)
            ->exists();
    }

    public function getGratitudeBenefits(): RedirectResponse
    {
        $getResponse = Http::post('https://artinvoyage.com/wp-json/api/all-gratitude-benefits');
        if ($getResponse && $getResponse->successful()) {
            $data = $getResponse->json();

            if (!empty($data['benefits'])) {
                $levels = GratitudeLevel::get();
                $explorerLevel     = $levels->first(fn($l) => stripos($l->name, 'Explorer') !== false);
                $globetrotterLevel = $levels->first(fn($l) => stripos($l->name, 'Globetrotter') !== false);
                $jetsetterLevel    = $levels->first(fn($l) => stripos($l->name, 'Jetsetter') !== false || stripos($l->name, 'Jetesetter') !== false);

                foreach ($data['benefits'] as $value) {
                    $benefitData = $value['gratitude_benefit'];
                    $benefitName = $benefitData['benefit_name'];

                    // Find existing record by name (no benefit_key comes from the API)
                    $getBenefit = GratitudeBenefit::where('name', $benefitName)->first()
                                  ?? new GratitudeBenefit;

                    // Generate a benefit_key if the record doesn't already have one
                    $benefitKey = $getBenefit->benefit_key
                                  ?: $this->generateBenefitKey($benefitName);

                    $getBenefit->name        = $benefitName;
                    $getBenefit->benefit_key = $benefitKey;
                    $getBenefit->description = $benefitData['benefit_description'] ?? null;
                    $getBenefit->is_active   = 1;
                    $getBenefit->save();

                    // Sync level assignments
                    $syncData = [];

                    if ($explorerLevel && !empty($value['gratitude_explorer'])) {
                        $syncData[$explorerLevel->id] = [
                            'value'       => $value['gratitude_explorer'],
                            'description' => $value['gratitude_explorer'],
                            'is_active'   => 1,
                            'web_status'  => 1,
                        ];
                    }
                    if ($globetrotterLevel && !empty($value['gratitude_globetrotter'])) {
                        $syncData[$globetrotterLevel->id] = [
                            'value'       => $value['gratitude_globetrotter'],
                            'description' => $value['gratitude_globetrotter'],
                            'is_active'   => 1,
                            'web_status'  => 1,
                        ];
                    }
                    if ($jetsetterLevel && !empty($value['gratitude_jetsetter'])) {
                        $syncData[$jetsetterLevel->id] = [
                            'value'       => $value['gratitude_jetsetter'],
                            'description' => $value['gratitude_jetsetter'],
                            'is_active'   => 1,
                            'web_status'  => 1,
                        ];
                    }

                    $getBenefit->levels()->sync($syncData);
                }
            }
        }

        return Redirect::back();
    }

    /**
     * Use Claude (Anthropic API) to generate a concise snake_case benefit_key from
     * a human-readable benefit name.  Falls back to Str::snake() if the API is
     * unavailable or returns an unusable response.
     *
     * The generated key is also de-duplicated against existing keys in the DB.
     */
    public function generateBenefitKey(string $name): string
    {
        $apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));
        $generated = null;

        if ($apiKey) {
            try {
                $response = Http::withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model'      => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 20,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => "Generate a short snake_case identifier key for a travel loyalty program benefit named \"{$name}\". "
                                       . "Rules: lowercase only, words separated by underscores, no spaces or special characters, 1-4 words max. "
                                       . "Return only the key itself with no explanation, punctuation, or quotes. "
                                       . "Examples: journey_payment, room_upgrade, priority_support, service_discount.",
                        ],
                    ],
                ]);

                if ($response->successful()) {
                    $raw = trim($response->json('content.0.text') ?? '');
                    // Accept only valid snake_case strings
                    if ($raw && preg_match('/^[a-z][a-z0-9_]{1,49}$/', $raw)) {
                        $generated = $raw;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('generateBenefitKey: Claude API call failed', ['error' => $e->getMessage()]);
            }
        }

        // Fallback: derive key from the name using Str::snake
        if (!$generated) {
            $generated = Str::snake(Str::ascii($name));
            // Remove any characters that are not lowercase letters, digits, or underscores
            $generated = preg_replace('/[^a-z0-9_]/', '_', $generated);
            $generated = preg_replace('/_+/', '_', $generated);
            $generated = trim($generated, '_');
        }

        // Ensure uniqueness within the gratitude_benefits table
        $base  = $generated;
        $index = 2;
        while (GratitudeBenefit::where('benefit_key', $generated)->exists()) {
            $generated = $base . '_' . $index++;
        }

        return $generated;
    }

}

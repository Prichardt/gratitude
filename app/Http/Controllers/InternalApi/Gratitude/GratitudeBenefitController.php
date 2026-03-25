<?php

namespace App\Http\Controllers\InternalApi\Gratitude;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Gratitude\GratitudeBenefit;
use App\Models\Gratitude\GratitudeLevel;

class GratitudeBenefitController extends Controller
{
    public function importBenefits()
    {
        $getResponse = Http::get('http://artinvoyage.local/wp-json/api/all-gratitude-benefits');

        if ($getResponse && $getResponse->successful()) {
            $data = $getResponse->json();
            
            if (isset($data['benefits'])) {
                // Pre-fetch levels to map them. Handling a typo in "Jetesetter" with partial matching.
                $levels = GratitudeLevel::get();
                $explorerLevel = $levels->first(fn($l) => stripos($l->name, 'Explorer') !== false);
                $globetrotterLevel = $levels->first(fn($l) => stripos($l->name, 'Globetrotter') !== false);
                $jetsetterLevel = $levels->first(fn($l) => stripos($l->name, 'Jetsetter') !== false || stripos($l->name, 'Jetesetter') !== false);
                
                foreach ($data['benefits'] as $value) {
                    $benefitData = $value['gratitude_benefit'];
                    
                    $benefit = GratitudeBenefit::updateOrCreate(
                        ['name' => $benefitData['benefit_name']],
                        [
                            'description' => $benefitData['benefit_description'],
                            'is_active' => 1,
                        ]
                    );

                    $syncData = [];
                    
                    if ($explorerLevel && !empty($value['gratitude_explorer'])) {
                        $syncData[$explorerLevel->id] = [
                            'value' => $value['gratitude_explorer'],
                            'description' => $value['gratitude_explorer'],
                            'is_active' => 1,
                            'web_status' => 1,
                        ];
                    }
                    if ($globetrotterLevel && !empty($value['gratitude_globetrotter'])) {
                        $syncData[$globetrotterLevel->id] = [
                            'value' => $value['gratitude_globetrotter'],
                            'description' => $value['gratitude_globetrotter'],
                            'is_active' => 1,
                            'web_status' => 1,
                        ];
                    }
                    if ($jetsetterLevel && !empty($value['gratitude_jetsetter'])) {
                        $syncData[$jetsetterLevel->id] = [
                            'value' => $value['gratitude_jetsetter'],
                            'description' => $value['gratitude_jetsetter'],
                            'is_active' => 1,
                            'web_status' => 1,
                        ];
                    }

                    $benefit->levels()->sync($syncData);
                }
                
                return response()->json(['success' => true, 'message' => 'Benefits imported successfully']);
            }
        }
        
        return response()->json(['success' => false, 'message' => 'Failed to import benefits'], 500);
    }
}

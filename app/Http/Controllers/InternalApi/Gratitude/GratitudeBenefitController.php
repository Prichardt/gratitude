<?php

namespace App\Http\Controllers\InternalApi\Gratitude;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GratitudeBenefitController extends Controller
{
    public function import()
    {
        $getResponse = Http::post('https://artinvoyage.com/wp-json/api/all-gratitude-benefits');
        if ($getResponse) {
            $data = json_decode($getResponse->body(), true);
            // dd($data);
            if ($data['benefits']) {
                foreach ($data['benefits'] as $key => $value) {
                    $getBenefit = Benefit::where('name', $value['gratitude_benefit']['benefit_name'])->first();
                    if ($getBenefit) {

                        $getBenefit->name = $value['gratitude_benefit']['benefit_name'];
                        $getBenefit->description = $value['gratitude_benefit']['benefit_description'];
                        $getBenefit->explorer = $value['gratitude_explorer'];
                        $getBenefit->globetrotter = $value['gratitude_globetrotter'];
                        $getBenefit->jetsetter = $value['gratitude_jetsetter'];
                        $getBenefit->status = 1;

                        $getBenefit->save();
                    } else {
                        $benefit = new Benefit;

                        $benefit->name = $value['gratitude_benefit']['benefit_name'];
                        $benefit->description = $value['gratitude_benefit']['benefit_description'];
                        $benefit->explorer = $value['gratitude_explorer'];
                        $benefit->globetrotter = $value['gratitude_globetrotter'];
                        $benefit->jetsetter = $value['gratitude_jetsetter'];
                        $benefit->status = 1;

                        $benefit->save();
                    }
                }
            }
        }
    }
}

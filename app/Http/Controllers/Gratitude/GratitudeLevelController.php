<?php

namespace App\Http\Controllers\Gratitude;

use App\Http\Controllers\Controller;
use App\Models\Gratitude\GratitudeLevel;
use Illuminate\Http\Request;

class GratitudeLevelController extends Controller
{
    public function index()
    {
        $levels = GratitudeLevel::orderBy('min_points')->get();
        return response()->json($levels);
    }
}

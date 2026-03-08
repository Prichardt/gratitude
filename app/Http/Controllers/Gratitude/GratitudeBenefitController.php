<?php

namespace App\Http\Controllers\Gratitude;

use App\Http\Controllers\Controller;
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
        $gridData = $this->benefitsService->getBenefitsGrid();
        return response()->json($gridData);
    }
}

<?php

namespace App\Http\Controllers\Api\Gratitude;

use App\Http\Controllers\Controller;
use App\Services\Gratitude\GratitudeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function import(Request $request, GratitudeService $gratitudeService)
    {
        $data = $request->json()->all();

        if (empty($data) || ! is_array($data)) {
            return response()->json(['message' => 'Invalid data format or empty payload'], 400);
        }

        DB::beginTransaction();

        try {
            $gratitudeService->import($data);

            DB::commit();

            return response()->json(['message' => 'Data imported successfully'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to import data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

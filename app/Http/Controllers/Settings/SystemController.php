<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Settings\SystemSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemController extends Controller
{
    public function __construct(protected SystemSettingService $settingService)
    {
    }

    /**
     * Return all settings as JSON for the internal API.
     */
    public function apiIndex()
    {
        return response()->json($this->settingService->getAllSettings());
    }

    /**
     * Handle updating standard text/color settings.
     */
    public function apiUpdate(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ]);

        $this->settingService->updateSettings($validated['settings']);

        return response()->json([
            'message' => 'Settings updated successfully.',
            'settings' => $this->settingService->getAllSettings()
        ]);
    }

    /**
     * Handle uploading an image setting (logo or icon).
     */
    public function apiUploadImage(Request $request)
    {
        $request->validate([
            'key' => 'required|string|in:company_logo,company_icon',
            'image' => 'required|image|max:2048', // Max 2MB
        ]);

        $setting = $this->settingService->uploadImageSetting(
            $request->key,
            $request->file('image')
        );

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'key' => $setting->key,
            'url' => $setting->value
        ]);
    }
}

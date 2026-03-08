<?php

namespace App\Services\Settings;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SystemSettingService
{
    /**
     * Get all settings as a key-value pair array.
     */
    public function getAllSettings(): array
    {
        return SystemSetting::all()->pluck('value', 'key')->toArray();
    }

    /**
     * Get a specific setting by key.
     */
    public function getSetting(string $key, $default = null)
    {
        $setting = SystemSetting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Update or create a setting.
     */
    public function updateSetting(string $key, $value): SystemSetting
    {
        return SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Handle updating multiple settings from an array.
     */
    public function updateSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            // Skip nulls if we don't want to overwrite with empty
            if ($value !== null) {
                $this->updateSetting($key, $value);
            }
        }
    }

    /**
     * Handle file upload for settings like logo or icon.
     */
    public function uploadImageSetting(string $key, UploadedFile $file): SystemSetting
    {
        // Delete old file if exists
        $oldPath = $this->getSetting($key);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $file->store('settings', 'public');
        
        return $this->updateSetting($key, '/storage/' . $path);
    }
}

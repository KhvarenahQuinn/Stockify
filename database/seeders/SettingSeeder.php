<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Application settings.
 *
 * The logo is deliberately left unset. The previous value pointed at a
 * "logo.png" that was never written to the public disk, so every fresh install
 * rendered a broken image in the navbar and settings screen. Setting it is a
 * user action through the settings form.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            SettingService::KEY_APP_NAME => 'Stockify',
            SettingService::KEY_DEFAULT_MIN_STOCK => '10',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->clearStaleLogo();
    }

    /**
     * Remove any app_logo setting whose file is no longer on the public disk.
     */
    protected function clearStaleLogo(): void
    {
        $logo = Setting::where('key', SettingService::KEY_APP_LOGO)->value('value');

        if ($logo === null || $logo === '') {
            return;
        }

        if (! Storage::disk(SettingService::LOGO_DISK)->exists($logo)) {
            Setting::where('key', SettingService::KEY_APP_LOGO)->update(['value' => null]);
        }
    }
}
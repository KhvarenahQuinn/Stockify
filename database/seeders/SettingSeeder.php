<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seed the application's settings.
     */
    public function run(): void
    {
        foreach ([
            'app_name' => 'Stockify',
            'app_logo' => 'logo.png',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

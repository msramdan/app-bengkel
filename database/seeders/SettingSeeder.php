<?php

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingService::class);

        $settings->setMany([
            'app_name' => config('branding.name'),
            'app_tagline' => config('branding.tagline'),
            'app_description' => config('branding.description'),
            'hiwa_enabled' => false,
            'oil_change_reminder_enabled' => false,
            'oil_change_reminder_months' => 3,
        ]);
    }
}

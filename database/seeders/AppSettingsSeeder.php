<?php

namespace Database\Seeders;

use App\Models\DesignConfiguration;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    /**
     * Seed default app branding settings into design_configurations.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'Maimaar Estimation Calculator', 'label' => 'App Name', 'sort_order' => 1],
            ['key' => 'company_name', 'value' => 'Maimaar', 'label' => 'Company Name', 'sort_order' => 2],
            ['key' => 'app_logo_path', 'value' => '', 'label' => 'App Logo', 'sort_order' => 3],
            ['key' => 'favicon_path', 'value' => '', 'label' => 'Favicon', 'sort_order' => 4],
            ['key' => 'primary_color', 'value' => '#3B82F6', 'label' => 'Primary Color', 'sort_order' => 5],
        ];

        foreach ($settings as $setting) {
            DesignConfiguration::query()->updateOrCreate(
                ['category' => 'app_settings', 'key' => $setting['key']],
                $setting + ['category' => 'app_settings'],
            );
        }
    }
}

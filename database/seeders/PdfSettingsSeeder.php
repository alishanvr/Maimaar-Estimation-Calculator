<?php

namespace Database\Seeders;

use App\Models\DesignConfiguration;
use Illuminate\Database\Seeder;

class PdfSettingsSeeder extends Seeder
{
    /**
     * Seed default PDF settings into design_configurations.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => 'Maimaar Group', 'label' => 'Company Name', 'sort_order' => 1],
            ['key' => 'company_logo_path', 'value' => '', 'label' => 'Company Logo', 'sort_order' => 2],
            ['key' => 'font_family', 'value' => 'dejavu-sans', 'label' => 'Font Family', 'sort_order' => 3],
            ['key' => 'header_color', 'value' => '#1e3a5f', 'label' => 'Header/Footer Color', 'sort_order' => 4],
            ['key' => 'body_font_size', 'value' => '11', 'label' => 'Body Font Size (px)', 'sort_order' => 5],
            ['key' => 'body_line_height', 'value' => '1.4', 'label' => 'Body Line Height', 'sort_order' => 6],
            ['key' => 'show_page_numbers', 'value' => '1', 'label' => 'Show Page Numbers', 'sort_order' => 7],
            ['key' => 'paper_size', 'value' => 'a4', 'label' => 'Paper Size', 'sort_order' => 8],
            ['key' => 'footer_text', 'value' => '', 'label' => 'Custom Footer Text', 'sort_order' => 9],
        ];

        foreach ($settings as $setting) {
            DesignConfiguration::query()->updateOrCreate(
                ['category' => 'pdf_settings', 'key' => $setting['key']],
                $setting + ['category' => 'pdf_settings'],
            );
        }
    }
}

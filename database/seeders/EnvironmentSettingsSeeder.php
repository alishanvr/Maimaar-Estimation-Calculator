<?php

namespace Database\Seeders;

use App\Models\DesignConfiguration;
use Illuminate\Database\Seeder;

class EnvironmentSettingsSeeder extends Seeder
{
    /**
     * Seed default environment settings into design_configurations.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'app_url', 'value' => config('app.url', 'http://localhost'), 'label' => 'App URL', 'sort_order' => 1],
            ['key' => 'mail_mailer', 'value' => 'log', 'label' => 'Mail Mailer', 'sort_order' => 2],
            ['key' => 'mail_host', 'value' => '127.0.0.1', 'label' => 'Mail Host', 'sort_order' => 3],
            ['key' => 'mail_port', 'value' => '2525', 'label' => 'Mail Port', 'sort_order' => 4],
            ['key' => 'mail_username', 'value' => '', 'label' => 'Mail Username', 'sort_order' => 5],
            ['key' => 'mail_password', 'value' => '', 'label' => 'Mail Password', 'sort_order' => 6],
            ['key' => 'mail_from_address', 'value' => 'hello@example.com', 'label' => 'Mail From Address', 'sort_order' => 7],
            ['key' => 'mail_from_name', 'value' => 'Maimaar', 'label' => 'Mail From Name', 'sort_order' => 8],
            ['key' => 'mail_scheme', 'value' => '', 'label' => 'Mail Scheme', 'sort_order' => 9],
            ['key' => 'log_channel', 'value' => 'stack', 'label' => 'Log Channel', 'sort_order' => 10],
            ['key' => 'log_level', 'value' => 'debug', 'label' => 'Log Level', 'sort_order' => 11],
            ['key' => 'session_lifetime', 'value' => '120', 'label' => 'Session Lifetime', 'sort_order' => 12],
            ['key' => 'session_encrypt', 'value' => 'false', 'label' => 'Session Encrypt', 'sort_order' => 13],
            ['key' => 'session_secure_cookie', 'value' => 'false', 'label' => 'Session Secure Cookie', 'sort_order' => 14],
            ['key' => 'frontend_url', 'value' => '', 'label' => 'Frontend URL', 'sort_order' => 15],
            ['key' => 'filesystem_disk', 'value' => 'local', 'label' => 'Filesystem Disk', 'sort_order' => 16],
        ];

        foreach ($settings as $setting) {
            DesignConfiguration::query()->updateOrCreate(
                ['category' => 'environment_settings', 'key' => $setting['key']],
                $setting + ['category' => 'environment_settings'],
            );
        }
    }
}

<?php

use App\Filament\Pages\EnvironmentSettings;
use App\Models\DesignConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;

it('can render the environment settings page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get('/admin/environment-settings')
        ->assertSuccessful();
});

it('cannot access environment settings page as non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/admin/environment-settings')
        ->assertForbidden();
});

it('loads existing settings into the form', function () {
    $admin = User::factory()->admin()->create();

    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'mail_mailer',
        'value' => 'smtp',
        'label' => 'Mail Mailer',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'mail_host',
        'value' => 'smtp.example.com',
        'label' => 'Mail Host',
    ]);

    Cache::forget('environment_settings');
    $this->actingAs($admin);

    Livewire::test(EnvironmentSettings::class)
        ->assertFormSet([
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.com',
        ]);
});

it('can save environment settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(EnvironmentSettings::class)
        ->fillForm([
            'app_url' => 'https://test-app.com',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.test.com',
            'mail_port' => '587',
            'mail_from_address' => 'test@test.com',
            'mail_from_name' => 'Test',
            'log_channel' => 'daily',
            'log_level' => 'error',
            'session_lifetime' => '60',
            'filesystem_disk' => 'local',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(DesignConfiguration::query()
        ->where('category', 'environment_settings')
        ->where('key', 'app_url')
        ->value('value')
    )->toBe('https://test-app.com');

    expect(DesignConfiguration::query()
        ->where('category', 'environment_settings')
        ->where('key', 'mail_mailer')
        ->value('value')
    )->toBe('smtp');

    expect(DesignConfiguration::query()
        ->where('category', 'environment_settings')
        ->where('key', 'log_level')
        ->value('value')
    )->toBe('error');
});

it('encrypts mail password on save', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(EnvironmentSettings::class)
        ->fillForm([
            'app_url' => 'https://test.com',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.test.com',
            'mail_port' => '587',
            'mail_password' => 'super-secret',
            'mail_from_address' => 'test@test.com',
            'mail_from_name' => 'Test',
            'log_channel' => 'stack',
            'log_level' => 'debug',
            'session_lifetime' => '120',
            'filesystem_disk' => 'local',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $storedPassword = DesignConfiguration::query()
        ->where('category', 'environment_settings')
        ->where('key', 'mail_password')
        ->value('value');

    // Password should be encrypted (not plain text)
    expect($storedPassword)->not->toBe('super-secret');

    // But should be decryptable
    expect(Crypt::decryptString($storedPassword))->toBe('super-secret');
});

it('flushes cache after saving', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    // Pre-populate cache
    Cache::put('environment_settings', ['old' => 'data'], 86400);

    Livewire::test(EnvironmentSettings::class)
        ->fillForm([
            'app_url' => 'https://test.com',
            'mail_mailer' => 'log',
            'mail_from_address' => 'test@test.com',
            'mail_from_name' => 'Test',
            'log_channel' => 'stack',
            'log_level' => 'debug',
            'session_lifetime' => '120',
            'filesystem_disk' => 'local',
        ])
        ->call('save');

    expect(Cache::has('environment_settings'))->toBeFalse();
});

it('loads database settings from config', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(EnvironmentSettings::class)
        ->assertFormSet([
            'db_connection' => config('database.default'),
        ]);
});

it('warns when attempting to migrate to the same database driver', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(EnvironmentSettings::class)
        ->callAction('migrate_database')
        ->assertNotified('Same database driver');
});

it('preserves existing password when field is empty', function () {
    $admin = User::factory()->admin()->create();

    $encrypted = Crypt::encryptString('existing-password');
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'mail_password',
        'value' => $encrypted,
        'label' => 'Mail Password',
    ]);

    $this->actingAs($admin);
    Cache::forget('environment_settings');

    Livewire::test(EnvironmentSettings::class)
        ->fillForm([
            'app_url' => 'https://test.com',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.test.com',
            'mail_port' => '587',
            'mail_password' => '', // empty = keep existing
            'mail_from_address' => 'test@test.com',
            'mail_from_name' => 'Test',
            'log_channel' => 'stack',
            'log_level' => 'debug',
            'session_lifetime' => '120',
            'filesystem_disk' => 'local',
        ])
        ->call('save');

    // Password should remain unchanged (the encrypted version)
    $storedPassword = DesignConfiguration::query()
        ->where('category', 'environment_settings')
        ->where('key', 'mail_password')
        ->value('value');

    expect($storedPassword)->toBe($encrypted);
});

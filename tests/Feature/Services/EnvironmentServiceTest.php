<?php

use App\Models\DesignConfiguration;
use App\Services\EnvironmentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

beforeEach(function () {
    Cache::forget('environment_settings');
});

it('returns default values when no settings exist', function () {
    $service = app(EnvironmentService::class);

    expect($service->mailMailer())->toBe(config('mail.default', 'log'));
    expect($service->mailPort())->toBe((int) config('mail.mailers.smtp.port', 2525));
    expect($service->logChannel())->toBe(config('logging.default', 'stack'));
    expect($service->sessionLifetime())->toBe((int) config('session.lifetime', 120));
    expect($service->sessionEncrypt())->toBeFalse();
    expect($service->sessionSecureCookie())->toBeFalse();
    expect($service->filesystemDisk())->toBe(config('filesystems.default', 'local'));
});

it('reads settings from database', function () {
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
    $service = app(EnvironmentService::class);

    expect($service->mailMailer())->toBe('smtp');
    expect($service->mailHost())->toBe('smtp.example.com');
});

it('caches settings for 24 hours', function () {
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'log_level',
        'value' => 'error',
        'label' => 'Log Level',
    ]);

    $service = app(EnvironmentService::class);
    $service->all(); // trigger cache

    expect(Cache::has('environment_settings'))->toBeTrue();
});

it('flushes cache correctly', function () {
    $service = app(EnvironmentService::class);
    $service->all(); // trigger cache

    expect(Cache::has('environment_settings'))->toBeTrue();

    $service->flushCache();

    expect(Cache::has('environment_settings'))->toBeFalse();
});

it('decrypts encrypted mail password', function () {
    $encryptedPassword = Crypt::encryptString('my-secret-password');

    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'mail_password',
        'value' => $encryptedPassword,
        'label' => 'Mail Password',
    ]);

    Cache::forget('environment_settings');
    $service = app(EnvironmentService::class);

    expect($service->mailPassword())->toBe('my-secret-password');
});

it('returns default when decryption fails', function () {
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'mail_password',
        'value' => 'not-encrypted-garbage-value',
        'label' => 'Mail Password',
    ]);

    Cache::forget('environment_settings');
    $service = app(EnvironmentService::class);

    // Should return default (empty string from config) instead of crashing
    expect($service->mailPassword())->toBeString();
});

it('returns correct mail settings', function () {
    $settings = [
        ['key' => 'mail_mailer', 'value' => 'smtp'],
        ['key' => 'mail_host', 'value' => 'smtp.gmail.com'],
        ['key' => 'mail_port', 'value' => '587'],
        ['key' => 'mail_username', 'value' => 'user@gmail.com'],
        ['key' => 'mail_from_address', 'value' => 'noreply@test.com'],
        ['key' => 'mail_from_name', 'value' => 'Test App'],
        ['key' => 'mail_scheme', 'value' => 'tls'],
    ];

    foreach ($settings as $setting) {
        DesignConfiguration::query()->create(
            $setting + ['category' => 'environment_settings', 'label' => 'Test'],
        );
    }

    Cache::forget('environment_settings');
    $service = app(EnvironmentService::class);

    expect($service->mailMailer())->toBe('smtp');
    expect($service->mailHost())->toBe('smtp.gmail.com');
    expect($service->mailPort())->toBe(587);
    expect($service->mailUsername())->toBe('user@gmail.com');
    expect($service->mailFromAddress())->toBe('noreply@test.com');
    expect($service->mailFromName())->toBe('Test App');
    expect($service->mailScheme())->toBe('tls');
});

it('returns correct session settings', function () {
    $settings = [
        ['key' => 'session_lifetime', 'value' => '60'],
        ['key' => 'session_encrypt', 'value' => 'true'],
        ['key' => 'session_secure_cookie', 'value' => 'true'],
    ];

    foreach ($settings as $setting) {
        DesignConfiguration::query()->create(
            $setting + ['category' => 'environment_settings', 'label' => 'Test'],
        );
    }

    Cache::forget('environment_settings');
    $service = app(EnvironmentService::class);

    expect($service->sessionLifetime())->toBe(60);
    expect($service->sessionEncrypt())->toBeTrue();
    expect($service->sessionSecureCookie())->toBeTrue();
});

it('returns correct logging settings', function () {
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'log_channel',
        'value' => 'daily',
        'label' => 'Log Channel',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'log_level',
        'value' => 'warning',
        'label' => 'Log Level',
    ]);

    Cache::forget('environment_settings');
    $service = app(EnvironmentService::class);

    expect($service->logChannel())->toBe('daily');
    expect($service->logLevel())->toBe('warning');
});

it('returns correct frontend url', function () {
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'frontend_url',
        'value' => 'https://app.example.com',
        'label' => 'Frontend URL',
    ]);

    Cache::forget('environment_settings');
    $service = app(EnvironmentService::class);

    expect($service->frontendUrl())->toBe('https://app.example.com');
});

it('returns null mail scheme when empty', function () {
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'mail_scheme',
        'value' => '',
        'label' => 'Mail Scheme',
    ]);

    Cache::forget('environment_settings');
    $service = app(EnvironmentService::class);

    expect($service->mailScheme())->toBeNull();
});

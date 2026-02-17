<?php

use App\Models\DesignConfiguration;
use App\Providers\EnvironmentServiceProvider;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('environment_settings');

    // Ensure installed flag exists for provider to run
    if (! file_exists(storage_path('app/installed'))) {
        file_put_contents(storage_path('app/installed'), now()->toIso8601String());
    }
});

it('overrides mail config from database settings', function () {
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'mail_mailer',
        'value' => 'smtp',
        'label' => 'Mail Mailer',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'mail_host',
        'value' => 'smtp.test.com',
        'label' => 'Mail Host',
    ]);

    Cache::forget('environment_settings');

    $provider = new EnvironmentServiceProvider(app());
    $provider->boot();

    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.test.com');
});

it('overrides logging config from database settings', function () {
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
    $provider = new EnvironmentServiceProvider(app());
    $provider->boot();

    expect(config('logging.default'))->toBe('daily');
    expect(config('logging.channels.single.level'))->toBe('warning');
    expect(config('logging.channels.daily.level'))->toBe('warning');
});

it('overrides session config from database settings', function () {
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'session_lifetime',
        'value' => '60',
        'label' => 'Session Lifetime',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'session_encrypt',
        'value' => 'true',
        'label' => 'Session Encrypt',
    ]);

    Cache::forget('environment_settings');
    $provider = new EnvironmentServiceProvider(app());
    $provider->boot();

    expect(config('session.lifetime'))->toBe(60);
    expect(config('session.encrypt'))->toBeTrue();
});

it('overrides cors config with frontend url from database', function () {
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'frontend_url',
        'value' => 'https://frontend.example.com',
        'label' => 'Frontend URL',
    ]);

    Cache::forget('environment_settings');
    $provider = new EnvironmentServiceProvider(app());
    $provider->boot();

    expect(config('cors.allowed_origins'))->toBe(['https://frontend.example.com']);
});

it('does not fail when database is not available', function () {
    // Even if cache returns garbage, it should not throw
    Cache::put('environment_settings', 'invalid-data', 60);

    $provider = new EnvironmentServiceProvider(app());

    // Should not throw an exception
    expect(fn () => $provider->boot())->not->toThrow(\Throwable::class);
});

it('does not run when app is not installed', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    // Remove installed flag
    if ($wasInstalled) {
        unlink($installedPath);
    }

    // Set a DB setting
    DesignConfiguration::query()->create([
        'category' => 'environment_settings',
        'key' => 'mail_mailer',
        'value' => 'smtp',
        'label' => 'Mail Mailer',
    ]);

    Cache::forget('environment_settings');

    // Store original config
    $originalMailer = config('mail.default');

    // Boot provider - should skip because not installed
    $provider = new EnvironmentServiceProvider(app());
    $provider->boot();

    // Config should not have changed
    expect(config('mail.default'))->toBe($originalMailer);

    // Restore installed flag
    if ($wasInstalled) {
        file_put_contents($installedPath, now()->toIso8601String());
    }
});

<?php

use App\Filament\Pages\AppSettings;
use App\Models\DesignConfiguration;
use App\Models\User;
use Livewire\Livewire;

it('can render the app settings page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get('/admin/app-settings')
        ->assertSuccessful();
});

it('cannot access app settings page as non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/admin/app-settings')
        ->assertForbidden();
});

it('loads existing settings into the form', function () {
    $admin = User::factory()->admin()->create();

    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'app_name',
        'value' => 'Custom App',
        'label' => 'App Name',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'company_name',
        'value' => 'Custom Co',
        'label' => 'Company Name',
    ]);

    $this->actingAs($admin);

    Livewire::test(AppSettings::class)
        ->assertFormSet([
            'app_name' => 'Custom App',
            'company_name' => 'Custom Co',
        ]);
});

it('can save app settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(AppSettings::class)
        ->fillForm([
            'app_name' => 'New App Name',
            'company_name' => 'New Co',
            'primary_color' => '#ff5500',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(DesignConfiguration::query()
        ->where('category', 'app_settings')
        ->where('key', 'app_name')
        ->value('value')
    )->toBe('New App Name');

    expect(DesignConfiguration::query()
        ->where('category', 'app_settings')
        ->where('key', 'company_name')
        ->value('value')
    )->toBe('New Co');

    expect(DesignConfiguration::query()
        ->where('category', 'app_settings')
        ->where('key', 'primary_color')
        ->value('value')
    )->toBe('#ff5500');
});

it('validates required fields', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(AppSettings::class)
        ->fillForm([
            'app_name' => '',
            'company_name' => '',
            'primary_color' => '',
        ])
        ->call('save')
        ->assertHasFormErrors([
            'app_name' => 'required',
            'company_name' => 'required',
            'primary_color' => 'required',
        ]);
});

it('flushes cache after saving', function () {
    $admin = User::factory()->admin()->create();

    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'app_name',
        'value' => 'Cached Name',
        'label' => 'App Name',
    ]);

    $this->actingAs($admin);

    // Warm up the cache
    $service = app(\App\Services\AppSettingsService::class);
    expect($service->appName())->toBe('Cached Name');

    // Save new value via Filament page
    Livewire::test(AppSettings::class)
        ->fillForm([
            'app_name' => 'Fresh Name',
            'company_name' => 'Fresh Co',
            'primary_color' => '#3B82F6',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Cache should be flushed so new value is returned
    expect($service->appName())->toBe('Fresh Name');
});

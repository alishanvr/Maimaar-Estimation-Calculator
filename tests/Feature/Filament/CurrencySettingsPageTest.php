<?php

use App\Filament\Pages\CurrencySettings;
use App\Models\DesignConfiguration;
use App\Models\User;
use Livewire\Livewire;

it('can render the currency settings page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get('/admin/currency-settings')
        ->assertSuccessful();
});

it('cannot access currency settings page as non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/admin/currency-settings')
        ->assertForbidden();
});

it('loads existing currency settings into the form', function () {
    $admin = User::factory()->admin()->create();

    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'display_currency',
        'value' => 'USD',
        'label' => 'Display Currency',
    ]);

    $this->actingAs($admin);

    Livewire::test(CurrencySettings::class)
        ->assertFormSet([
            'display_currency' => 'USD',
        ]);
});

it('can save currency settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CurrencySettings::class)
        ->fillForm([
            'display_currency' => 'EUR',
            'manual_overrides' => ['USD' => '0.28'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(DesignConfiguration::query()
        ->where('category', 'currency_settings')
        ->where('key', 'display_currency')
        ->value('value')
    )->toBe('EUR');

    $overrides = json_decode(
        DesignConfiguration::query()
            ->where('category', 'currency_settings')
            ->where('key', 'manual_overrides')
            ->value('value'),
        true
    );

    expect($overrides)->toHaveKey('USD');
    expect($overrides['USD'])->toBe(0.28);
});

it('flushes currency cache after saving', function () {
    $admin = User::factory()->admin()->create();

    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'display_currency',
        'value' => 'USD',
        'label' => 'Display Currency',
    ]);

    $this->actingAs($admin);

    $service = app(\App\Services\CurrencyService::class);
    expect($service->getDisplayCurrency())->toBe('USD');

    Livewire::test(CurrencySettings::class)
        ->fillForm([
            'display_currency' => 'GBP',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Cache should be flushed
    expect($service->getDisplayCurrency())->toBe('GBP');
});

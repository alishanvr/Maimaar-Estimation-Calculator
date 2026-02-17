<?php

use App\Models\DesignConfiguration;
use Illuminate\Support\Facades\Http;

it('fetches and stores exchange rates via artisan command', function () {
    Http::fake([
        'open.er-api.com/*' => Http::response([
            'result' => 'success',
            'rates' => [
                'USD' => 0.2723,
                'EUR' => 0.2510,
            ],
        ], 200),
    ]);

    $this->artisan('currency:fetch-rates')
        ->expectsOutputToContain('Fetching latest exchange rates')
        ->expectsOutputToContain('updated successfully')
        ->assertSuccessful();

    $ratesJson = DesignConfiguration::query()
        ->where('category', 'currency_settings')
        ->where('key', 'exchange_rates')
        ->value('value');

    $rates = json_decode($ratesJson, true);
    expect($rates)->toHaveKey('USD');
    expect($rates['USD'])->toBe(0.2723);
});

it('handles api failure gracefully via artisan command', function () {
    Http::fake([
        'open.er-api.com/*' => Http::response([], 500),
    ]);

    $this->artisan('currency:fetch-rates')
        ->expectsOutputToContain('could not be fetched')
        ->assertSuccessful();
});

it('preserves manual overrides when fetching via command', function () {
    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'manual_overrides',
        'value' => json_encode(['USD' => 0.28]),
        'label' => 'Manual Overrides',
    ]);

    Http::fake([
        'open.er-api.com/*' => Http::response([
            'result' => 'success',
            'rates' => ['USD' => 0.2723, 'EUR' => 0.2510],
        ], 200),
    ]);

    $this->artisan('currency:fetch-rates')->assertSuccessful();

    // Manual overrides should still exist in DB
    $overridesJson = DesignConfiguration::query()
        ->where('category', 'currency_settings')
        ->where('key', 'manual_overrides')
        ->value('value');

    $overrides = json_decode($overridesJson, true);
    expect($overrides)->toHaveKey('USD');
    expect($overrides['USD'])->toBe(0.28);
});

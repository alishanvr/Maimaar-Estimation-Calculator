<?php

use App\Models\DesignConfiguration;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::forget('currency_settings');
});

it('returns AED as default display currency', function () {
    $service = app(CurrencyService::class);

    expect($service->getDisplayCurrency())->toBe('AED');
});

it('returns 1.0 exchange rate for AED', function () {
    $service = app(CurrencyService::class);

    expect($service->getExchangeRate('AED'))->toBe(1.0);
    expect($service->getExchangeRate())->toBe(1.0);
});

it('reads display currency from database', function () {
    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'display_currency',
        'value' => 'USD',
        'label' => 'Display Currency',
    ]);

    Cache::forget('currency_settings');
    $service = app(CurrencyService::class);

    expect($service->getDisplayCurrency())->toBe('USD');
});

it('reads exchange rates from database', function () {
    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'display_currency',
        'value' => 'USD',
        'label' => 'Display Currency',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'exchange_rates',
        'value' => json_encode(['USD' => 0.2723, 'EUR' => 0.2510]),
        'label' => 'Exchange Rates',
    ]);

    Cache::forget('currency_settings');
    $service = app(CurrencyService::class);

    expect($service->getExchangeRate())->toBe(0.2723);
    expect($service->getExchangeRate('EUR'))->toBe(0.2510);
});

it('gives manual overrides precedence over api rates', function () {
    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'exchange_rates',
        'value' => json_encode(['USD' => 0.2723]),
        'label' => 'Exchange Rates',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'manual_overrides',
        'value' => json_encode(['USD' => 0.28]),
        'label' => 'Manual Overrides',
    ]);

    Cache::forget('currency_settings');
    $service = app(CurrencyService::class);

    expect($service->getExchangeRate('USD'))->toBe(0.28);
});

it('converts amounts correctly', function () {
    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'display_currency',
        'value' => 'USD',
        'label' => 'Display Currency',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'exchange_rates',
        'value' => json_encode(['USD' => 0.25]),
        'label' => 'Exchange Rates',
    ]);

    Cache::forget('currency_settings');
    $service = app(CurrencyService::class);

    expect($service->convert(100.0))->toBe(25.0);
    expect($service->convert(100.0, 'AED'))->toBe(100.0);
});

it('formats amounts with currency symbol', function () {
    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'display_currency',
        'value' => 'USD',
        'label' => 'Display Currency',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'exchange_rates',
        'value' => json_encode(['USD' => 0.25]),
        'label' => 'Exchange Rates',
    ]);

    Cache::forget('currency_settings');
    $service = app(CurrencyService::class);

    expect($service->format(1000.0))->toBe('250.00 USD');
    expect($service->format(1000.0, 'AED'))->toBe('1,000.00 AED');
});

it('returns supported currencies list', function () {
    $currencies = CurrencyService::supportedCurrencies();

    expect($currencies)->toBeArray()
        ->toHaveKey('AED')
        ->toHaveKey('USD')
        ->toHaveKey('EUR')
        ->toHaveKey('GBP');
});

it('returns correct currency symbol', function () {
    $service = app(CurrencyService::class);

    expect($service->getCurrencySymbol('AED'))->toBe('AED');
    expect($service->getCurrencySymbol('USD'))->toBe('USD');
});

it('flushes cache correctly', function () {
    DesignConfiguration::query()->create([
        'category' => 'currency_settings',
        'key' => 'display_currency',
        'value' => 'USD',
        'label' => 'Display Currency',
    ]);

    $service = app(CurrencyService::class);

    // Warm cache
    expect($service->getDisplayCurrency())->toBe('USD');

    // Update DB directly
    DesignConfiguration::query()
        ->where('category', 'currency_settings')
        ->where('key', 'display_currency')
        ->update(['value' => 'EUR']);

    // Cache still returns old value
    expect($service->getDisplayCurrency())->toBe('USD');

    // After flush, returns new value
    $service->flushCache();
    expect($service->getDisplayCurrency())->toBe('EUR');
});

it('fetches and stores rates from api', function () {
    Http::fake([
        'open.er-api.com/*' => Http::response([
            'result' => 'success',
            'rates' => [
                'USD' => 0.2723,
                'EUR' => 0.2510,
                'GBP' => 0.2150,
                'INR' => 22.75,
                'XYZ' => 1.5, // Unsupported currency — should be filtered out
            ],
        ], 200),
    ]);

    $service = app(CurrencyService::class);
    $service->fetchAndStoreRates();

    // Re-read from database
    $service->flushCache();

    $rates = $service->getExchangeRates();
    expect($rates)->toHaveKey('USD')
        ->toHaveKey('EUR')
        ->toHaveKey('GBP')
        ->toHaveKey('INR')
        ->not->toHaveKey('XYZ')
        ->not->toHaveKey('AED');

    expect($rates['USD'])->toBe(0.2723);
    expect($service->getRatesLastUpdated())->not->toBeNull();
});

it('preserves manual overrides when fetching api rates', function () {
    // Set manual override first
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

    $service = app(CurrencyService::class);
    $service->fetchAndStoreRates();
    $service->flushCache();

    // Manual override should still take precedence
    expect($service->getExchangeRate('USD'))->toBe(0.28);
    // API rate should be available for EUR
    expect($service->getExchangeRate('EUR'))->toBe(0.2510);
});

it('handles api failure gracefully', function () {
    Http::fake([
        'open.er-api.com/*' => Http::response([], 500),
    ]);

    $service = app(CurrencyService::class);
    $service->fetchAndStoreRates();

    // Should not throw, and rates_last_updated should remain null
    expect($service->getRatesLastUpdated())->toBeNull();
});

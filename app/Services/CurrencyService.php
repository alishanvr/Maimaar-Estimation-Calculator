<?php

namespace App\Services;

use App\Models\DesignConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    private const CACHE_KEY = 'currency_settings';

    private const CACHE_TTL = 86400;

    private const CATEGORY = 'currency_settings';

    private const API_URL = 'https://open.er-api.com/v6/latest/AED';

    /**
     * @var array<string, string>
     */
    private const CURRENCY_SYMBOLS = [
        'AED' => 'AED',
        'USD' => 'USD',
        'EUR' => 'EUR',
        'GBP' => 'GBP',
        'SAR' => 'SAR',
        'INR' => 'INR',
        'PKR' => 'PKR',
        'BHD' => 'BHD',
        'OMR' => 'OMR',
        'QAR' => 'QAR',
        'KWD' => 'KWD',
        'EGP' => 'EGP',
    ];

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return DesignConfiguration::query()
                ->byCategory(self::CATEGORY)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->all()[$key] ?? $default;
    }

    public function getDisplayCurrency(): string
    {
        return $this->get('display_currency', 'AED');
    }

    /**
     * @return array<string, float>
     */
    public function getExchangeRates(): array
    {
        $apiRates = json_decode($this->get('exchange_rates', '{}'), true) ?: [];
        $manualOverrides = json_decode($this->get('manual_overrides', '{}'), true) ?: [];

        return array_merge($apiRates, $manualOverrides);
    }

    public function getExchangeRate(?string $currency = null): float
    {
        $currency ??= $this->getDisplayCurrency();

        if ($currency === 'AED') {
            return 1.0;
        }

        $rates = $this->getExchangeRates();

        return (float) ($rates[$currency] ?? 1.0);
    }

    public function convert(float $amountAed, ?string $toCurrency = null): float
    {
        return $amountAed * $this->getExchangeRate($toCurrency);
    }

    public function format(float $amountAed, ?string $toCurrency = null, int $decimals = 2): string
    {
        $converted = $this->convert($amountAed, $toCurrency);

        return number_format($converted, $decimals).' '.$this->getCurrencySymbol($toCurrency);
    }

    public function getCurrencySymbol(?string $code = null): string
    {
        $code ??= $this->getDisplayCurrency();

        return self::CURRENCY_SYMBOLS[$code] ?? $code;
    }

    /**
     * @return array<string, string>
     */
    public static function supportedCurrencies(): array
    {
        return self::CURRENCY_SYMBOLS;
    }

    public function getRatesLastUpdated(): ?string
    {
        return $this->get('rates_last_updated') ?: null;
    }

    public function fetchAndStoreRates(): void
    {
        try {
            $response = Http::timeout(15)->get(self::API_URL);

            if (! $response->successful()) {
                Log::warning('Currency API request failed', [
                    'status' => $response->status(),
                ]);

                return;
            }

            $data = $response->json();

            if (($data['result'] ?? '') !== 'success') {
                Log::warning('Currency API returned non-success result', [
                    'result' => $data['result'] ?? 'unknown',
                ]);

                return;
            }

            $apiRates = $data['rates'] ?? [];
            $supportedCodes = array_keys(self::CURRENCY_SYMBOLS);
            $filteredRates = [];

            foreach ($supportedCodes as $code) {
                if ($code !== 'AED' && isset($apiRates[$code])) {
                    $filteredRates[$code] = (float) $apiRates[$code];
                }
            }

            $this->saveSetting('exchange_rates', json_encode($filteredRates));
            $this->saveSetting('rates_last_updated', now()->toIso8601String());

            $this->flushCache();

            Log::info('Exchange rates updated successfully', [
                'currencies' => count($filteredRates),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch exchange rates', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function saveSetting(string $key, string $value): void
    {
        DesignConfiguration::query()->updateOrCreate(
            ['category' => self::CATEGORY, 'key' => $key],
            [
                'value' => $value,
                'label' => str($key)->replace('_', ' ')->title()->toString(),
            ],
        );
    }
}

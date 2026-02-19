<?php

namespace App\Http\Middleware;

use App\Services\AppSettingsService;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'inertia';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'company_name' => $request->user()->company_name,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'appSettings' => function () {
                $appSettings = app(AppSettingsService::class);
                $currency = app(CurrencyService::class);

                return [
                    'app_name' => $appSettings->appName(),
                    'company_name' => $appSettings->companyName(),
                    'logo_url' => $appSettings->logoUrl(),
                    'favicon_url' => $appSettings->faviconUrl(),
                    'primary_color' => $appSettings->primaryColor(),
                    'enable_fill_test_data' => $appSettings->enableFillTestData(),
                    'display_currency' => $currency->getDisplayCurrency(),
                    'currency_symbol' => $currency->getCurrencySymbol(),
                    'exchange_rate' => $currency->getExchangeRate(),
                ];
            },
        ];
    }
}

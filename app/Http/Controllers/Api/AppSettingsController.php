<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppSettingsService;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppSettingsController extends Controller
{
    public function __invoke(Request $request, AppSettingsService $appSettings, CurrencyService $currencyService): JsonResponse
    {
        return response()->json([
            'app_name' => $appSettings->appName(),
            'company_name' => $appSettings->companyName(),
            'logo_url' => $appSettings->logoUrl(),
            'favicon_url' => $appSettings->faviconUrl(),
            'primary_color' => $appSettings->primaryColor(),
            'enable_fill_test_data' => $appSettings->enableFillTestData(),
            'display_currency' => $currencyService->getDisplayCurrency(),
            'currency_symbol' => $currencyService->getCurrencySymbol(),
            'exchange_rate' => $currencyService->getExchangeRate(),
        ]);
    }
}

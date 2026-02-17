<?php

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

class FetchExchangeRates extends Command
{
    protected $signature = 'currency:fetch-rates';

    protected $description = 'Fetch latest exchange rates from the API and store them';

    public function handle(CurrencyService $currencyService): int
    {
        $this->info('Fetching latest exchange rates...');

        $currencyService->fetchAndStoreRates();

        $lastUpdated = $currencyService->getRatesLastUpdated();

        if ($lastUpdated) {
            $this->info("Exchange rates updated successfully at {$lastUpdated}.");
        } else {
            $this->warn('Exchange rates could not be fetched. Check the logs for details.');
        }

        return self::SUCCESS;
    }
}

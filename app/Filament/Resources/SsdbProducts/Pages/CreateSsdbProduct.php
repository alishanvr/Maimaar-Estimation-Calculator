<?php

namespace App\Filament\Resources\SsdbProducts\Pages;

use App\Filament\Resources\SsdbProducts\SsdbProductResource;
use App\Services\Estimation\CachingService;
use Filament\Resources\Pages\CreateRecord;

class CreateSsdbProduct extends CreateRecord
{
    protected static string $resource = SsdbProductResource::class;

    protected function afterCreate(): void
    {
        app(CachingService::class)->clearReferenceCache();
    }
}

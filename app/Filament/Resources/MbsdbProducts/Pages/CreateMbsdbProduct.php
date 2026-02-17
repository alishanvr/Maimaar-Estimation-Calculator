<?php

namespace App\Filament\Resources\MbsdbProducts\Pages;

use App\Filament\Resources\MbsdbProducts\MbsdbProductResource;
use App\Services\Estimation\CachingService;
use Filament\Resources\Pages\CreateRecord;

class CreateMbsdbProduct extends CreateRecord
{
    protected static string $resource = MbsdbProductResource::class;

    protected function afterCreate(): void
    {
        app(CachingService::class)->clearReferenceCache();
    }
}

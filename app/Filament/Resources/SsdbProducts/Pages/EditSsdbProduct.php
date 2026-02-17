<?php

namespace App\Filament\Resources\SsdbProducts\Pages;

use App\Filament\Resources\SsdbProducts\SsdbProductResource;
use App\Services\Estimation\CachingService;
use Filament\Resources\Pages\EditRecord;

class EditSsdbProduct extends EditRecord
{
    protected static string $resource = SsdbProductResource::class;

    protected function afterSave(): void
    {
        app(CachingService::class)->clearReferenceCache();
    }
}

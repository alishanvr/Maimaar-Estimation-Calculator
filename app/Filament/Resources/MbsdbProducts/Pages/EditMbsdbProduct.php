<?php

namespace App\Filament\Resources\MbsdbProducts\Pages;

use App\Filament\Resources\MbsdbProducts\MbsdbProductResource;
use App\Services\Estimation\CachingService;
use Filament\Resources\Pages\EditRecord;

class EditMbsdbProduct extends EditRecord
{
    protected static string $resource = MbsdbProductResource::class;

    protected function afterSave(): void
    {
        app(CachingService::class)->clearReferenceCache();
    }
}

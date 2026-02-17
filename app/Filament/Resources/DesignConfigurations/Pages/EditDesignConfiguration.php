<?php

namespace App\Filament\Resources\DesignConfigurations\Pages;

use App\Filament\Resources\DesignConfigurations\DesignConfigurationResource;
use App\Services\Estimation\CachingService;
use Filament\Resources\Pages\EditRecord;

class EditDesignConfiguration extends EditRecord
{
    protected static string $resource = DesignConfigurationResource::class;

    protected function afterSave(): void
    {
        app(CachingService::class)->clearReferenceCache();
    }
}

<?php

namespace App\Filament\Resources\DesignConfigurations\Pages;

use App\Filament\Resources\DesignConfigurations\DesignConfigurationResource;
use App\Services\Estimation\CachingService;
use Filament\Resources\Pages\CreateRecord;

class CreateDesignConfiguration extends CreateRecord
{
    protected static string $resource = DesignConfigurationResource::class;

    protected function afterCreate(): void
    {
        app(CachingService::class)->clearReferenceCache();
    }
}

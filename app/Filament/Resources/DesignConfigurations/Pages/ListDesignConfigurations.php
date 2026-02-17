<?php

namespace App\Filament\Resources\DesignConfigurations\Pages;

use App\Filament\Resources\DesignConfigurations\DesignConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListDesignConfigurations extends ListRecords
{
    protected static string $resource = DesignConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Dropdown options and configuration values used throughout the estimation input form. '
            .'Categories include frame types, panel profiles, base types, finishes, insulation options, and more. '
            .'Grouped by category — each entry maps a key/value pair to a display label.';
    }
}

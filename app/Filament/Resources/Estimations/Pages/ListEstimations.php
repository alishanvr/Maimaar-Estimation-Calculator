<?php

namespace App\Filament\Resources\Estimations\Pages;

use App\Filament\Resources\Estimations\EstimationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEstimations extends ListRecords
{
    protected static string $resource = EstimationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

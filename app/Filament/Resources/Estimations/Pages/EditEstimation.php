<?php

namespace App\Filament\Resources\Estimations\Pages;

use App\Filament\Resources\Estimations\EstimationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEstimation extends EditRecord
{
    protected static string $resource = EstimationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

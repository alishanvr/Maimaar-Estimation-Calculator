<?php

namespace App\Filament\Resources\Estimations\Pages;

use App\Filament\Resources\Estimations\EstimationResource;
use Filament\Resources\Pages\ListRecords;

class ListEstimations extends ListRecords
{
    protected static string $resource = EstimationResource::class;
}

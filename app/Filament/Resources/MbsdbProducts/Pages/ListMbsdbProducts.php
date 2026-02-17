<?php

namespace App\Filament\Resources\MbsdbProducts\Pages;

use App\Filament\Resources\MbsdbProducts\MbsdbProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListMbsdbProducts extends ListRecords
{
    protected static string $resource = MbsdbProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Metal Building System Database — Primary product catalog used in estimation calculations. '
            .'Contains manufactured items: purlins, girts, panels, sheeting, fasteners, trims, and accessories. '
            .'Each product has a code, rate, and rate type (priced per kg, m², or unit).';
    }
}

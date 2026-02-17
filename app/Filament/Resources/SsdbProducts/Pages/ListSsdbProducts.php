<?php

namespace App\Filament\Resources\SsdbProducts\Pages;

use App\Filament\Resources\SsdbProducts\SsdbProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSsdbProducts extends ListRecords
{
    protected static string $resource = SsdbProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Structural Steel Database — Standard structural steel sections sourced from suppliers. '
            .'Contains hot-rolled profiles, tubes, plates, trusses, and handrails with grade classification (Light, Medium, Heavy). '
            .'Tracks both material cost and manufacturing cost separately.';
    }
}

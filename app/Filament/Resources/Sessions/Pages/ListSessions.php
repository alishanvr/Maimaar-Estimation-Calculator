<?php

namespace App\Filament\Resources\Sessions\Pages;

use App\Filament\Resources\Sessions\SessionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSessions extends ListRecords
{
    protected static string $resource = SessionResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Active user sessions stored in the database. '
            .'Terminating a session forces the user to log in again.';
    }
}

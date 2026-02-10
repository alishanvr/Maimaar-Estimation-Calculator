<?php

namespace App\Filament\Resources\Estimations\Schemas;

use App\Models\Estimation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EstimationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('quote_number')
                    ->placeholder('-'),
                TextEntry::make('revision_no')
                    ->placeholder('-'),
                TextEntry::make('building_name')
                    ->placeholder('-'),
                TextEntry::make('building_no')
                    ->placeholder('-'),
                TextEntry::make('project_name')
                    ->placeholder('-'),
                TextEntry::make('customer_name')
                    ->placeholder('-'),
                TextEntry::make('salesperson_code')
                    ->placeholder('-'),
                TextEntry::make('estimation_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('input_data')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('results_data')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('total_weight_mt')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('total_price_aed')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Estimation $record): bool => $record->trashed()),
            ]);
    }
}

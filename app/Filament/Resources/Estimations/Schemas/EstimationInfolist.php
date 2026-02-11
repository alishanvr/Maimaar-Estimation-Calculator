<?php

namespace App\Filament\Resources\Estimations\Schemas;

use App\Models\Estimation;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EstimationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project Information')
                    ->icon('heroicon-o-building-office')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Created By'),
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
                    ]),

                Section::make('Calculation Results')
                    ->icon('heroicon-o-calculator')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'calculated' => 'success',
                                'finalized' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('total_weight_mt')
                            ->label('Total Weight (MT)')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder('-'),
                        TextEntry::make('total_price_aed')
                            ->label('Total Price (AED)')
                            ->numeric(decimalPlaces: 0)
                            ->placeholder('-'),
                    ]),

                Section::make('Input Data')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        KeyValueEntry::make('input_data')
                            ->label('')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->placeholder('No input data provided.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Metadata')
                    ->icon('heroicon-o-clock')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->visible(fn (Estimation $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}

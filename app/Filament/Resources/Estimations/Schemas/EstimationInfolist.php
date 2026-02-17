<?php

namespace App\Filament\Resources\Estimations\Schemas;

use App\Models\Estimation;
use App\Services\CurrencyService;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EstimationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $cur = app(CurrencyService::class)->getDisplayCurrency();
        $xr = app(CurrencyService::class)->getExchangeRate();

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
                    ->columns(4)
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
                            ->numeric(decimalPlaces: 4)
                            ->placeholder('-'),
                        TextEntry::make('total_price_aed')
                            ->label("Total Price ({$cur})")
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state * $xr, 2) : null)
                            ->placeholder('-'),
                        TextEntry::make('results_data.summary.price_per_mt')
                            ->label("Price/MT ({$cur})")
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state * $xr, 2) : null)
                            ->placeholder('-'),
                        TextEntry::make('results_data.summary.fob_price_aed')
                            ->label("FOB Price ({$cur})")
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state * $xr, 2) : null)
                            ->placeholder('-'),
                        TextEntry::make('results_data.summary.steel_weight_kg')
                            ->label('Steel Weight (kg)')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder('-'),
                        TextEntry::make('results_data.summary.panels_weight_kg')
                            ->label('Panels Weight (kg)')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder('-'),
                    ]),

                Section::make('FCPBS — Cost & Price Breakdown')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed()
                    ->visible(fn (Estimation $record): bool => ! empty($record->results_data['fcpbs']['categories'] ?? []))
                    ->schema([
                        RepeatableEntry::make('fcpbs_categories_list')
                            ->label('')
                            ->table([
                                TableColumn::make('Category'),
                                TableColumn::make('Name'),
                                TableColumn::make('Weight (kg)'),
                                TableColumn::make('Weight %'),
                                TableColumn::make('Total Cost'),
                                TableColumn::make('Selling Price'),
                                TableColumn::make('Price/MT'),
                            ])
                            ->schema([
                                TextEntry::make('key'),
                                TextEntry::make('name'),
                                TextEntry::make('weight_kg')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('weight_pct')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('total_cost')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('selling_price')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('price_per_mt')
                                    ->numeric(decimalPlaces: 2),
                            ]),
                    ]),

                Section::make('BOQ — Bill of Quantities')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->visible(fn (Estimation $record): bool => ! empty($record->results_data['boq']['items'] ?? []))
                    ->schema([
                        RepeatableEntry::make('results_data.boq.items')
                            ->label('')
                            ->table([
                                TableColumn::make('Sl No'),
                                TableColumn::make('Description'),
                                TableColumn::make('Unit'),
                                TableColumn::make('Quantity'),
                                TableColumn::make('Unit Rate'),
                                TableColumn::make('Total Price'),
                            ])
                            ->schema([
                                TextEntry::make('sl_no'),
                                TextEntry::make('description'),
                                TextEntry::make('unit'),
                                TextEntry::make('quantity')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('unit_rate')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('total_price')
                                    ->numeric(decimalPlaces: 2),
                            ]),
                    ]),

                Section::make('SAL — Sales Summary')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed()
                    ->visible(fn (Estimation $record): bool => ! empty($record->results_data['sal']['lines'] ?? []))
                    ->schema([
                        RepeatableEntry::make('results_data.sal.lines')
                            ->label('')
                            ->table([
                                TableColumn::make('Code'),
                                TableColumn::make('Description'),
                                TableColumn::make('Weight (kg)'),
                                TableColumn::make('Cost'),
                                TableColumn::make('Price'),
                                TableColumn::make('Price/MT'),
                            ])
                            ->schema([
                                TextEntry::make('code'),
                                TextEntry::make('description'),
                                TextEntry::make('weight_kg')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('cost')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('price')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('price_per_mt')
                                    ->numeric(decimalPlaces: 2),
                            ]),
                    ]),

                Section::make('RAWMAT — Raw Materials')
                    ->icon('heroicon-o-cube')
                    ->collapsed()
                    ->visible(fn (Estimation $record): bool => ! empty($record->results_data['rawmat']['items'] ?? []))
                    ->schema([
                        RepeatableEntry::make('results_data.rawmat.items')
                            ->label('')
                            ->table([
                                TableColumn::make('No'),
                                TableColumn::make('Code'),
                                TableColumn::make('Description'),
                                TableColumn::make('Unit'),
                                TableColumn::make('Quantity'),
                                TableColumn::make('Unit Weight'),
                                TableColumn::make('Total Weight'),
                                TableColumn::make('Category'),
                            ])
                            ->schema([
                                TextEntry::make('no'),
                                TextEntry::make('code'),
                                TextEntry::make('description'),
                                TextEntry::make('unit'),
                                TextEntry::make('quantity')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('unit_weight')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('total_weight')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('category'),
                            ]),
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

<?php

namespace App\Filament\Resources\RawMaterials\Tables;

use App\Services\Estimation\CachingService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RawMaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('weight_per_sqm')
                    ->label('Weight/sqm')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('unit')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->after(fn () => app(CachingService::class)->clearReferenceCache()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(fn () => app(CachingService::class)->clearReferenceCache()),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\MbsdbProducts\Tables;

use App\Models\MbsdbProduct;
use App\Services\Estimation\CachingService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MbsdbProductsTable
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
                    ->limit(40),
                TextColumn::make('category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit')
                    ->sortable(),
                TextColumn::make('rate')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('rate_type')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => MbsdbProduct::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->all()),
                SelectFilter::make('rate_type')
                    ->options(fn (): array => MbsdbProduct::query()
                        ->whereNotNull('rate_type')
                        ->distinct()
                        ->pluck('rate_type', 'rate_type')
                        ->all()),
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

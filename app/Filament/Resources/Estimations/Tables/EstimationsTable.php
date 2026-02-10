<?php

namespace App\Filament\Resources\Estimations\Tables;

use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EstimationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('building_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'calculated' => 'success',
                        default => 'info',
                    })
                    ->sortable(),
                TextColumn::make('total_weight_mt')
                    ->label('Weight (MT)')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('total_price_aed')
                    ->label('Price (AED)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'calculated' => 'Calculated',
                    ]),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn (): array => User::query()->pluck('name', 'id')->all()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}

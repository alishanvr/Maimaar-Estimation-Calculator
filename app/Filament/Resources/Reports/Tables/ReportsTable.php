<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('estimation.quote_number')
                    ->label('Estimation')
                    ->default('-')
                    ->searchable(),
                TextColumn::make('report_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pdf' => 'info',
                        'csv' => 'success',
                        'zip' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('sheet_name')
                    ->label('Sheet')
                    ->sortable(),
                TextColumn::make('filename')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024).' KB' : '-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('report_type')
                    ->label('Type')
                    ->options([
                        'pdf' => 'PDF',
                        'csv' => 'CSV',
                        'zip' => 'ZIP',
                    ]),
                SelectFilter::make('sheet_name')
                    ->label('Sheet')
                    ->options([
                        'boq' => 'BOQ',
                        'jaf' => 'JAF',
                        'recap' => 'Recap',
                        'detail' => 'Detail',
                        'fcpbs' => 'FCPBS',
                        'sal' => 'SAL',
                        'rawmat' => 'RAWMAT',
                        'dashboard' => 'Dashboard',
                        'bulk' => 'Bulk',
                    ]),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn (): array => User::query()->pluck('name', 'id')->all()),
            ]);
    }
}

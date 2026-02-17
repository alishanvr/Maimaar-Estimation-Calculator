<?php

namespace App\Filament\Resources\Sessions\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class SessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_activity', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->default('Guest')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->default('-')
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_activity')
                    ->label('Last Active')
                    ->formatStateUsing(fn (int $state): string => Carbon::createFromTimestamp($state)->diffForHumans())
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn (): array => User::query()->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Terminate'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Terminate Selected'),
                ]),
            ]);
    }
}

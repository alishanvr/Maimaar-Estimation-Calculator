<?php

namespace App\Filament\Resources\Estimations\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('item_code')
                    ->maxLength(100),
                TextInput::make('description')
                    ->required()
                    ->maxLength(500),
                TextInput::make('unit')
                    ->maxLength(20),
                TextInput::make('category')
                    ->maxLength(50),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(0),
                TextInput::make('weight_kg')
                    ->label('Weight (kg)')
                    ->numeric()
                    ->default(0),
                TextInput::make('rate')
                    ->numeric()
                    ->default(0),
                TextInput::make('amount')
                    ->numeric()
                    ->default(0),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                KeyValue::make('metadata')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('item_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('unit')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('weight_kg')
                    ->label('Weight (kg)')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('rate')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('amount')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('category')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\RawMaterials\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RawMaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                TextInput::make('description')
                    ->required()
                    ->maxLength(500),
                TextInput::make('weight_per_sqm')
                    ->label('Weight per sqm')
                    ->numeric()
                    ->required(),
                TextInput::make('unit')
                    ->maxLength(20),
                KeyValue::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}

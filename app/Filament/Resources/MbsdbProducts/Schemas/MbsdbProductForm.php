<?php

namespace App\Filament\Resources\MbsdbProducts\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MbsdbProductForm
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
                TextInput::make('unit')
                    ->maxLength(20),
                TextInput::make('category')
                    ->maxLength(100),
                TextInput::make('rate')
                    ->numeric()
                    ->required(),
                TextInput::make('rate_type')
                    ->maxLength(50),
                KeyValue::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}

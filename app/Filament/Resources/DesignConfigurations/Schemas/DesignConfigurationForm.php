<?php

namespace App\Filament\Resources\DesignConfigurations\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DesignConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('category')
                    ->required()
                    ->maxLength(100),
                TextInput::make('key')
                    ->required()
                    ->maxLength(100),
                TextInput::make('value')
                    ->required()
                    ->maxLength(200),
                TextInput::make('label')
                    ->required()
                    ->maxLength(200),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                KeyValue::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}

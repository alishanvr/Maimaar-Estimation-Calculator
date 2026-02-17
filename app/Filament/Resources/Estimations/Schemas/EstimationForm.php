<?php

namespace App\Filament\Resources\Estimations\Schemas;

use App\Models\Project;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EstimationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Owner')
                    ->options(fn (): array => User::query()->pluck('name', 'id')->all())
                    ->required()
                    ->searchable()
                    ->default(fn (): ?int => auth()->id()),
                Select::make('project_id')
                    ->label('Project')
                    ->options(fn (): array => Project::query()->pluck('project_name', 'id')->all())
                    ->searchable()
                    ->placeholder('None'),
                TextInput::make('quote_number')
                    ->maxLength(50),
                TextInput::make('revision_no')
                    ->maxLength(10),
                TextInput::make('building_name')
                    ->maxLength(200),
                TextInput::make('building_no')
                    ->maxLength(50),
                TextInput::make('project_name')
                    ->maxLength(200),
                TextInput::make('customer_name')
                    ->maxLength(200),
                TextInput::make('salesperson_code')
                    ->maxLength(50),
                DatePicker::make('estimation_date'),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'calculated' => 'Calculated',
                        'finalized' => 'Finalized',
                    ])
                    ->required()
                    ->default('draft'),
            ]);
    }
}

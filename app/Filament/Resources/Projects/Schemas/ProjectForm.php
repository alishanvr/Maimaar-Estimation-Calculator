<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('project_number')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('project_name')
                    ->required()
                    ->maxLength(200),
                TextInput::make('customer_name')
                    ->maxLength(200),
                TextInput::make('location')
                    ->maxLength(200),
                Select::make('user_id')
                    ->label('Owner')
                    ->options(fn (): array => User::query()->pluck('name', 'id')->all())
                    ->required()
                    ->searchable()
                    ->default(fn (): ?int => auth()->id()),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                    ])
                    ->required()
                    ->default('draft'),
                Textarea::make('description')
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project Information')
                    ->icon('heroicon-o-building-office-2')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('project_number'),
                        TextEntry::make('project_name'),
                        TextEntry::make('customer_name')
                            ->placeholder('-'),
                        TextEntry::make('location')
                            ->placeholder('-'),
                        TextEntry::make('user.name')
                            ->label('Owner'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'in_progress' => 'warning',
                                'completed' => 'success',
                                'archived' => 'info',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Description')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('description')
                            ->label('')
                            ->placeholder('No description provided.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Metadata')
                    ->icon('heroicon-o-clock')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->visible(fn (Project $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}

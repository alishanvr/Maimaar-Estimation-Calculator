<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->default('-'),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->sortable(),
                TextColumn::make('properties')
                    ->label('Changes')
                    ->formatStateUsing(function (mixed $state): string {
                        if (empty($state)) {
                            return '-';
                        }

                        $data = is_array($state) ? $state : (array) $state;
                        $attributes = $data['attributes'] ?? [];
                        $old = $data['old'] ?? [];

                        if (empty($attributes)) {
                            return '-';
                        }

                        $parts = [];
                        foreach ($attributes as $key => $value) {
                            $oldValue = $old[$key] ?? 'null';
                            $parts[] = "{$key}: {$oldValue} → {$value}";
                        }

                        return implode(', ', $parts);
                    })
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}

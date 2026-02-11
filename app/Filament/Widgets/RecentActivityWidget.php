<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Spatie\Activitylog\Models\Activity;

class RecentActivityWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Activity')
            ->query(
                Activity::query()->latest()->limit(10)
            )
            ->columns([
                TextColumn::make('description')
                    ->label('Action')
                    ->limit(50),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->placeholder('-'),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}

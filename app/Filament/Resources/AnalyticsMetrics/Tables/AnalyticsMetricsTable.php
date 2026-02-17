<?php

namespace App\Filament\Resources\AnalyticsMetrics\Tables;

use App\Models\AnalyticsMetric;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnalyticsMetricsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->default('System-wide')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('metric_name')
                    ->label('Metric')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'monthly_estimations' => 'Monthly Estimations',
                        'total_weight' => 'Total Weight (MT)',
                        'total_revenue' => 'Total Revenue (AED)',
                        'avg_price_per_mt' => 'Avg Price/MT',
                        default => $state,
                    }),
                TextColumn::make('metric_value')
                    ->label('Value')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('period')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('metric_name')
                    ->label('Metric')
                    ->options([
                        'monthly_estimations' => 'Monthly Estimations',
                        'total_weight' => 'Total Weight',
                        'total_revenue' => 'Total Revenue',
                        'avg_price_per_mt' => 'Avg Price/MT',
                    ]),
                SelectFilter::make('period')
                    ->label('Period')
                    ->options(fn (): array => AnalyticsMetric::query()
                        ->distinct()
                        ->orderByDesc('period')
                        ->pluck('period', 'period')
                        ->all()),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn (): array => array_merge(
                        ['' => 'System-wide'],
                        User::query()->pluck('name', 'id')->all()
                    )),
            ]);
    }
}

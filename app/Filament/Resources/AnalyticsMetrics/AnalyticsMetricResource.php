<?php

namespace App\Filament\Resources\AnalyticsMetrics;

use App\Filament\Resources\AnalyticsMetrics\Pages\ListAnalyticsMetrics;
use App\Filament\Resources\AnalyticsMetrics\Tables\AnalyticsMetricsTable;
use App\Models\AnalyticsMetric;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AnalyticsMetricResource extends Resource
{
    protected static ?string $model = AnalyticsMetric::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Activity & Logs';

    protected static ?string $navigationLabel = 'Analytics Metrics';

    protected static ?string $modelLabel = 'Analytics Metric';

    protected static ?string $pluralModelLabel = 'Analytics Metrics';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return AnalyticsMetricsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnalyticsMetrics::route('/'),
        ];
    }
}

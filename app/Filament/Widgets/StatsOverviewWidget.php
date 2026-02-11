<?php

namespace App\Filament\Widgets;

use App\Models\Estimation;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();

        $totalEstimations = Estimation::count();
        $calculatedEstimations = Estimation::where('status', 'calculated')->count();

        $estimationsToday = Estimation::whereDate('created_at', today())->count();

        $totalValue = Estimation::where('status', 'calculated')->sum('total_price_aed');

        return [
            Stat::make('Total Users', (string) $totalUsers)
                ->description("{$activeUsers} active")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Total Estimations', (string) $totalEstimations)
                ->description("{$calculatedEstimations} calculated")
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),

            Stat::make('Estimations Today', (string) $estimationsToday)
                ->description('Created today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Total Value', number_format($totalValue, 0).' AED')
                ->description('All calculated estimations')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}

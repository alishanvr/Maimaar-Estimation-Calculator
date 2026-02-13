<?php

namespace App\Filament\Widgets;

use App\Models\Estimation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EstimationValueTrendsWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Estimation Value Trends';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = collect();
        $totals = collect();
        $averages = collect();

        for ($i = 5; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i)->startOfMonth();
            $end = Carbon::now()->subMonths($i)->endOfMonth();

            $result = Estimation::query()
                ->whereIn('status', ['calculated', 'finalized'])
                ->whereBetween('created_at', [$start, $end])
                ->select([
                    DB::raw('COALESCE(SUM(total_price_aed), 0) as total'),
                    DB::raw('COALESCE(AVG(total_price_aed), 0) as average'),
                ])
                ->first();

            $months->push($start->format('M Y'));
            $totals->push(round((float) $result->total, 0));
            $averages->push(round((float) $result->average, 0));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Value (AED)',
                    'data' => $totals->toArray(),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.6)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 1,
                    'order' => 2,
                ],
                [
                    'label' => 'Avg Value (AED)',
                    'data' => $averages->toArray(),
                    'type' => 'line',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'order' => 1,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }
}

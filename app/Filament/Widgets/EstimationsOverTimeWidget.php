<?php

namespace App\Filament\Widgets;

use App\Models\Estimation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class EstimationsOverTimeWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Estimations Over Time';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $weeks = collect();
        $counts = collect();

        for ($i = 11; $i >= 0; $i--) {
            $start = Carbon::now()->subWeeks($i)->startOfWeek();
            $end = Carbon::now()->subWeeks($i)->endOfWeek();

            $count = Estimation::query()
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $weeks->push($start->format('M d'));
            $counts->push($count);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Estimations Created',
                    'data' => $counts->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $weeks->toArray(),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Estimation;
use Filament\Widgets\ChartWidget;

class EstimationsByStatusWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Estimations by Status';

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $draft = Estimation::where('status', 'draft')->count();
        $calculated = Estimation::where('status', 'calculated')->count();
        $finalized = Estimation::where('status', 'finalized')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Estimations',
                    'data' => [$draft, $calculated, $finalized],
                    'backgroundColor' => [
                        'rgb(156, 163, 175)', // gray for draft
                        'rgb(34, 197, 94)',   // green for calculated
                        'rgb(59, 130, 246)',  // blue for finalized
                    ],
                ],
            ],
            'labels' => ['Draft', 'Calculated', 'Finalized'],
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Estimation;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserActivityWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Estimations by User (Top 10)';

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $results = Estimation::query()
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $userIds = $results->pluck('user_id');
        $users = User::query()
            ->whereIn('id', $userIds)
            ->pluck('name', 'id');

        $labels = $results->map(fn ($row) => $users[$row->user_id] ?? 'Unknown')->toArray();
        $counts = $results->pluck('count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Estimations',
                    'data' => $counts,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(236, 72, 153, 0.7)',
                        'rgba(20, 184, 166, 0.7)',
                        'rgba(249, 115, 22, 0.7)',
                        'rgba(99, 102, 241, 0.7)',
                        'rgba(168, 162, 158, 0.7)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}

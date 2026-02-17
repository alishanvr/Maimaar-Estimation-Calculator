<?php

namespace App\Console\Commands;

use App\Models\AnalyticsMetric;
use App\Models\Estimation;
use App\Models\User;
use Illuminate\Console\Command;

class AggregateAnalytics extends Command
{
    protected $signature = 'analytics:aggregate {--period= : YYYY-MM to aggregate (default: current month)}';

    protected $description = 'Compute and store aggregated analytics metrics for the given period.';

    public function handle(): int
    {
        $period = $this->option('period') ?? now()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $this->error("Invalid period format: {$period}. Expected YYYY-MM.");

            return self::FAILURE;
        }

        $this->info("Aggregating analytics for period: {$period}");

        $this->aggregateForPeriod($period, null);

        User::query()
            ->whereHas('estimations')
            ->each(fn (User $user) => $this->aggregateForPeriod($period, $user->id));

        $this->info('Analytics aggregation complete.');

        return self::SUCCESS;
    }

    private function aggregateForPeriod(string $period, ?int $userId): void
    {
        $query = Estimation::query()
            ->where('estimation_date', 'like', $period.'%');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $totalEstimations = (clone $query)->count();
        $totalWeight = (float) (clone $query)->sum('total_weight_mt');
        $totalRevenue = (float) (clone $query)->sum('total_price_aed');
        $avgPricePerMt = $totalWeight > 0 ? round($totalRevenue / $totalWeight, 4) : 0;

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $metrics = [
            'monthly_estimations' => [
                'value' => $totalEstimations,
                'metadata' => ['status_breakdown' => $statusCounts],
            ],
            'total_weight' => [
                'value' => $totalWeight,
                'metadata' => null,
            ],
            'total_revenue' => [
                'value' => $totalRevenue,
                'metadata' => null,
            ],
            'avg_price_per_mt' => [
                'value' => $avgPricePerMt,
                'metadata' => null,
            ],
        ];

        foreach ($metrics as $metricName => $data) {
            AnalyticsMetric::updateOrCreate(
                [
                    'user_id' => $userId,
                    'metric_name' => $metricName,
                    'period' => $period,
                ],
                [
                    'metric_value' => $data['value'],
                    'metadata' => $data['metadata'],
                ]
            );
        }
    }
}

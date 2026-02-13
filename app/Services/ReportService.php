<?php

namespace App\Services;

use App\Models\Estimation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Build the complete dashboard data for the given filters.
     *
     * @param  array{
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     statuses?: string[]|null,
     *     customer_name?: string|null,
     *     salesperson_code?: string|null,
     * }  $filters
     * @return array<string, mixed>
     */
    public function getDashboardData(array $filters, User $user): array
    {
        $baseQuery = $this->buildBaseQuery($filters, $user);

        return [
            'kpis' => $this->getKpis(clone $baseQuery),
            'monthly_trends' => $this->getMonthlyTrends(clone $baseQuery),
            'customer_revenue' => $this->getCustomerRevenue(clone $baseQuery),
            'weight_distribution' => $this->getWeightDistribution(clone $baseQuery),
            'status_breakdown' => $this->getStatusBreakdown(clone $baseQuery),
            'price_per_mt_trend' => $this->getPricePerMtTrend(clone $baseQuery),
            'cost_category_breakdown' => $this->getCostCategoryBreakdown(clone $baseQuery),
            'filters_meta' => $this->getFiltersMeta($user),
        ];
    }

    /**
     * Get flat estimation rows for CSV export.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getCsvRows(array $filters, User $user): array
    {
        $query = $this->buildBaseQuery($filters, $user);

        return $query
            ->select([
                'id', 'quote_number', 'revision_no', 'building_name',
                'customer_name', 'salesperson_code', 'status',
                'total_weight_mt', 'total_price_aed', 'estimation_date', 'created_at',
            ])
            ->latest('estimation_date')
            ->limit(5000)
            ->get()
            ->map(fn (Estimation $est) => [
                'ID' => $est->id,
                'Quote #' => $est->quote_number ?? '',
                'Revision' => $est->revision_no ?? '',
                'Building' => $est->building_name ?? '',
                'Customer' => $est->customer_name ?? '',
                'Salesperson' => $est->salesperson_code ?? '',
                'Status' => $est->status,
                'Weight (MT)' => $est->total_weight_mt !== null
                    ? number_format((float) $est->total_weight_mt, 4)
                    : '',
                'Price (AED)' => $est->total_price_aed !== null
                    ? number_format((float) $est->total_price_aed, 2)
                    : '',
                'Price/MT (AED)' => ($est->total_weight_mt && $est->total_price_aed)
                    ? number_format((float) $est->total_price_aed / (float) $est->total_weight_mt, 2)
                    : '',
                'Date' => $est->estimation_date?->format('Y-m-d') ?? '',
                'Created' => $est->created_at?->format('Y-m-d H:i') ?? '',
            ])
            ->toArray();
    }

    /**
     * Build a filtered Estimation query scoped to the user.
     */
    private function buildBaseQuery(array $filters, User $user): Builder
    {
        $query = Estimation::query();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if (! empty($filters['date_from'])) {
            $query->where('estimation_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('estimation_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        if (! empty($filters['customer_name'])) {
            $query->where('customer_name', 'like', '%'.$filters['customer_name'].'%');
        }

        if (! empty($filters['salesperson_code'])) {
            $query->where('salesperson_code', $filters['salesperson_code']);
        }

        return $query;
    }

    /**
     * Get the SQL expression for extracting year-month from a date column.
     * Works on both MySQL (DATE_FORMAT) and SQLite (strftime).
     */
    private function yearMonthExpression(string $column): string
    {
        $driver = DB::getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /**
     * Aggregate KPI totals.
     *
     * @return array{
     *     total_estimations: int,
     *     total_weight_mt: float,
     *     total_revenue_aed: float,
     *     avg_price_per_mt: float,
     *     finalized_count: int,
     *     calculated_count: int,
     *     draft_count: int,
     * }
     */
    private function getKpis(Builder $query): array
    {
        $totalEstimations = (clone $query)->count();
        $totalWeight = (float) (clone $query)->sum('total_weight_mt');
        $totalRevenue = (float) (clone $query)->sum('total_price_aed');
        $avgPricePerMt = $totalWeight > 0 ? round($totalRevenue / $totalWeight, 2) : 0;

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return [
            'total_estimations' => $totalEstimations,
            'total_weight_mt' => round($totalWeight, 4),
            'total_revenue_aed' => round($totalRevenue, 2),
            'avg_price_per_mt' => $avgPricePerMt,
            'finalized_count' => (int) ($statusCounts['finalized'] ?? 0),
            'calculated_count' => (int) ($statusCounts['calculated'] ?? 0),
            'draft_count' => (int) ($statusCounts['draft'] ?? 0),
        ];
    }

    /**
     * Monthly estimation trends (count, revenue, weight) for calculated/finalized estimations.
     *
     * @return array<int, array{month: string, label: string, count: int, revenue: float, weight_mt: float}>
     */
    private function getMonthlyTrends(Builder $query): array
    {
        $expr = $this->yearMonthExpression('estimation_date');

        $rows = (clone $query)
            ->whereIn('status', ['calculated', 'finalized'])
            ->whereNotNull('estimation_date')
            ->selectRaw("{$expr} as month")
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(total_price_aed), 0) as revenue')
            ->selectRaw('COALESCE(SUM(total_weight_mt), 0) as weight_mt')
            ->groupByRaw($expr)
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($row) => [
            'month' => $row->month,
            'label' => Carbon::createFromFormat('Y-m', $row->month)->format('M Y'),
            'count' => (int) $row->count,
            'revenue' => round((float) $row->revenue, 2),
            'weight_mt' => round((float) $row->weight_mt, 4),
        ])->toArray();
    }

    /**
     * Top 10 customers by total price (calculated/finalized only).
     *
     * @return array<int, array{customer_name: string, total_price_aed: float, estimation_count: int}>
     */
    private function getCustomerRevenue(Builder $query): array
    {
        $rows = (clone $query)
            ->whereIn('status', ['calculated', 'finalized'])
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->selectRaw('customer_name, SUM(total_price_aed) as total_price_aed, COUNT(*) as estimation_count')
            ->groupBy('customer_name')
            ->orderByDesc('total_price_aed')
            ->limit(10)
            ->get();

        return $rows->map(fn ($row) => [
            'customer_name' => $row->customer_name,
            'total_price_aed' => round((float) $row->total_price_aed, 2),
            'estimation_count' => (int) $row->estimation_count,
        ])->toArray();
    }

    /**
     * Aggregate steel vs panels weight from results_data->summary.
     *
     * @return array{steel_weight_kg: float, panels_weight_kg: float}
     */
    private function getWeightDistribution(Builder $query): array
    {
        $estimations = (clone $query)
            ->whereIn('status', ['calculated', 'finalized'])
            ->whereNotNull('results_data')
            ->select(['id', 'results_data'])
            ->limit(500)
            ->get();

        $steelKg = 0.0;
        $panelsKg = 0.0;

        foreach ($estimations as $est) {
            $summary = $est->results_data['summary'] ?? [];
            $steelKg += (float) ($summary['steel_weight_kg'] ?? 0);
            $panelsKg += (float) ($summary['panels_weight_kg'] ?? 0);
        }

        return [
            'steel_weight_kg' => round($steelKg, 2),
            'panels_weight_kg' => round($panelsKg, 2),
        ];
    }

    /**
     * Count estimations by status.
     *
     * @return array<int, array{status: string, count: int}>
     */
    private function getStatusBreakdown(Builder $query): array
    {
        $rows = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return $rows->map(fn ($row) => [
            'status' => $row->status,
            'count' => (int) $row->count,
        ])->toArray();
    }

    /**
     * Average price per MT trend by month (calculated/finalized only).
     *
     * @return array<int, array{month: string, label: string, avg_price_per_mt: float}>
     */
    private function getPricePerMtTrend(Builder $query): array
    {
        $expr = $this->yearMonthExpression('estimation_date');

        $rows = (clone $query)
            ->whereIn('status', ['calculated', 'finalized'])
            ->whereNotNull('estimation_date')
            ->where('total_weight_mt', '>', 0)
            ->selectRaw("{$expr} as month")
            ->selectRaw('AVG(total_price_aed / total_weight_mt) as avg_price_per_mt')
            ->groupByRaw($expr)
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($row) => [
            'month' => $row->month,
            'label' => Carbon::createFromFormat('Y-m', $row->month)->format('M Y'),
            'avg_price_per_mt' => round((float) $row->avg_price_per_mt, 2),
        ])->toArray();
    }

    /**
     * Aggregate FCPBS cost categories from results_data.
     *
     * @return array<int, array{key: string, name: string, total_cost: float, total_selling: float}>
     */
    private function getCostCategoryBreakdown(Builder $query): array
    {
        $estimations = (clone $query)
            ->whereIn('status', ['calculated', 'finalized'])
            ->whereNotNull('results_data')
            ->select(['id', 'results_data'])
            ->limit(500)
            ->get();

        /** @var array<string, array{name: string, total_cost: float, total_selling: float}> $aggregated */
        $aggregated = [];

        foreach ($estimations as $est) {
            $categories = $est->results_data['fcpbs']['categories'] ?? [];
            foreach ($categories as $key => $cat) {
                if (! isset($aggregated[$key])) {
                    $aggregated[$key] = [
                        'name' => $cat['name'] ?? $key,
                        'total_cost' => 0.0,
                        'total_selling' => 0.0,
                    ];
                }
                $aggregated[$key]['total_cost'] += (float) ($cat['total_cost'] ?? 0);
                $aggregated[$key]['total_selling'] += (float) ($cat['selling_price'] ?? 0);
            }
        }

        $result = [];
        foreach ($aggregated as $key => $data) {
            $result[] = [
                'key' => (string) $key,
                'name' => $data['name'],
                'total_cost' => round($data['total_cost'], 2),
                'total_selling' => round($data['total_selling'], 2),
            ];
        }

        return $result;
    }

    /**
     * Distinct customer names and salesperson codes for filter dropdowns.
     *
     * @return array{customers: string[], salespersons: string[]}
     */
    private function getFiltersMeta(User $user): array
    {
        $baseQuery = Estimation::query();

        if (! $user->isAdmin()) {
            $baseQuery->where('user_id', $user->id);
        }

        $customers = (clone $baseQuery)
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->distinct()
            ->orderBy('customer_name')
            ->pluck('customer_name')
            ->toArray();

        $salespersons = (clone $baseQuery)
            ->whereNotNull('salesperson_code')
            ->where('salesperson_code', '!=', '')
            ->distinct()
            ->orderBy('salesperson_code')
            ->pluck('salesperson_code')
            ->toArray();

        return [
            'customers' => $customers,
            'salespersons' => $salespersons,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Return pre-aggregated analytics metrics.
     */
    public function aggregated(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['sometimes', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'metric_name' => ['sometimes', 'string', 'in:monthly_estimations,total_weight,total_revenue,avg_price_per_mt'],
        ]);

        $query = AnalyticsMetric::query()->with('user:id,name,email');

        if (! $request->user()->isAdmin()) {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                    ->orWhereNull('user_id');
            });
        }

        if ($request->filled('period')) {
            $query->where('period', $request->query('period'));
        }

        if ($request->filled('metric_name')) {
            $query->where('metric_name', $request->query('metric_name'));
        }

        $metrics = $query
            ->orderByDesc('period')
            ->orderBy('metric_name')
            ->limit(200)
            ->get()
            ->map(fn (AnalyticsMetric $metric) => [
                'id' => $metric->id,
                'user' => $metric->user ? [
                    'id' => $metric->user->id,
                    'name' => $metric->user->name,
                ] : null,
                'metric_name' => $metric->metric_name,
                'metric_value' => (float) $metric->metric_value,
                'period' => $metric->period,
                'metadata' => $metric->metadata,
                'updated_at' => $metric->updated_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $metrics]);
    }
}

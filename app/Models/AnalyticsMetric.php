<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsMetric extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsMetricFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'metric_name',
        'metric_value',
        'period',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metric_value' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to system-wide metrics (no user).
     */
    public function scopeSystemWide(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope to a specific metric name.
     */
    public function scopeForMetric(Builder $query, string $metricName): Builder
    {
        return $query->where('metric_name', $metricName);
    }

    /**
     * Scope to a specific period.
     */
    public function scopeForPeriod(Builder $query, string $period): Builder
    {
        return $query->where('period', $period);
    }
}

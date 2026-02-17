<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Estimation extends Model
{
    /** @use HasFactory<\Database\Factories\EstimationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_id',
        'project_id',
        'quote_number',
        'revision_no',
        'building_name',
        'building_no',
        'project_name',
        'customer_name',
        'salesperson_code',
        'estimation_date',
        'status',
        'input_data',
        'results_data',
        'total_weight_mt',
        'total_price_aed',
    ];

    protected function casts(): array
    {
        return [
            'estimation_date' => 'date',
            'input_data' => 'array',
            'results_data' => 'array',
            'total_weight_mt' => 'decimal:4',
            'total_price_aed' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id',
                'project_id',
                'quote_number',
                'revision_no',
                'building_name',
                'building_no',
                'project_name',
                'customer_name',
                'salesperson_code',
                'estimation_date',
                'status',
                'total_weight_mt',
                'total_price_aed',
            ])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Estimation::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Estimation::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimationItem::class)->orderBy('sort_order');
    }

    /**
     * Get all estimations in the same revision chain (root + all descendants).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Estimation>
     */
    public function getRevisionChain(): \Illuminate\Database\Eloquent\Collection
    {
        $root = $this;
        while ($root->parent_id !== null) {
            $root = $root->parent;
        }

        $ids = collect([$root->id]);
        $toProcess = collect([$root->id]);
        while ($toProcess->isNotEmpty()) {
            $childIds = Estimation::query()
                ->whereIn('parent_id', $toProcess)
                ->pluck('id');
            $ids = $ids->merge($childIds);
            $toProcess = $childIds;
        }

        return Estimation::query()
            ->whereIn('id', $ids)
            ->orderBy('created_at')
            ->get(['id', 'quote_number', 'revision_no', 'status', 'parent_id', 'total_weight_mt', 'total_price_aed', 'created_at']);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCalculated(): bool
    {
        return $this->status === 'calculated';
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    /**
     * Sync estimation items from calculated detail data.
     * Deletes existing items and re-creates from the detail array.
     *
     * @param  array<int, array<string, mixed>>  $detailItems
     */
    public function syncEstimationItems(array $detailItems): void
    {
        $this->items()->delete();

        $rows = [];
        foreach ($detailItems as $index => $item) {
            if ($item['is_header'] ?? false) {
                continue;
            }

            $weightKg = (float) ($item['weight_per_unit'] ?? 0)
                * (float) ($item['size'] ?? 0)
                * (float) ($item['qty'] ?? 0);

            $rows[] = [
                'estimation_id' => $this->id,
                'item_code' => $item['code'] ?? null,
                'description' => $item['description'] ?? '',
                'unit' => $item['unit'] ?? null,
                'quantity' => (float) ($item['qty'] ?? 0),
                'weight_kg' => round($weightKg, 4),
                'rate' => (float) ($item['rate'] ?? 0),
                'amount' => round($weightKg * (float) ($item['rate'] ?? 0), 2),
                'category' => $item['cost_code'] ?? null,
                'sort_order' => $item['sort_order'] ?? ($index + 1),
                'metadata' => json_encode([
                    'sales_code' => $item['sales_code'] ?? null,
                    'cost_code' => $item['cost_code'] ?? null,
                    'size' => $item['size'] ?? null,
                    'weight_per_unit' => $item['weight_per_unit'] ?? null,
                    'surface_area' => $item['surface_area'] ?? null,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($rows)) {
            EstimationItem::insert($rows);
        }
    }

    /**
     * Get FCPBS categories as a sequential array for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFcpbsCategoriesListAttribute(): array
    {
        $categories = $this->results_data['fcpbs']['categories'] ?? [];

        return array_values($categories);
    }
}

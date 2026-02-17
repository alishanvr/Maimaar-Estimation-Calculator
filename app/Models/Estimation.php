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
            ->logOnly(['status', 'total_weight_mt', 'total_price_aed'])
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
}

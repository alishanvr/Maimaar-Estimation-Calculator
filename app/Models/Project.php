<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'project_number',
        'project_name',
        'customer_name',
        'location',
        'description',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'project_name'])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estimations(): HasMany
    {
        return $this->hasMany(Estimation::class);
    }

    /**
     * Get aggregated summary for the project.
     *
     * @return array{building_count: int, total_weight: float|null, total_price: float|null}
     */
    public function getSummary(): array
    {
        $estimations = $this->estimations()->get(['total_weight_mt', 'total_price_aed']);

        return [
            'building_count' => $estimations->count(),
            'total_weight' => $estimations->sum('total_weight_mt') ?: null,
            'total_price' => $estimations->sum('total_price_aed') ?: null,
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}

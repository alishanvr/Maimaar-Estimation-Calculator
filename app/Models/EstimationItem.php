<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimationItem extends Model
{
    /** @use HasFactory<\Database\Factories\EstimationItemFactory> */
    use HasFactory;

    protected $fillable = [
        'estimation_id',
        'item_code',
        'description',
        'unit',
        'quantity',
        'weight_kg',
        'rate',
        'amount',
        'category',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'weight_kg' => 'decimal:4',
            'rate' => 'decimal:4',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function estimation(): BelongsTo
    {
        return $this->belongsTo(Estimation::class);
    }
}

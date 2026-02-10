<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    /** @use HasFactory<\Database\Factories\RawMaterialFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'weight_per_sqm',
        'unit',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'weight_per_sqm' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }
}

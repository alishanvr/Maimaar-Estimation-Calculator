<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MbsdbProduct extends Model
{
    /** @use HasFactory<\Database\Factories\MbsdbProductFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'unit',
        'category',
        'rate',
        'rate_type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DesignConfiguration extends Model
{
    /** @use HasFactory<\Database\Factories\DesignConfigurationFactory> */
    use HasFactory;

    protected $fillable = [
        'category',
        'key',
        'value',
        'label',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category)->orderBy('sort_order');
    }

    /**
     * @return array<string, string>
     */
    public static function getOptionsForCategory(string $category): array
    {
        return Cache::remember("design_opts:{$category}", 86400, function () use ($category) {
            return static::query()
                ->byCategory($category)
                ->pluck('label', 'value')
                ->toArray();
        });
    }
}

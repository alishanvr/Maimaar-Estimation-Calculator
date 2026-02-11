<?php

namespace App\Services\Estimation;

use App\Models\DesignConfiguration;
use App\Models\MbsdbProduct;
use App\Models\SsdbProduct;
use Illuminate\Support\Facades\Cache;

class CachingService
{
    /**
     * Cache TTL in seconds (24 hours).
     * Reference data is seeded from Excel and doesn't change during normal operation.
     */
    private const TTL = 86400;

    /**
     * Get a product from MBSDB by code, with caching.
     */
    public function getProductByCode(string $code): ?MbsdbProduct
    {
        return Cache::remember("mbsdb:{$code}", self::TTL, function () use ($code) {
            return MbsdbProduct::query()->byCode($code)->first();
        });
    }

    /**
     * Get product weight (rate) from MBSDB by code.
     */
    public function getProductWeight(string $code): float
    {
        $product = $this->getProductByCode($code);

        return $product ? (float) $product->rate : 0.0;
    }

    /**
     * Get a specific field from a MBSDB product by code.
     */
    public function getProductField(string $code, string $field): mixed
    {
        $product = $this->getProductByCode($code);

        return $product ? $product->{$field} : null;
    }

    /**
     * Lookup product details from MBSDB formatted for DetailGenerator.
     *
     * @return array{description: string, unit: string, weight_per_unit: float, rate: float, surface_area: float}
     */
    public function lookupProductDetails(string $code): array
    {
        $product = $this->getProductByCode($code);

        if (! $product) {
            return [
                'description' => $code,
                'unit' => '',
                'weight_per_unit' => 0,
                'rate' => 0,
                'surface_area' => 0,
            ];
        }

        $metadata = $product->metadata ?? [];

        return [
            'description' => $product->description ?? $code,
            'unit' => $product->unit ?? '',
            'weight_per_unit' => (float) ($metadata['weight_per_unit'] ?? $product->rate ?? 0),
            'rate' => (float) ($product->rate ?? 0),
            'surface_area' => (float) ($metadata['surface_area'] ?? 0),
        ];
    }

    /**
     * Get an SSDB product by code, with caching.
     */
    public function getSsdbProduct(string $code): ?SsdbProduct
    {
        return Cache::remember("ssdb:{$code}", self::TTL, function () use ($code) {
            return SsdbProduct::query()->byCode($code)->first();
        });
    }

    /**
     * Get design configuration options by category, with caching.
     *
     * @return array<string, string>
     */
    public function getDesignOptions(string $category): array
    {
        return Cache::remember("design_opts:{$category}", self::TTL, function () use ($category) {
            return DesignConfiguration::getOptionsForCategory($category);
        });
    }

    /**
     * Clear all reference data caches.
     */
    public function clearReferenceCache(): void
    {
        Cache::flush();
    }
}

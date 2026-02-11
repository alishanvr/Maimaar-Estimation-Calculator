<?php

use App\Models\MbsdbProduct;
use App\Services\Estimation\CachingService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->cachingService = new CachingService;
});

it('caches product lookups by code', function () {
    $product = MbsdbProduct::factory()->create([
        'code' => 'TEST-CACHE-001',
        'rate' => 5.25,
    ]);

    $firstResult = $this->cachingService->getProductByCode('TEST-CACHE-001');
    expect($firstResult)->not->toBeNull();
    expect($firstResult->code)->toBe('TEST-CACHE-001');

    // Second call should hit cache (product is same)
    $secondResult = $this->cachingService->getProductByCode('TEST-CACHE-001');
    expect($secondResult->code)->toBe('TEST-CACHE-001');

    // Verify cache key exists
    expect(Cache::has('mbsdb:TEST-CACHE-001'))->toBeTrue();
});

it('returns zero weight for unknown product code', function () {
    $weight = $this->cachingService->getProductWeight('NONEXISTENT-CODE');

    expect($weight)->toBe(0.0);
});

it('returns product weight from cached data', function () {
    MbsdbProduct::factory()->create([
        'code' => 'WEIGHT-TEST',
        'rate' => 12.75,
    ]);

    $weight = $this->cachingService->getProductWeight('WEIGHT-TEST');

    expect($weight)->toBe(12.75);
});

it('lookups product details for detail generator', function () {
    MbsdbProduct::factory()->create([
        'code' => 'DETAIL-TEST',
        'description' => 'Test Product',
        'unit' => 'KG',
        'rate' => 8.5,
    ]);

    $details = $this->cachingService->lookupProductDetails('DETAIL-TEST');

    expect($details)->toHaveKeys(['description', 'unit', 'weight_per_unit', 'rate', 'surface_area']);
    expect($details['description'])->toBe('Test Product');
    expect($details['unit'])->toBe('KG');
});

it('returns fallback for unknown product in lookupProductDetails', function () {
    $details = $this->cachingService->lookupProductDetails('UNKNOWN-CODE');

    expect($details['description'])->toBe('UNKNOWN-CODE');
    expect($details['weight_per_unit'])->toBe(0);
    expect($details['rate'])->toBe(0);
});

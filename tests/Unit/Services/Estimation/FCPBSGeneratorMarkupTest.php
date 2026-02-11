<?php

use App\Services\Estimation\FCPBSGenerator;

beforeEach(function () {
    $this->generator = new FCPBSGenerator;
});

/**
 * Helper: create a single detail item in category A (cost code 10111 = Main Frames).
 */
function steelItem(float $rate = 100, float $size = 1, float $qty = 1): array
{
    return [
        [
            'cost_code' => '10111',
            'rate' => $rate,
            'size' => $size,
            'qty' => $qty,
            'weight_per_unit' => 10,
            'is_header' => false,
        ],
    ];
}

it('uses explicit zero markup when steel is 0', function () {
    $items = steelItem(100);
    $result = $this->generator->generate($items, ['steel' => 0]);

    // With markup = 0, selling price should be 0
    expect($result['categories']['A']['selling_price'])->toBe(0.0);
});

it('uses default markup when steel is not provided', function () {
    $items = steelItem(100);
    $result = $this->generator->generate($items);

    // Default steel markup is 0.80885358250258
    $cost = $result['categories']['A']['total_cost'];
    $selling = $result['categories']['A']['selling_price'];

    // selling ≈ cost × 0.80885 (within floating-point tolerance)
    expect($selling)->toBeGreaterThan(0);
    expect(abs($selling / $cost - 0.80885358250258))->toBeLessThan(0.001);
});

it('uses explicit markup value when steel is 1.5', function () {
    $items = steelItem(100);
    $result = $this->generator->generate($items, ['steel' => 1.5]);

    $cost = $result['categories']['A']['total_cost'];
    $selling = $result['categories']['A']['selling_price'];

    expect(round($selling / $cost, 2))->toBe(1.5);
});

it('uses default markup when markups array is empty', function () {
    $items = steelItem(100);
    $resultDefault = $this->generator->generate($items);
    $resultEmpty = $this->generator->generate($items, []);

    // Both should produce same selling price (default markup)
    expect($resultEmpty['categories']['A']['selling_price'])
        ->toBe($resultDefault['categories']['A']['selling_price']);
});

it('uses default markup when steel key is missing from markups', function () {
    $items = steelItem(100);
    $resultDefault = $this->generator->generate($items);
    $resultOther = $this->generator->generate($items, ['panels' => 1.2]);

    // Steel should still use default even when panels is set
    expect($resultOther['categories']['A']['selling_price'])
        ->toBe($resultDefault['categories']['A']['selling_price']);
});

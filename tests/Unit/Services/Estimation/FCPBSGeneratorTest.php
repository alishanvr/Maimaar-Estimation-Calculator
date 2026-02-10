<?php

use App\Services\Estimation\FCPBSGenerator;

beforeEach(function () {
    $this->generator = new FCPBSGenerator;
});

describe('generate', function () {
    it('generates categories from empty detail items', function () {
        $result = $this->generator->generate([]);

        expect($result)->toHaveKeys(['categories', 'steel_subtotal', 'panels_subtotal', 'fob_price', 'total_price', 'total_weight_kg']);
        expect($result['total_weight_kg'])->toBe(0.0);
        expect($result['total_price'])->toBe(0.0);
    });

    it('assigns items to correct categories by cost code', function () {
        $items = [
            [
                'cost_code' => '10111',
                'rate' => 3.2079,
                'size' => 1825.8,
                'qty' => 4,
                'weight_per_unit' => 1.0,
                'is_header' => false,
            ],
        ];

        $result = $this->generator->generate($items);

        // Cost code 10111 belongs to category A (Main Frames)
        expect($result['categories']['A']['material_cost'])->toBeGreaterThan(0);
        expect($result['categories']['A']['selling_price'])->toBeGreaterThan(0);
    });

    it('applies steel markup to categories A-D', function () {
        $items = [
            [
                'cost_code' => '10111',
                'rate' => 100,
                'size' => 1,
                'qty' => 1,
                'weight_per_unit' => 10,
                'is_header' => false,
            ],
        ];

        $markups = ['steel' => 0.80885358250258];
        $result = $this->generator->generate($items, $markups);

        // Category A should use steel markup
        expect($result['categories']['A']['markup'])->toBe(0.80885358250258);
    });

    it('applies panels markup to categories F-J', function () {
        $items = [
            [
                'cost_code' => '20211',
                'rate' => 100,
                'size' => 1,
                'qty' => 1,
                'weight_per_unit' => 10,
                'is_header' => false,
            ],
        ];

        $markups = ['panels' => 1.0];
        $result = $this->generator->generate($items, $markups);

        // Category G (Sandwich Panels) should use panels markup
        expect($result['categories']['G']['markup'])->toBe(1.0);
    });

    it('calculates steel and panels subtotals', function () {
        $items = [
            [
                'cost_code' => '10111',
                'rate' => 100,
                'size' => 1,
                'qty' => 10,
                'weight_per_unit' => 5,
                'is_header' => false,
            ],
            [
                'cost_code' => '20211',
                'rate' => 200,
                'size' => 1,
                'qty' => 5,
                'weight_per_unit' => 8,
                'is_header' => false,
            ],
        ];

        $result = $this->generator->generate($items);

        expect($result['steel_subtotal']['weight_kg'])->toBeGreaterThan(0);
        expect($result['panels_subtotal']['weight_kg'])->toBeGreaterThan(0);
        expect($result['fob_price'])->toBe(round(
            $result['steel_subtotal']['selling_price'] + $result['panels_subtotal']['selling_price'],
            2
        ));
    });

    it('skips header items', function () {
        $items = [
            [
                'description' => 'Main Frames',
                'code' => '-',
                'is_header' => true,
            ],
            [
                'cost_code' => '10111',
                'rate' => 100,
                'size' => 1,
                'qty' => 1,
                'weight_per_unit' => 10,
                'is_header' => false,
            ],
        ];

        $result = $this->generator->generate($items);
        // Only the non-header item should contribute to weight
        expect($result['total_weight_kg'])->toBeGreaterThan(0);
    });

    it('calculates weight percentages', function () {
        $items = [
            [
                'cost_code' => '10111',
                'rate' => 100,
                'size' => 1,
                'qty' => 10,
                'weight_per_unit' => 10,
                'is_header' => false,
            ],
            [
                'cost_code' => '20211',
                'rate' => 100,
                'size' => 1,
                'qty' => 10,
                'weight_per_unit' => 10,
                'is_header' => false,
            ],
        ];

        $result = $this->generator->generate($items);
        $totalPct = 0;
        foreach ($result['categories'] as $cat) {
            $totalPct += $cat['weight_pct'];
        }
        // All weight percentages should sum to 100 (with floating point tolerance)
        expect((float) $totalPct)->toEqualWithDelta(100, 1);
    });
});

<?php

use App\Services\Estimation\SALGenerator;

beforeEach(function () {
    $this->generator = new SALGenerator;
});

describe('generate', function () {
    it('generates sales lines from empty inputs', function () {
        $result = $this->generator->generate([], [
            'categories' => [
                'Q' => ['total_cost' => 0, 'selling_price' => 0],
            ],
        ]);

        expect($result)->toHaveKeys(['lines', 'total_weight_kg', 'total_cost', 'total_price', 'markup_ratio', 'price_per_mt']);
        expect($result['total_cost'])->toBe(0.0);
    });

    it('aggregates items by sales code', function () {
        $detailItems = [
            [
                'sales_code' => 1,
                'cost_code' => '10111',
                'item_code' => 'BU',
                'weight_per_unit' => 50,
                'size' => 1,
                'qty' => 100,
                'rate' => 1000,
                'is_header' => false,
            ],
            [
                'sales_code' => 1,
                'cost_code' => '10211',
                'item_code' => 'BU',
                'weight_per_unit' => 30,
                'size' => 1,
                'qty' => 100,
                'rate' => 600,
                'is_header' => false,
            ],
            [
                'sales_code' => 'S',
                'cost_code' => '40111',
                'item_code' => 'Freight',
                'weight_per_unit' => 0,
                'size' => 1,
                'qty' => 1,
                'rate' => 30000,
                'is_header' => false,
            ],
        ];

        $fcpbsData = [
            'categories' => [
                'A' => ['total_cost' => 256000, 'selling_price' => 243200],
                'O' => ['total_cost' => 48000, 'selling_price' => 48000],
                'Q' => ['total_cost' => 0, 'selling_price' => 0],
            ],
        ];

        $result = $this->generator->generate($detailItems, $fcpbsData);

        // Sales code 1: weight = 50*1*100 + 30*1*100 = 8000 kg
        $code1 = collect($result['lines'])->firstWhere('code', 1);
        expect((float) $code1['weight_kg'])->toEqualWithDelta(8000.0, 0.1);
        expect($code1['price'])->toBeGreaterThan(0);
    });

    it('distributes other charges proportionally', function () {
        $detailItems = [
            [
                'sales_code' => 1,
                'cost_code' => '10111',
                'item_code' => 'BU',
                'weight_per_unit' => 100,
                'size' => 1,
                'qty' => 100,
                'rate' => 2000,
                'is_header' => false,
            ],
        ];

        // Category A markup = 384000/320000 = 1.2
        $fcpbsData = [
            'categories' => [
                'A' => ['total_cost' => 320000, 'selling_price' => 384000],
                'Q' => ['total_cost' => 10000, 'selling_price' => 12000],
            ],
        ];

        $result = $this->generator->generate($detailItems, $fcpbsData);

        // offer_price = book_price * markup = 200000 * 1.2 = 240000
        // plus Q selling allocation → total > 240000
        expect($result['total_price'])->toBeGreaterThan(240000);
    });

    it('calculates markup ratio correctly', function () {
        $detailItems = [
            [
                'sales_code' => 1,
                'cost_code' => '10111',
                'item_code' => 'BU',
                'weight_per_unit' => 100,
                'size' => 1,
                'qty' => 100,
                'rate' => 1000,
                'is_header' => false,
            ],
        ];

        // Markup factor = selling / cost = 97000 / 100000 = 0.97
        $fcpbsData = [
            'categories' => [
                'A' => ['total_cost' => 160000, 'selling_price' => 155200],
                'Q' => ['total_cost' => 0, 'selling_price' => 0],
            ],
        ];

        $result = $this->generator->generate($detailItems, $fcpbsData);

        // Markup = 155200/160000 = 0.97 applied to offer price
        expect((float) $result['markup_ratio'])->toEqualWithDelta(0.97, 0.01);
    });

    it('skips header items', function () {
        $detailItems = [
            ['is_header' => true, 'description' => 'Header'],
            [
                'sales_code' => 1,
                'cost_code' => '10111',
                'item_code' => 'BU',
                'weight_per_unit' => 10,
                'size' => 1,
                'qty' => 100,
                'rate' => 500,
                'is_header' => false,
            ],
        ];

        $fcpbsData = [
            'categories' => [
                'A' => ['total_cost' => 80000, 'selling_price' => 76000],
                'Q' => ['total_cost' => 0, 'selling_price' => 0],
            ],
        ];

        $result = $this->generator->generate($detailItems, $fcpbsData);
        // weight = 10*1*100 = 1000 kg (header row skipped)
        expect((float) $result['total_weight_kg'])->toEqualWithDelta(1000.0, 0.1);
    });
});

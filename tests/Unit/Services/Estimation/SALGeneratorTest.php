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
                'total_weight' => 5000,
                'book_price_total' => 100000,
                'offer_price_total' => 95000,
                'is_header' => false,
            ],
            [
                'sales_code' => 1,
                'total_weight' => 3000,
                'book_price_total' => 60000,
                'offer_price_total' => 57000,
                'is_header' => false,
            ],
            [
                'sales_code' => 'S',
                'total_weight' => 0,
                'book_price_total' => 30000,
                'offer_price_total' => 30000,
                'is_header' => false,
            ],
        ];

        $fcpbsData = [
            'categories' => [
                'Q' => ['total_cost' => 0, 'selling_price' => 0],
            ],
        ];

        $result = $this->generator->generate($detailItems, $fcpbsData);

        // Find sales code 1 line
        $code1 = collect($result['lines'])->firstWhere('code', 1);
        expect((float) $code1['weight_kg'])->toEqualWithDelta(8000.0, 0.1);
        expect($code1['price'])->toBeGreaterThan(0);
    });

    it('distributes other charges proportionally', function () {
        $detailItems = [
            [
                'sales_code' => 1,
                'total_weight' => 10000,
                'book_price_total' => 200000,
                'offer_price_total' => 190000,
                'is_header' => false,
            ],
        ];

        $fcpbsData = [
            'categories' => [
                'Q' => ['total_cost' => 10000, 'selling_price' => 10000],
            ],
        ];

        $result = $this->generator->generate($detailItems, $fcpbsData);

        // Total price should include the other charges selling price
        expect($result['total_price'])->toBeGreaterThan(190000);
    });

    it('calculates markup ratio correctly', function () {
        $detailItems = [
            [
                'sales_code' => 1,
                'total_weight' => 10000,
                'book_price_total' => 100000,
                'offer_price_total' => 97000,
                'is_header' => false,
            ],
        ];

        $fcpbsData = [
            'categories' => [
                'Q' => ['total_cost' => 0, 'selling_price' => 0],
            ],
        ];

        $result = $this->generator->generate($detailItems, $fcpbsData);
        // Markup = price / cost = 97000 / 100000 = 0.97
        expect((float) $result['markup_ratio'])->toEqualWithDelta(0.97, 0.01);
    });

    it('skips header items', function () {
        $detailItems = [
            ['is_header' => true, 'description' => 'Header'],
            [
                'sales_code' => 1,
                'total_weight' => 1000,
                'book_price_total' => 50000,
                'offer_price_total' => 48000,
                'is_header' => false,
            ],
        ];

        $fcpbsData = [
            'categories' => [
                'Q' => ['total_cost' => 0, 'selling_price' => 0],
            ],
        ];

        $result = $this->generator->generate($detailItems, $fcpbsData);
        expect((float) $result['total_weight_kg'])->toEqualWithDelta(1000.0, 0.1);
    });
});

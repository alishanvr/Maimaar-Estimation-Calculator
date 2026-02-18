<?php

use App\Services\Estimation\ErpExportService;

beforeEach(function () {
    $this->service = new ErpExportService;
});

function sampleFcpbsData(): array
{
    return [
        'categories' => [
            'A' => [
                'key' => 'A',
                'name' => 'Main Frames',
                'quantity' => 1,
                'weight_kg' => 15500,
                'weight_pct' => 31.3,
                'material_cost' => 52700,
                'manufacturing_cost' => 15500,
                'overhead_cost' => 7750,
                'total_cost' => 75950,
                'markup' => 0.970,
                'selling_price' => 85600,
                'selling_price_pct' => 20.1,
                'price_per_mt' => 5523,
                'value_added' => 9650,
                'va_per_mt' => 623,
            ],
            'B' => [
                'key' => 'B',
                'name' => 'Endwall Frames',
                'quantity' => 2,
                'weight_kg' => 3200,
                'weight_pct' => 6.5,
                'material_cost' => 10880,
                'manufacturing_cost' => 3200,
                'overhead_cost' => 1600,
                'total_cost' => 15680,
                'markup' => 0.970,
                'selling_price' => 17660,
                'selling_price_pct' => 4.2,
                'price_per_mt' => 5519,
                'value_added' => 1980,
                'va_per_mt' => 619,
            ],
        ],
        'steel_subtotal' => ['weight_kg' => 18700],
        'panels_subtotal' => ['weight_kg' => 0],
        'fob_price' => 103260,
        'total_price' => 103260,
    ];
}

function sampleErpInput(): array
{
    return [
        'job_number' => 'TEST01',
        'building_number' => '01',
        'contract_date' => '2026-01-15',
        'fiscal_year' => 2026,
    ];
}

describe('ERP export service', function () {
    it('generates header line with correct format', function () {
        $csv = $this->service->generate(sampleFcpbsData(), sampleErpInput(), 424933.00);
        $lines = explode("\r\n", $csv);

        expect($lines[0])->toStartWith('1,2026,');
        expect($lines[0])->toContain('15-01-2026');
        expect($lines[0])->toContain('TEST01');
    });

    it('generates item lines for categories with weight and selling price', function () {
        $csv = $this->service->generate(sampleFcpbsData(), sampleErpInput(), 424933.00);
        $lines = explode("\r\n", $csv);

        // Header + 2 category lines (A and B)
        expect(count($lines))->toBe(3);
        expect($lines[1])->toStartWith('2,2026,100100');
        expect($lines[2])->toStartWith('2,2026,100200');
    });

    it('skips categories with zero selling price', function () {
        $data = sampleFcpbsData();
        $data['categories']['A']['selling_price'] = 0;

        $csv = $this->service->generate($data, sampleErpInput(), 100000);
        $lines = explode("\r\n", $csv);

        // Header + 1 category line (only B)
        expect(count($lines))->toBe(2);
        expect($lines[1])->toStartWith('2,2026,100200');
    });

    it('handles lump-sum items when qty is zero', function () {
        $data = sampleFcpbsData();
        $data['categories']['A']['weight_kg'] = 0;
        $data['categories']['A']['selling_price'] = 5000;

        $csv = $this->service->generate($data, sampleErpInput(), 100000);
        $lines = explode("\r\n", $csv);

        // Line for A should have qty=1 (lump-sum)
        $parts = explode(',', $lines[1]);
        $qty = trim($parts[3]);
        expect($qty)->toBe('1.0000');
    });

    it('calculates per-unit costs correctly when qty > 0', function () {
        $data = sampleFcpbsData();
        // A: weight_kg=15500, material_cost=52700
        // Per-unit material: 52700 * 1000 / 15500 = 3400.00

        $csv = $this->service->generate($data, sampleErpInput(), 424933.00);
        $lines = explode("\r\n", $csv);
        $parts = explode(',', $lines[1]);

        // Qty in MT: 15500/1000 = 15.5
        $qty = (float) trim($parts[3]);
        expect($qty)->toBe(15.5);

        // Material per-unit: 52700*1000/15500 = 3400.00
        $matUnit = (float) trim($parts[5]);
        expect($matUnit)->toBeGreaterThan(3399);
        expect($matUnit)->toBeLessThan(3401);
    });

    it('formats ERP code as 6 digits', function () {
        $csv = $this->service->generate(sampleFcpbsData(), sampleErpInput(), 424933.00);
        $lines = explode("\r\n", $csv);
        $parts = explode(',', $lines[1]);

        expect(trim($parts[2]))->toBe('100100');
        expect(strlen(trim($parts[2])))->toBe(6);
    });
});

describe('ERP code mapping', function () {
    it('returns correct ERP code for known categories', function () {
        expect(ErpExportService::getErpCode('A'))->toBe(100100);
        expect(ErpExportService::getErpCode('F'))->toBe(200100);
        expect(ErpExportService::getErpCode('T'))->toBe(600100);
    });

    it('returns 0 for unknown categories', function () {
        expect(ErpExportService::getErpCode('Z'))->toBe(0);
        expect(ErpExportService::getErpCode(''))->toBe(0);
    });
});

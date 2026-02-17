<?php

use App\Services\Estimation\RawMatGenerator;

beforeEach(function () {
    $this->generator = new RawMatGenerator;
});

it('aggregates items with same code into one row', function () {
    $detailItems = [
        ['code' => 'Z15G', 'description' => 'Purlin 15G', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 10, 'size' => 9.1, 'weight_per_unit' => 3.2, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'Z15G', 'description' => 'Purlin 15G', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 5, 'size' => 6.0, 'weight_per_unit' => 3.2, 'sales_code' => '3', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);

    expect($result['summary']['unique_materials'])->toBe(1);
    expect($result['items'])->toHaveCount(1);

    $item = $result['items'][0];
    // Total quantity: (10 * 9.1) + (5 * 6.0) = 91 + 30 = 121
    expect($item['quantity'])->toBe(121.0);
    // Total weight: (3.2 * 10 * 9.1) + (3.2 * 5 * 6.0) = 291.2 + 96.0 = 387.2
    expect($item['total_weight'])->toBe(387.2);
});

it('skips header items', function () {
    $detailItems = [
        ['code' => '', 'description' => 'PRIMARY FRAMING', 'cost_code' => '', 'unit' => '', 'qty' => 0, 'size' => 0, 'weight_per_unit' => 0, 'sales_code' => '', 'is_header' => true],
        ['code' => 'BU200', 'description' => 'Built-up', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 5, 'size' => 28.5, 'weight_per_unit' => 35.2, 'sales_code' => '1', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);

    expect($result['summary']['unique_materials'])->toBe(1);
    expect($result['items'][0]['code'])->toBe('BU200');
});

it('skips items with dash code', function () {
    $detailItems = [
        ['code' => '-', 'description' => 'Spacer', 'cost_code' => '', 'unit' => '', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 0, 'sales_code' => '', 'is_header' => false],
        ['code' => 'HSB16', 'description' => 'Bolt', 'cost_code' => 'D1', 'unit' => 'pc', 'qty' => 20, 'size' => 1, 'weight_per_unit' => 0.5, 'sales_code' => '1', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);

    expect($result['summary']['unique_materials'])->toBe(1);
    expect($result['items'][0]['code'])->toBe('HSB16');
});

it('categorizes items correctly by code prefix', function () {
    $detailItems = [
        ['code' => 'BU200', 'description' => 'Built-up', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 1, 'size' => 10, 'weight_per_unit' => 35, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'Z15G', 'description' => 'Purlin', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 1, 'size' => 10, 'weight_per_unit' => 3, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'S5OW', 'description' => 'Sheet', 'cost_code' => 'F1', 'unit' => 'm2', 'qty' => 1, 'size' => 10, 'weight_per_unit' => 5, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'HSB16', 'description' => 'Bolt', 'cost_code' => 'D1', 'unit' => 'pc', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 0.5, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'TTS1', 'description' => 'Trim', 'cost_code' => 'H1', 'unit' => 'm', 'qty' => 1, 'size' => 5, 'weight_per_unit' => 1, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'DOOR-1', 'description' => 'Door', 'cost_code' => 'J1', 'unit' => 'pc', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 50, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'EG100', 'description' => 'Gutter', 'cost_code' => 'I1', 'unit' => 'm', 'qty' => 1, 'size' => 10, 'weight_per_unit' => 2, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'CRANE10T', 'description' => 'Crane Rail', 'cost_code' => 'K1', 'unit' => 'm', 'qty' => 1, 'size' => 10, 'weight_per_unit' => 15, 'sales_code' => '4', 'is_header' => false],
        ['code' => 'MZ-BEAM', 'description' => 'Mezz Beam', 'cost_code' => 'L1', 'unit' => 'm', 'qty' => 1, 'size' => 6, 'weight_per_unit' => 20, 'sales_code' => '2', 'is_header' => false],
        ['code' => 'PU50', 'description' => 'Liner Panel', 'cost_code' => 'M1', 'unit' => 'm2', 'qty' => 1, 'size' => 20, 'weight_per_unit' => 4, 'sales_code' => '18', 'is_header' => false],
        ['code' => 'XYZ-SPECIAL', 'description' => 'Custom Part', 'cost_code' => 'X1', 'unit' => 'pc', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 10, 'sales_code' => '1', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);

    $categoryMap = [];
    foreach ($result['items'] as $item) {
        $categoryMap[$item['code']] = $item['category'];
    }

    expect($categoryMap['BU200'])->toBe('Primary Steel');
    expect($categoryMap['Z15G'])->toBe('Secondary Steel');
    expect($categoryMap['S5OW'])->toBe('Roof/Wall Sheeting');
    expect($categoryMap['HSB16'])->toBe('Fasteners & Bolts');
    expect($categoryMap['TTS1'])->toBe('Trim & Flashing');
    expect($categoryMap['DOOR-1'])->toBe('Doors & Windows');
    expect($categoryMap['EG100'])->toBe('Gutters & Downspouts');
    expect($categoryMap['CRANE10T'])->toBe('Crane Components');
    expect($categoryMap['MZ-BEAM'])->toBe('Mezzanine');
    expect($categoryMap['PU50'])->toBe('Liner Panels');
    expect($categoryMap['XYZ-SPECIAL'])->toBe('Other');
});

it('returns correct summary counts', function () {
    $detailItems = [
        ['code' => 'BU200', 'description' => 'Built-up', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 5, 'size' => 28.5, 'weight_per_unit' => 35.2, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'BU200', 'description' => 'Built-up', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 10, 'size' => 7.5, 'weight_per_unit' => 28.4, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'Z15G', 'description' => 'Purlin', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 40, 'size' => 9.1, 'weight_per_unit' => 4.88, 'sales_code' => '1', 'is_header' => false],
        ['code' => '', 'description' => 'HEADER', 'cost_code' => '', 'unit' => '', 'qty' => 0, 'size' => 0, 'weight_per_unit' => 0, 'sales_code' => '', 'is_header' => true],
    ];

    $result = $this->generator->generate($detailItems);

    expect($result['summary']['total_items_before'])->toBe(3);
    expect($result['summary']['unique_materials'])->toBe(2);
    expect($result['summary']['category_count'])->toBe(2);
    expect($result['summary']['total_weight_kg'])->toBeGreaterThan(0);
});

it('sorts by category then code', function () {
    $detailItems = [
        ['code' => 'Z20G', 'description' => 'Purlin 20', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 1, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'BU300', 'description' => 'Built-up 300', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 1, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'BU200', 'description' => 'Built-up 200', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 1, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'Z15G', 'description' => 'Purlin 15', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 1, 'sales_code' => '1', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);
    $codes = array_column($result['items'], 'code');

    // Primary Steel (BU) first, then Secondary Steel (Z), each sorted alphabetically
    expect($codes)->toBe(['BU200', 'BU300', 'Z15G', 'Z20G']);
});

it('handles empty detail items', function () {
    $result = $this->generator->generate([]);

    expect($result['items'])->toBeEmpty();
    expect($result['summary']['total_items_before'])->toBe(0);
    expect($result['summary']['unique_materials'])->toBe(0);
    expect($result['summary']['total_weight_kg'])->toBe(0.0);
    expect($result['summary']['category_count'])->toBe(0);
    expect($result['categories'])->toBeEmpty();
});

it('tracks sources correctly across multiple sales codes', function () {
    $detailItems = [
        ['code' => 'Z15G', 'description' => 'Purlin', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 10, 'size' => 9.1, 'weight_per_unit' => 3.2, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'Z15G', 'description' => 'Purlin', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 5, 'size' => 6.0, 'weight_per_unit' => 3.2, 'sales_code' => '3', 'is_header' => false],
        ['code' => 'Z15G', 'description' => 'Purlin', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 3, 'size' => 4.0, 'weight_per_unit' => 3.2, 'sales_code' => '1', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);

    expect($result['items'][0]['sources'])->toBe('1, 3');
});

it('uses max of size and 1 for quantity calculation', function () {
    $detailItems = [
        ['code' => 'HSB16', 'description' => 'Bolt', 'cost_code' => 'D1', 'unit' => 'pc', 'qty' => 20, 'size' => 0, 'weight_per_unit' => 0.5, 'sales_code' => '1', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);

    // When size is 0, max(0,1)=1, so quantity = 20 * 1 = 20
    expect($result['items'][0]['quantity'])->toBe(20.0);
    // Weight = 0.5 * 20 * 1 = 10
    expect($result['items'][0]['total_weight'])->toBe(10.0);
});

it('assigns sequential numbers to items', function () {
    $detailItems = [
        ['code' => 'BU200', 'description' => 'Built-up', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 1, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'Z15G', 'description' => 'Purlin', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 1, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'HSB16', 'description' => 'Bolt', 'cost_code' => 'D1', 'unit' => 'pc', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 1, 'sales_code' => '1', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);

    expect($result['items'][0]['no'])->toBe(1);
    expect($result['items'][1]['no'])->toBe(2);
    expect($result['items'][2]['no'])->toBe(3);
});

it('builds correct category weight statistics', function () {
    $detailItems = [
        ['code' => 'BU200', 'description' => 'Built-up 200', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 5, 'size' => 10, 'weight_per_unit' => 35.2, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'BU300', 'description' => 'Built-up 300', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 3, 'size' => 8, 'weight_per_unit' => 45.0, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'Z15G', 'description' => 'Purlin', 'cost_code' => 'B1', 'unit' => 'm', 'qty' => 10, 'size' => 9, 'weight_per_unit' => 3.2, 'sales_code' => '1', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);

    // Primary Steel: (35.2 * 5 * 10) + (45.0 * 3 * 8) = 1760 + 1080 = 2840
    expect($result['categories']['Primary Steel']['count'])->toBe(2);
    expect($result['categories']['Primary Steel']['weight_kg'])->toBe(2840.0);

    // Secondary Steel: 3.2 * 10 * 9 = 288
    expect($result['categories']['Secondary Steel']['count'])->toBe(1);
    expect($result['categories']['Secondary Steel']['weight_kg'])->toBe(288.0);
});

it('skips items with empty code', function () {
    $detailItems = [
        ['code' => '', 'description' => 'Blank', 'cost_code' => '', 'unit' => '', 'qty' => 1, 'size' => 1, 'weight_per_unit' => 5, 'sales_code' => '1', 'is_header' => false],
        ['code' => 'BU200', 'description' => 'Built-up', 'cost_code' => 'A1', 'unit' => 'm', 'qty' => 1, 'size' => 10, 'weight_per_unit' => 35, 'sales_code' => '1', 'is_header' => false],
    ];

    $result = $this->generator->generate($detailItems);

    expect($result['summary']['unique_materials'])->toBe(1);
    expect($result['summary']['total_items_before'])->toBe(1);
});

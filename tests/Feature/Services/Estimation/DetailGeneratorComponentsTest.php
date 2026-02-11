<?php

use App\Services\Estimation\DetailGenerator;

beforeEach(function () {
    $this->generator = app(DetailGenerator::class);
});

/**
 * Minimal input array with required fields so generateAreaItems doesn't crash.
 * The component generators only read their own keys from $input.
 */
function baseInput(): array
{
    return [
        'bay_spacing' => '2@9',
        'span_widths' => '1@28',
        'back_eave_height' => 7.5,
        'front_eave_height' => 7.5,
        'left_roof_slope' => 1.0,
        'right_roof_slope' => 1.0,
        'dead_load' => 0.1,
        'live_load' => 0.57,
        'live_load_permanent' => 0.57,
        'live_load_floor' => 0.57,
        'additional_load' => 0,
        'wind_speed' => 0.7,
        'frame_type' => 'Clear Span',
        'base_type' => 'Pinned Base',
        'cf_finish' => 'Painted',
        'panel_profile' => 'M45-250',
        'outer_skin_material' => 'AZ Steel',
        'double_weld' => 'No',
        'min_thickness' => 6,
        'left_endwall_columns' => '1@14',
        'left_endwall_type' => 'Bearing Frame',
        'left_endwall_portal' => 'None',
        'right_endwall_columns' => '1@14',
        'right_endwall_type' => 'Bearing Frame',
        'right_endwall_portal' => 'None',
        'purlin_depth' => 200,
        'roof_sag_rods' => '0',
        'wall_sag_rods' => '0',
        'roof_sag_rod_dia' => 12,
        'wall_sag_rod_dia' => 12,
        'bracing_type' => 'Cables',
        'roof_panel_code' => 'M45AZ',
        'wall_panel_code' => 'M45AZ',
        'core_thickness' => 50,
        'paint_system' => 'Mammut 3 Coat',
        'roof_top_skin' => 'None',
        'roof_core' => '-',
        'roof_bottom_skin' => '-',
        'roof_insulation' => 'None',
        'wall_top_skin' => 'None',
        'wall_core' => '-',
        'wall_bottom_skin' => '-',
        'wall_insulation' => 'None',
        'trim_size' => '0.5 AZ',
        'back_eave_condition' => 'Gutter+Dwnspts',
        'front_eave_condition' => 'Gutter+Dwnspts',
        'wwm_option' => 'None',
        'bu_finish' => '',
        'monitor_type' => 'None',
        'monitor_width' => 0,
        'monitor_height' => 0,
        'monitor_length' => 0,
        'freight_type' => 'By Mammut',
        'freight_rate' => 0,
        'container_count' => 6,
        'container_rate' => 2000,
        'area_sales_code' => 1,
        'area_description' => 'Building Area',
        'acc_sales_code' => 1,
        'acc_description' => 'Accessories',
        'sales_office' => '',
        'num_buildings' => 1,
        'erection_price' => 0,
    ];
}

describe('crane items', function () {
    it('generates crane items from input data', function () {
        $input = baseInput();
        $input['cranes'] = [
            [
                'description' => 'EOT Crane',
                'sales_code' => 4,
                'capacity' => 10,
                'duty' => 'M',
                'rail_centers' => 25,
                'crane_run' => '2@9',
            ],
        ];

        $items = $this->generator->generate($input);
        $craneItems = array_filter($items, fn ($item) => $item['sales_code'] === 4 && ! $item['is_header']);

        expect($craneItems)->not->toBeEmpty();

        // Should have beam, bracket, rail, and stoppers
        $codes = array_column($craneItems, 'code');
        expect($codes)->toContain('BUCRB2'); // 10t → BUCRB2
        expect($codes)->toContain('BUCRBr3'); // 10t → BUCRBr3
        expect($codes)->toContain('CRC2'); // 10t → CRC2
        expect($codes)->toContain('CRS'); // Stoppers
    });

    it('selects correct beam code based on crane capacity', function () {
        $input = baseInput();
        $input['cranes'] = [
            ['description' => 'Heavy Crane', 'sales_code' => 4, 'capacity' => 25, 'duty' => 'H', 'rail_centers' => 25, 'crane_run' => '2@9'],
        ];

        $items = $this->generator->generate($input);
        $craneItems = array_filter($items, fn ($item) => $item['sales_code'] === 4 && ! $item['is_header']);
        $codes = array_column($craneItems, 'code');

        expect($codes)->toContain('BUCRB4'); // 25t → BUCRB4
        expect($codes)->toContain('BUCRBr6'); // 25t (>20) → BUCRBr6
        expect($codes)->toContain('CRC4'); // 25t → CRC4
    });

    it('handles multiple cranes', function () {
        $input = baseInput();
        $input['cranes'] = [
            ['description' => 'Crane 1', 'sales_code' => 4, 'capacity' => 5, 'duty' => 'L', 'rail_centers' => 20, 'crane_run' => '2@9'],
            ['description' => 'Crane 2', 'sales_code' => 4, 'capacity' => 10, 'duty' => 'M', 'rail_centers' => 25, 'crane_run' => '2@9'],
        ];

        $items = $this->generator->generate($input);
        $craneItems = array_filter($items, fn ($item) => $item['sales_code'] === 4 && ! $item['is_header']);
        $codes = array_column($craneItems, 'code');

        // Both crane beam codes should be present
        expect($codes)->toContain('BUCRB1'); // 5t → BUCRB1
        expect($codes)->toContain('BUCRB2'); // 10t → BUCRB2
    });

    it('skips cranes with zero rail centers', function () {
        $input = baseInput();
        $input['cranes'] = [
            ['description' => 'Bad Crane', 'sales_code' => 4, 'capacity' => 10, 'duty' => 'M', 'rail_centers' => 0, 'crane_run' => '2@9'],
        ];

        $items = $this->generator->generate($input);
        $craneItems = array_filter($items, fn ($item) => $item['sales_code'] === 4);

        expect($craneItems)->toBeEmpty();
    });
});

describe('mezzanine items', function () {
    it('generates mezzanine items from input data', function () {
        $input = baseInput();
        $input['mezzanines'] = [
            [
                'description' => 'Office Mezzanine',
                'sales_code' => 2,
                'col_spacing' => '2@6',
                'beam_spacing' => '1@6',
                'joist_spacing' => '1@3',
                'clear_height' => 4.5,
                'n_stairs' => 1,
            ],
        ];

        $items = $this->generator->generate($input);
        $mezzItems = array_filter($items, fn ($item) => $item['sales_code'] === 2 && ! $item['is_header']);

        expect($mezzItems)->not->toBeEmpty();

        $codes = array_column($mezzItems, 'code');
        expect($codes)->toContain('BU'); // Columns and beams
        expect($codes)->toContain('MD7G'); // Deck
        expect($codes)->toContain('DSP'); // Stairs
        expect($codes)->toContain('HRAIL'); // Handrail
        expect($codes)->toContain('MEA'); // Edge angle
    });

    it('skips mezzanines with no col/beam spacing', function () {
        $input = baseInput();
        $input['mezzanines'] = [
            ['description' => 'Empty Mezz', 'sales_code' => 2, 'col_spacing' => '', 'beam_spacing' => '', 'clear_height' => 4.0],
        ];

        $items = $this->generator->generate($input);
        $mezzItems = array_filter($items, fn ($item) => $item['sales_code'] === 2);

        expect($mezzItems)->toBeEmpty();
    });
});

describe('partition items', function () {
    it('generates partition items from input data', function () {
        $input = baseInput();
        $input['partitions'] = [
            [
                'description' => 'Internal Partition',
                'sales_code' => 11,
                'height' => 6,
                'col_spacing' => '2@6',
                'front_sheeting' => 'M45AZ',
                'back_sheeting' => 'M45AZ',
                'insulation' => 'None',
            ],
        ];

        $items = $this->generator->generate($input);
        $partItems = array_filter($items, fn ($item) => $item['sales_code'] === 11 && ! $item['is_header']);

        expect($partItems)->not->toBeEmpty();

        $codes = array_column($partItems, 'code');
        expect($codes)->toContain('BU'); // Partition columns
        expect($codes)->toContain('Z20G'); // Partition girts
        expect($codes)->toContain('M45AZ'); // Sheeting
    });

    it('skips partitions with zero height', function () {
        $input = baseInput();
        $input['partitions'] = [
            ['description' => 'Bad Part', 'sales_code' => 11, 'height' => 0, 'col_spacing' => '2@6'],
        ];

        $items = $this->generator->generate($input);
        $partItems = array_filter($items, fn ($item) => $item['sales_code'] === 11);

        expect($partItems)->toBeEmpty();
    });
});

describe('canopy items', function () {
    it('generates canopy items from input data', function () {
        $input = baseInput();
        $input['canopies'] = [
            [
                'description' => 'Front Canopy',
                'sales_code' => 3,
                'width' => 3,
                'height' => 4,
                'col_spacing' => '2@6',
                'roof_sheeting' => 'M45AZ',
                'wall_sheeting' => 'None',
                'soffit' => 'None',
                'drainage' => 'EGS',
            ],
        ];

        $items = $this->generator->generate($input);
        $canopyItems = array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']);

        expect($canopyItems)->not->toBeEmpty();

        $codes = array_column($canopyItems, 'code');
        expect($codes)->toContain('BU'); // Canopy rafters
        expect($codes)->toContain('Z15G'); // Canopy purlins
        expect($codes)->toContain('M45AZ'); // Roof sheeting
        expect($codes)->toContain('EGS'); // Drainage
    });

    it('skips canopies with zero width', function () {
        $input = baseInput();
        $input['canopies'] = [
            ['description' => 'Bad Canopy', 'sales_code' => 3, 'width' => 0, 'col_spacing' => '2@6'],
        ];

        $items = $this->generator->generate($input);
        $canopyItems = array_filter($items, fn ($item) => $item['sales_code'] === 3);

        expect($canopyItems)->toBeEmpty();
    });
});

describe('empty components', function () {
    it('gracefully handles empty component arrays', function () {
        $input = baseInput();
        $input['cranes'] = [];
        $input['mezzanines'] = [];
        $input['partitions'] = [];
        $input['canopies'] = [];

        $items = $this->generator->generate($input);

        // Should still have building area items but no component items
        $componentSalesCodes = [2, 3, 4, 11];
        $componentItems = array_filter($items, fn ($item) => in_array($item['sales_code'], $componentSalesCodes));

        expect($componentItems)->toBeEmpty();
    });

    it('gracefully handles missing component keys', function () {
        $input = baseInput();
        // Don't set cranes, mezzanines, partitions, canopies at all

        $items = $this->generator->generate($input);

        // Should still work without errors
        expect($items)->toBeArray();
        expect(count($items))->toBeGreaterThan(0);
    });
});

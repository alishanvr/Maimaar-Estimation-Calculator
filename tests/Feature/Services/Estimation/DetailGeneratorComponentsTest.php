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

    it('expands All Around canopy into 4 wall-specific entries', function () {
        $input = baseInput();
        // baseInput has bay_spacing=2@9 (length=18) and span_widths=1@28 (width=28)
        $input['building_length'] = 18.0;
        $input['building_width'] = 28.0;
        $input['canopies'] = [
            [
                'description' => 'Extension All Around',
                'sales_code' => 3,
                'frame_type' => 'Roof Extension',
                'location' => 'All Around',
                'width' => 3.0,
                'height' => 0,
                'col_spacing' => '2@6',
                'roof_sheeting' => 'M45AZ',
                'wall_sheeting' => 'None',
                'soffit' => 'None',
                'drainage' => 'EGS',
            ],
        ];

        $items = $this->generator->generate($input);

        // The first BU rafter item for each wall carries the wall-specific description
        $wallItems = array_values(array_filter($items, fn ($item) => $item['sales_code'] === 3
            && str_contains($item['description'], 'Extension All Around - ')
            && $item['code'] === 'BU'));

        // Should have 4 BU rafter entries: one for each wall
        expect($wallItems)->toHaveCount(4);

        $descriptions = array_column($wallItems, 'description');
        expect($descriptions)->toContain('Extension All Around - Left Wall');
        expect($descriptions)->toContain('Extension All Around - Right Wall');
        expect($descriptions)->toContain('Extension All Around - Front Wall');
        expect($descriptions)->toContain('Extension All Around - Back Wall');
    });

    it('uses building length for Left/Right walls and width for Front/Back when All Around', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['building_width'] = 28.0;
        $input['canopies'] = [
            [
                'description' => 'Test AA',
                'sales_code' => 3,
                'frame_type' => 'Roof Extension',
                'location' => 'All Around',
                'width' => 2.0,
                'col_spacing' => '2@6',
                'roof_sheeting' => 'M45AZ',
                'drainage' => 'None',
                'soffit' => 'None',
                'wall_sheeting' => 'None',
            ],
        ];

        $items = $this->generator->generate($input);
        $nonHeaderCanopy = array_filter($items, fn ($i) => ! $i['is_header'] && $i['sales_code'] === 3);

        // Items exist for all 4 walls — at minimum BU rafters + Z15G purlins per wall = 8+ items
        expect(count($nonHeaderCanopy))->toBeGreaterThanOrEqual(8);
    });

    it('does not expand when location is a specific wall', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['building_width'] = 28.0;
        $input['canopies'] = [
            [
                'description' => 'Front Only',
                'sales_code' => 3,
                'frame_type' => 'Roof Extension',
                'location' => 'Front',
                'width' => 3.0,
                'col_spacing' => '2@6',
                'roof_sheeting' => 'M45AZ',
                'drainage' => 'EGS',
                'soffit' => 'None',
                'wall_sheeting' => 'None',
            ],
        ];

        $items = $this->generator->generate($input);
        // Only one BU rafter entry with 'Front Only' description (not expanded)
        $canopyBU = array_values(array_filter($items, fn ($item) => str_contains($item['description'], 'Front Only') && $item['code'] === 'BU'));

        expect($canopyBU)->toHaveCount(1);
    });
});

describe('fascia items', function () {
    it('generates fascia items with posts, girts, connections, and wall sheeting', function () {
        $input = baseInput();
        $input['canopies'] = [
            [
                'description' => 'Front Fascia',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 1,
                'height' => 2,
                'col_spacing' => '2@6',
                'wall_sheeting' => 'S5OW',
                'wind_speed' => 130,
            ],
        ];

        $items = $this->generator->generate($input);
        $fasciaItems = array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']);

        expect($fasciaItems)->not->toBeEmpty();

        $codes = array_column($fasciaItems, 'code');

        // Posts (IPEa for moderate wind index)
        expect($codes)->toContain('IPEa');
        // Connections
        expect($codes)->toContain('MFC1');
        expect($codes)->toContain('HSB16');
        // Girts (Z-section based on wind design index)
        $hasGirt = false;
        foreach ($codes as $code) {
            if (str_starts_with($code, 'Z') || $code === 'BUB') {
                $hasGirt = true;
                break;
            }
        }
        expect($hasGirt)->toBeTrue();
        // Girt bolts and clips
        expect($codes)->toContain('HSB12');
        expect($codes)->toContain('CFClip');
        // Wall sheeting
        expect($codes)->toContain('S5OW');
        // Trims
        expect($codes)->toContain('TTS1');
        // Fasteners (carbon steel for steel sheeting)
        expect($codes)->toContain('CS2');
    });

    it('uses stainless screws for aluminum wall sheeting', function () {
        $input = baseInput();
        $input['canopies'] = [
            [
                'description' => 'Fascia Aluminum',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 1,
                'height' => 2,
                'col_spacing' => '2@6',
                'wall_sheeting' => 'A5OW',
                'wind_speed' => 130,
            ],
        ];

        $items = $this->generator->generate($input);
        $fasciaItems = array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']);
        $codes = array_column($fasciaItems, 'code');

        expect($codes)->toContain('SS2');
        expect($codes)->not->toContain('CS2');
    });

    it('selects UB2 posts for moderate wind index', function () {
        $input = baseInput();
        // postIndex = windSpeed * (height + width) * bayWidth
        // 130 * (3 + 2) * 8 = 5200, which is > 2500 but ≤ 6000 → UB2
        $input['canopies'] = [
            [
                'description' => 'High Wind Fascia',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 2,
                'height' => 3,
                'col_spacing' => '2@8',
                'wall_sheeting' => 'S5OW',
                'wind_speed' => 130,
            ],
        ];

        $items = $this->generator->generate($input);
        $fasciaItems = array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']);
        $codes = array_column($fasciaItems, 'code');

        expect($codes)->toContain('UB2');
    });

    it('selects UB3 posts for high wind index', function () {
        $input = baseInput();
        // postIndex = windSpeed * (height + width) * bayWidth
        // 200 * (4 + 2) * 10 = 12000, which is > 6000 → UB3
        $input['canopies'] = [
            [
                'description' => 'Very High Wind Fascia',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 2,
                'height' => 4,
                'col_spacing' => '1@10',
                'wall_sheeting' => 'S5OW',
                'wind_speed' => 200,
            ],
        ];

        $items = $this->generator->generate($input);
        $fasciaItems = array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']);
        $codes = array_column($fasciaItems, 'code');

        expect($codes)->toContain('UB3');
    });

    it('calculates correct girt lines based on fascia height and width', function () {
        $input = baseInput();
        // height=3, width=2 → girtLines = int((3+2)/1.7)+1 = int(2.94)+1 = 3
        // 2 bays → 2 sets of girts
        $input['canopies'] = [
            [
                'description' => 'Fascia Girt Test',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 2,
                'height' => 3,
                'col_spacing' => '2@6',
                'wall_sheeting' => 'None',
                'wind_speed' => 130,
            ],
        ];

        $items = $this->generator->generate($input);
        $girtItems = array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']
            && str_starts_with($item['code'], 'Z'));

        // girtLines=3, 2 bays → each bay gets 3 girts
        $totalGirtQty = array_sum(array_column($girtItems, 'qty'));
        expect($totalGirtQty)->toBe(6); // 2 bays * 3 girt lines
    });

    it('uses minimum 3 girt lines when height is short', function () {
        $input = baseInput();
        // height=1.0 (≤ 1.2), width=0.5 → girtLines = 3 (minimum)
        $input['canopies'] = [
            [
                'description' => 'Short Fascia',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 0.5,
                'height' => 1.0,
                'col_spacing' => '1@6',
                'wall_sheeting' => 'None',
                'wind_speed' => 100,
            ],
        ];

        $items = $this->generator->generate($input);
        $girtItems = array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']
            && (str_starts_with($item['code'], 'Z') || $item['code'] === 'BUB'));

        // 1 bay * 3 girt lines = 3
        $totalGirtQty = array_sum(array_column($girtItems, 'qty'));
        expect($totalGirtQty)->toBe(3);
    });

    it('does not generate roof sheeting for fascia type', function () {
        $input = baseInput();
        $input['canopies'] = [
            [
                'description' => 'Fascia No Roof',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 1,
                'height' => 2,
                'col_spacing' => '2@6',
                'roof_sheeting' => 'M45AZ',
                'wall_sheeting' => 'S5OW',
                'wind_speed' => 130,
            ],
        ];

        $items = $this->generator->generate($input);
        $fasciaItems = array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']);
        $codes = array_column($fasciaItems, 'code');

        // Fascia should NOT have roof sheeting or purlins
        expect($codes)->not->toContain('M45AZ');
        expect($codes)->not->toContain('BU'); // No rafters
    });

    it('skips fascia with no wall sheeting', function () {
        $input = baseInput();
        $input['canopies'] = [
            [
                'description' => 'Fascia No Wall',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 1,
                'height' => 2,
                'col_spacing' => '2@6',
                'wall_sheeting' => 'None',
                'wind_speed' => 130,
            ],
        ];

        $items = $this->generator->generate($input);
        $fasciaItems = array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']);
        $codes = array_column($fasciaItems, 'code');

        // Should still have posts, girts, connections — but no sheeting, trims, or screws
        expect($codes)->not->toContain('TTS1');
        expect($codes)->not->toContain('CS2');
        expect($codes)->not->toContain('SS2');
        // But should have structural elements
        expect($codes)->toContain('MFC1');
        expect($codes)->toContain('HSB16');
    });

    it('skips fascia with zero width and zero height', function () {
        $input = baseInput();
        $input['canopies'] = [
            [
                'description' => 'Bad Fascia',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 0,
                'height' => 0,
                'col_spacing' => '2@6',
            ],
        ];

        $items = $this->generator->generate($input);
        $fasciaItems = array_filter($items, fn ($item) => $item['sales_code'] === 3);

        expect($fasciaItems)->toBeEmpty();
    });

    it('calculates correct wall area and trim length', function () {
        $input = baseInput();
        // totalLength = 2*6 = 12m, height=2, width=1
        // wallArea = 12 * (2+1) = 36 m²
        // trimLength = 2*12 + 4*(2+1) = 24 + 12 = 36 m
        // fasteners = 4 * 36 = 144
        $input['canopies'] = [
            [
                'description' => 'Area Test Fascia',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 1,
                'height' => 2,
                'col_spacing' => '2@6',
                'wall_sheeting' => 'S5OW',
                'wind_speed' => 130,
            ],
        ];

        $items = $this->generator->generate($input);
        $fasciaItems = array_values(array_filter($items, fn ($item) => $item['sales_code'] === 3 && ! $item['is_header']));

        // Find wall sheeting item (S5OW)
        $sheetingItems = array_filter($fasciaItems, fn ($item) => $item['code'] === 'S5OW');
        $sheetingItem = array_values($sheetingItems)[0] ?? null;
        expect($sheetingItem)->not->toBeNull();
        expect((float) $sheetingItem['qty'])->toBe(36.0); // wallArea = 12 * 3

        // Find trim item (TTS1)
        $trimItems = array_filter($fasciaItems, fn ($item) => $item['code'] === 'TTS1');
        $trimItem = array_values($trimItems)[0] ?? null;
        expect($trimItem)->not->toBeNull();
        expect((float) $trimItem['qty'])->toBe(36.0); // 2*12 + 4*(2+1) = 36

        // Find fastener item (CS2)
        $screwItems = array_filter($fasciaItems, fn ($item) => $item['code'] === 'CS2');
        $screwItem = array_values($screwItems)[0] ?? null;
        expect($screwItem)->not->toBeNull();
        expect((float) $screwItem['qty'])->toBe(144.0); // 4 * 36
    });

    it('generates header row for fascia description', function () {
        $input = baseInput();
        $input['canopies'] = [
            [
                'description' => 'Custom Fascia Name',
                'sales_code' => 3,
                'frame_type' => 'Fascia',
                'width' => 1,
                'height' => 2,
                'col_spacing' => '2@6',
                'wall_sheeting' => 'S5OW',
                'wind_speed' => 130,
            ],
        ];

        $items = $this->generator->generate($input);
        $headerItems = array_filter($items, fn ($item) => $item['sales_code'] === 3
            && ($item['is_header'] ?? false)
            && str_contains($item['description'], 'Custom Fascia Name'));

        expect($headerItems)->not->toBeEmpty();
    });
});

describe('empty components', function () {
    it('gracefully handles empty component arrays', function () {
        $input = baseInput();
        $input['cranes'] = [];
        $input['mezzanines'] = [];
        $input['partitions'] = [];
        $input['canopies'] = [];
        $input['liners'] = [];

        $items = $this->generator->generate($input);

        // Should still have building area items but no component items
        $componentSalesCodes = [2, 3, 4, 11, 18];
        $componentItems = array_filter($items, fn ($item) => in_array($item['sales_code'], $componentSalesCodes));

        expect($componentItems)->toBeEmpty();
    });

    it('gracefully handles missing component keys', function () {
        $input = baseInput();
        // Don't set cranes, mezzanines, partitions, canopies, liners at all

        $items = $this->generator->generate($input);

        // Should still work without errors
        expect($items)->toBeArray();
        expect(count($items))->toBeGreaterThan(0);
    });
});

describe('liner items', function () {
    it('generates roof and wall liner items for type Both', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'Roof & Wall Liner',
                'sales_code' => 18,
                'type' => 'Both',
                'roof_liner_code' => 'S5OW',
                'wall_liner_code' => 'S5OW',
                'roof_area' => 0,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $linerItems = array_filter($items, fn ($item) => $item['sales_code'] === 18 && ! $item['is_header']);

        expect($linerItems)->not->toBeEmpty();

        $codes = array_column($linerItems, 'code');
        expect($codes)->toContain('S5OW');
        expect($codes)->toContain('CS2');
        expect($codes)->toContain('CS1');
    });

    it('generates header row for liner items', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'Test Liner',
                'sales_code' => 18,
                'type' => 'Both',
                'roof_liner_code' => 'S5OW',
                'wall_liner_code' => 'S5OW',
                'roof_area' => 0,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $headers = array_filter($items, fn ($item) => $item['sales_code'] === 18 && $item['is_header']);

        expect($headers)->not->toBeEmpty();
        $headerDescs = array_column($headers, 'description');
        expect($headerDescs)->toContain('LINER / CEILING PANELS - Test Liner');
    });

    it('generates only roof liner items for type Roof Liner', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'Roof Only',
                'sales_code' => 18,
                'type' => 'Roof Liner',
                'roof_liner_code' => 'S5OW',
                'wall_liner_code' => 'S5OW',
                'roof_area' => 0,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $linerItems = array_filter($items, fn ($item) => $item['sales_code'] === 18 && ! $item['is_header']);

        expect(count($linerItems))->toBe(3);
    });

    it('generates only wall liner items for type Wall Liner', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'Wall Only',
                'sales_code' => 18,
                'type' => 'Wall Liner',
                'roof_liner_code' => 'S5OW',
                'wall_liner_code' => 'S5OW',
                'roof_area' => 0,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $linerItems = array_filter($items, fn ($item) => $item['sales_code'] === 18 && ! $item['is_header']);

        expect(count($linerItems))->toBe(3);
    });

    it('generates 6 product rows for type Both', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'Full Liner',
                'sales_code' => 18,
                'type' => 'Both',
                'roof_liner_code' => 'S5OW',
                'wall_liner_code' => 'S5OW',
                'roof_area' => 0,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $linerItems = array_filter($items, fn ($item) => $item['sales_code'] === 18 && ! $item['is_header']);

        expect(count($linerItems))->toBe(6);
    });

    it('uses stainless screws for aluminum liner codes', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'Aluminum Liner',
                'sales_code' => 18,
                'type' => 'Roof Liner',
                'roof_liner_code' => 'A5OW',
                'wall_liner_code' => '',
                'roof_area' => 100,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $linerItems = array_filter($items, fn ($item) => $item['sales_code'] === 18 && ! $item['is_header']);
        $codes = array_column($linerItems, 'code');

        expect($codes)->toContain('A5OW');
        expect($codes)->toContain('SS2');
        expect($codes)->toContain('SS1');
    });

    it('uses SS4 screws for PU Aluminum liner codes', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'PUA Liner',
                'sales_code' => 18,
                'type' => 'Roof Liner',
                'roof_liner_code' => 'PUA50',
                'wall_liner_code' => '',
                'roof_area' => 100,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $linerItems = array_filter($items, fn ($item) => $item['sales_code'] === 18 && ! $item['is_header']);
        $codes = array_column($linerItems, 'code');

        expect($codes)->toContain('PUA50');
        expect($codes)->toContain('SS4');
        expect($codes)->toContain('SS1');
    });

    it('uses CS4 screws for PU Steel liner codes', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'PUS Liner',
                'sales_code' => 18,
                'type' => 'Roof Liner',
                'roof_liner_code' => 'PUS50',
                'wall_liner_code' => '',
                'roof_area' => 100,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $linerItems = array_filter($items, fn ($item) => $item['sales_code'] === 18 && ! $item['is_header']);
        $codes = array_column($linerItems, 'code');

        expect($codes)->toContain('PUS50');
        expect($codes)->toContain('CS4');
        expect($codes)->toContain('CS1');
    });

    it('uses manual area override when roof_area is provided', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'Manual Roof Area',
                'sales_code' => 18,
                'type' => 'Roof Liner',
                'roof_liner_code' => 'S5OW',
                'wall_liner_code' => '',
                'roof_area' => 250,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $sheetItems = array_values(array_filter(
            $items,
            fn ($item) => $item['code'] === 'S5OW' && $item['sales_code'] === 18
        ));

        expect($sheetItems)->toHaveCount(1);
        expect((float) $sheetItems[0]['size'])->toBe(250.0);
    });

    it('correctly calculates screw quantities from area', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'Screw Qty Test',
                'sales_code' => 18,
                'type' => 'Roof Liner',
                'roof_liner_code' => 'S5OW',
                'wall_liner_code' => '',
                'roof_area' => 100,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $linerItems = array_values(array_filter(
            $items,
            fn ($item) => $item['sales_code'] === 18 && ! $item['is_header']
        ));

        expect($linerItems[0]['code'])->toBe('S5OW');
        expect((float) $linerItems[0]['size'])->toBe(100.0);
        expect((int) $linerItems[0]['qty'])->toBe(1);

        expect($linerItems[1]['code'])->toBe('CS2');
        expect((int) $linerItems[1]['qty'])->toBe(400);

        expect($linerItems[2]['code'])->toBe('CS1');
        expect((int) $linerItems[2]['qty'])->toBe(50);
    });

    it('deducts roof openings from auto-calculated roof area', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;

        $input['liners'] = [
            [
                'description' => 'No Openings',
                'sales_code' => 18,
                'type' => 'Roof Liner',
                'roof_liner_code' => 'S5OW',
                'wall_liner_code' => '',
                'roof_area' => 0,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];
        $itemsNoOpenings = $this->generator->generate($input);
        $sheetNoOpenings = array_values(array_filter(
            $itemsNoOpenings,
            fn ($item) => $item['code'] === 'S5OW' && $item['sales_code'] === 18
        ));

        $input['liners'][0]['roof_openings_area'] = 20;
        $itemsWithOpenings = $this->generator->generate($input);
        $sheetWithOpenings = array_values(array_filter(
            $itemsWithOpenings,
            fn ($item) => $item['code'] === 'S5OW' && $item['sales_code'] === 18
        ));

        expect((float) $sheetWithOpenings[0]['size'])->toBeLessThan((float) $sheetNoOpenings[0]['size']);
    });

    it('handles multiple liners', function () {
        $input = baseInput();
        $input['building_length'] = 18.0;
        $input['rafter_length'] = 14.1;
        $input['endwall_area'] = 105.7;
        $input['liners'] = [
            [
                'description' => 'Liner 1',
                'sales_code' => 18,
                'type' => 'Roof Liner',
                'roof_liner_code' => 'S5OW',
                'wall_liner_code' => '',
                'roof_area' => 100,
                'wall_area' => 0,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
            [
                'description' => 'Liner 2',
                'sales_code' => 19,
                'type' => 'Wall Liner',
                'roof_liner_code' => '',
                'wall_liner_code' => 'A5OW',
                'roof_area' => 0,
                'wall_area' => 200,
                'roof_openings_area' => 0,
                'wall_openings_area' => 0,
            ],
        ];

        $items = $this->generator->generate($input);
        $liner1Items = array_filter($items, fn ($item) => $item['sales_code'] === 18 && ! $item['is_header']);
        $liner2Items = array_filter($items, fn ($item) => $item['sales_code'] === 19 && ! $item['is_header']);

        expect($liner1Items)->not->toBeEmpty();
        expect($liner2Items)->not->toBeEmpty();

        $liner2Codes = array_column($liner2Items, 'code');
        expect($liner2Codes)->toContain('A5OW');
        expect($liner2Codes)->toContain('SS2');
        expect($liner2Codes)->toContain('SS1');
    });

    it('skips liners with empty arrays', function () {
        $input = baseInput();
        $input['liners'] = [];

        $items = $this->generator->generate($input);
        $linerItems = array_filter($items, fn ($item) => $item['sales_code'] === 18);

        expect($linerItems)->toBeEmpty();
    });

    it('gracefully handles missing liners key', function () {
        $input = baseInput();

        $items = $this->generator->generate($input);
        expect($items)->toBeArray();
        expect(count($items))->toBeGreaterThan(0);
    });
});

describe('imported items', function () {
    it('includes imported items in generated detail', function () {
        $input = baseInput();
        $input['imported_items'] = [
            [
                'description' => 'Custom Beam',
                'code' => 'BU',
                'sales_code' => 1,
                'cost_code' => 'X1',
                'size' => 10.0,
                'qty' => 2,
                'unit' => 'm',
                'weight_per_unit' => 25.0,
                'rate' => 3.5,
            ],
        ];

        $items = $this->generator->generate($input);
        $importedHeaders = array_filter($items, fn ($i) => $i['is_header'] && str_contains($i['description'], 'IMPORTED ITEMS'));
        expect($importedHeaders)->not->toBeEmpty();

        // Find imported BU items (size=10, which wouldn't come from main area items)
        $importedBU = array_filter($items, fn ($i) => ! $i['is_header'] && $i['code'] === 'BU' && $i['size'] == 10.0);
        expect($importedBU)->not->toBeEmpty();
    });

    it('uses CSV weight and rate instead of DB product lookup', function () {
        $input = baseInput();
        $input['imported_items'] = [
            [
                'description' => 'Custom Part',
                'code' => 'CUSTOM99',
                'sales_code' => 5,
                'cost_code' => 'CC1',
                'size' => 3.0,
                'qty' => 7,
                'unit' => 'EA',
                'weight_per_unit' => 99.99,
                'rate' => 55.55,
            ],
        ];

        $items = $this->generator->generate($input);
        $imported = array_values(array_filter(
            $items,
            fn ($i) => ! $i['is_header'] && $i['code'] === 'CUSTOM99'
        ));

        expect($imported)->toHaveCount(1);
        expect($imported[0]['weight_per_unit'])->toBe(99.99);
        expect($imported[0]['rate'])->toBe(55.55);
        expect($imported[0]['unit'])->toBe('EA');
        expect($imported[0]['description'])->toBe('Custom Part');
        expect($imported[0]['cost_code'])->toBe('CC1');
    });

    it('allows items with zero size or zero qty', function () {
        $input = baseInput();
        $input['imported_items'] = [
            [
                'description' => 'Labour Item',
                'code' => 'LB',
                'sales_code' => 1,
                'cost_code' => '',
                'size' => 0,
                'qty' => 1,
                'unit' => 'LS',
                'weight_per_unit' => 0,
                'rate' => 5000,
            ],
            [
                'description' => 'Placeholder',
                'code' => 'PH',
                'sales_code' => 1,
                'cost_code' => '',
                'size' => 5,
                'qty' => 0,
                'unit' => 'EA',
                'weight_per_unit' => 10,
                'rate' => 100,
            ],
        ];

        $items = $this->generator->generate($input);
        $labourItem = array_values(array_filter($items, fn ($i) => $i['code'] === 'LB'));
        $placeholderItem = array_values(array_filter($items, fn ($i) => $i['code'] === 'PH'));

        expect($labourItem)->toHaveCount(1);
        expect($labourItem[0]['rate'])->toBe(5000.0);
        expect($placeholderItem)->toHaveCount(1);
    });

    it('skips items with empty code and empty description', function () {
        $input = baseInput();
        $input['imported_items'] = [
            ['description' => '', 'code' => '', 'sales_code' => 1, 'size' => 1, 'qty' => 1],
            ['description' => 'Has desc only', 'code' => '', 'sales_code' => 1, 'size' => 1, 'qty' => 1],
        ];

        $items = $this->generator->generate($input);
        $descOnly = array_filter($items, fn ($i) => $i['description'] === 'Has desc only');
        expect($descOnly)->toHaveCount(1);
    });

    it('skips when no imported items exist', function () {
        $input = baseInput();
        // No imported_items key
        $items = $this->generator->generate($input);
        $importedHeaders = array_filter($items, fn ($i) => $i['is_header'] && str_contains($i['description'], 'IMPORTED'));
        expect($importedHeaders)->toBeEmpty();
    });
});

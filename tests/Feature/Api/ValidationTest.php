<?php

use App\Models\Estimation;
use App\Models\User;

// ── New Field Validation Rules ──────────────────────────────────────────

it('validates endwall type is one of allowed values', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'left_endwall_type' => 'Invalid Type',
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.left_endwall_type']);
});

it('validates freight type is one of allowed values', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'freight_type' => 'Invalid Freight',
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.freight_type']);
});

it('validates numeric fields reject strings', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'min_thickness' => 'not-a-number',
                'freight_rate' => 'abc',
                'erection_price' => 'xyz',
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'input_data.min_thickness',
            'input_data.freight_rate',
            'input_data.erection_price',
        ]);
});

it('validates openings array structure', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'openings' => [
                    ['location' => 'Front Sidewall', 'size' => '4x4', 'qty' => 2],
                ],
            ],
        ]);

    $response->assertSuccessful();
});

it('validates accessories array structure', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'accessories' => [
                    ['description' => 'Skylight', 'code' => 'SL-01', 'qty' => 4],
                ],
            ],
        ]);

    $response->assertSuccessful();
});

it('validates wwm_option is one of allowed values', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'wwm_option' => 'Invalid Option',
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.wwm_option']);
});

it('validates double_weld is one of allowed values', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'double_weld' => 'Maybe',
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.double_weld']);
});

// ── Optional Component Validation ───────────────────────────────────────

it('validates crane duty is one of allowed values', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'cranes' => [
                    ['description' => 'Crane 1', 'duty' => 'X'],
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.cranes.0.duty']);
});

it('validates crane capacity must be numeric', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'cranes' => [
                    ['description' => 'Crane 1', 'capacity' => 'heavy'],
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.cranes.0.capacity']);
});

it('validates cranes array structure accepted', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'cranes' => [
                    ['description' => 'EOT Crane', 'sales_code' => 4, 'capacity' => 10, 'duty' => 'M', 'rail_centers' => 25, 'crane_run' => '3@9.144'],
                ],
            ],
        ]);

    $response->assertSuccessful();
});

it('validates mezzanine double_welded is one of allowed values', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'mezzanines' => [
                    ['description' => 'Mezz 1', 'double_welded' => 'Maybe'],
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.mezzanines.0.double_welded']);
});

it('validates mezzanines array structure accepted', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'mezzanines' => [
                    ['description' => 'Office Mezz', 'sales_code' => 2, 'clear_height' => 4.5, 'double_welded' => 'No', 'n_stairs' => 1],
                ],
            ],
        ]);

    $response->assertSuccessful();
});

it('validates partition direction is one of allowed values', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'partitions' => [
                    ['description' => 'Part 1', 'direction' => 'Diagonal'],
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.partitions.0.direction']);
});

it('validates partitions array structure accepted', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'partitions' => [
                    ['description' => 'Internal Partition', 'sales_code' => 11, 'direction' => 'Transverse', 'height' => 6],
                ],
            ],
        ]);

    $response->assertSuccessful();
});

it('validates canopy frame_type is one of allowed values', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'canopies' => [
                    ['description' => 'Canopy 1', 'frame_type' => 'Cantilever'],
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.canopies.0.frame_type']);
});

it('validates canopy location is one of allowed values', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'canopies' => [
                    ['description' => 'Canopy 1', 'location' => 'Top'],
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data.canopies.0.location']);
});

it('validates canopies array structure accepted', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'canopies' => [
                    ['description' => 'Front Canopy', 'sales_code' => 3, 'frame_type' => 'Lean-To', 'location' => 'Front', 'height' => 4, 'width' => 3],
                ],
            ],
        ]);

    $response->assertSuccessful();
});

it('validates canopy frame_type accepts Fascia', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'canopies' => [
                    ['description' => 'Front Fascia', 'sales_code' => 3, 'frame_type' => 'Fascia', 'location' => 'Front', 'height' => 2, 'width' => 1],
                ],
            ],
        ]);

    $response->assertSuccessful();
});

// ── Persistence Tests ───────────────────────────────────────────────────

it('can save estimation with openings data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $openings = [
        ['location' => 'Front Sidewall', 'size' => '4x4', 'qty' => 2, 'purlin_support' => 1, 'bracing' => 0],
        ['location' => 'Back Sidewall', 'size' => '3x3', 'qty' => 1, 'purlin_support' => 0, 'bracing' => 1],
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => [
                'openings' => $openings,
            ],
        ]);

    $response->assertSuccessful();

    // Fetch and verify persistence
    $show = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}");

    $show->assertSuccessful();
    $savedOpenings = $show->json('data.input_data.openings');
    expect($savedOpenings)->toHaveCount(2);
    expect($savedOpenings[0]['location'])->toBe('Front Sidewall');
    expect($savedOpenings[0]['qty'])->toBe(2);
    expect($savedOpenings[1]['location'])->toBe('Back Sidewall');
});

it('can save estimation with all new input fields', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $newFields = [
        'min_thickness' => 6,
        'double_weld' => 'No',
        'left_endwall_columns' => '1@4.5+1@5',
        'left_endwall_type' => 'Bearing Frame',
        'left_endwall_portal' => 'None',
        'right_endwall_columns' => '1@4.5+1@5',
        'right_endwall_type' => 'Main Frame',
        'right_endwall_portal' => 'Portal',
        'purlin_depth' => '200',
        'roof_sag_rods' => '0',
        'wall_sag_rods' => '0',
        'roof_sag_rod_dia' => '12',
        'wall_sag_rod_dia' => '16',
        'bracing_type' => 'Cables',
        'live_load_permanent' => 0.5,
        'live_load_floor' => 0.3,
        'additional_load' => 0.1,
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
        'front_eave_condition' => 'Curved',
        'wwm_option' => 'None',
        'bu_finish' => 'standard',
        'freight_type' => 'By Mammut',
        'freight_rate' => 150,
        'container_count' => 6,
        'container_rate' => 2000,
        'area_sales_code' => 1,
        'area_description' => 'Building Area',
        'acc_sales_code' => 1,
        'acc_description' => 'Accessories',
        'sales_office' => 'Dubai',
        'num_buildings' => 1,
        'erection_price' => 5000,
        'accessories' => [
            ['description' => 'Skylight Panel', 'code' => 'SL-01', 'qty' => 4],
        ],
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => $newFields,
        ]);

    $response->assertSuccessful();

    // Fetch and verify persistence
    $show = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}");

    $show->assertSuccessful();
    $inputData = $show->json('data.input_data');

    expect($inputData['min_thickness'])->toBe(6);
    expect($inputData['left_endwall_type'])->toBe('Bearing Frame');
    expect($inputData['freight_type'])->toBe('By Mammut');
    expect($inputData['freight_rate'])->toBe(150);
    expect($inputData['erection_price'])->toBe(5000);
    expect($inputData['sales_office'])->toBe('Dubai');
    expect($inputData['accessories'])->toHaveCount(1);
    expect($inputData['accessories'][0]['code'])->toBe('SL-01');
});

it('can save estimation with all four optional components', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $componentData = [
        'cranes' => [
            ['description' => 'EOT Crane', 'sales_code' => 4, 'capacity' => 10, 'duty' => 'M', 'rail_centers' => 25, 'crane_run' => '3@9.144'],
        ],
        'mezzanines' => [
            ['description' => 'Office Mezzanine', 'sales_code' => 2, 'col_spacing' => '2@6', 'beam_spacing' => '1@6', 'joist_spacing' => '1@3', 'clear_height' => 4.5, 'double_welded' => 'No', 'deck_type' => 'Concrete', 'n_stairs' => 1, 'dead_load' => 0.15, 'live_load' => 2.5, 'additional_load' => 0, 'bu_finish' => '', 'cf_finish' => '', 'min_thickness' => 6],
        ],
        'partitions' => [
            ['description' => 'Internal Partition', 'sales_code' => 11, 'direction' => 'Transverse', 'col_spacing' => '1@6', 'height' => 6, 'opening_height' => 4],
        ],
        'canopies' => [
            ['description' => 'Front Canopy', 'sales_code' => 3, 'frame_type' => 'Lean-To', 'location' => 'Front', 'height' => 4, 'width' => 3, 'col_spacing' => '2@6'],
        ],
    ];

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => $componentData,
        ]);

    $response->assertSuccessful();

    // Fetch and verify persistence
    $show = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}");

    $show->assertSuccessful();
    $inputData = $show->json('data.input_data');

    // Cranes
    expect($inputData['cranes'])->toHaveCount(1);
    expect($inputData['cranes'][0]['description'])->toBe('EOT Crane');
    expect($inputData['cranes'][0]['capacity'])->toBe(10);
    expect($inputData['cranes'][0]['duty'])->toBe('M');

    // Mezzanines
    expect($inputData['mezzanines'])->toHaveCount(1);
    expect($inputData['mezzanines'][0]['description'])->toBe('Office Mezzanine');
    expect($inputData['mezzanines'][0]['clear_height'])->toBe(4.5);
    expect($inputData['mezzanines'][0]['double_welded'])->toBe('No');

    // Partitions
    expect($inputData['partitions'])->toHaveCount(1);
    expect($inputData['partitions'][0]['direction'])->toBe('Transverse');
    expect($inputData['partitions'][0]['height'])->toBe(6);

    // Canopies
    expect($inputData['canopies'])->toHaveCount(1);
    expect($inputData['canopies'][0]['frame_type'])->toBe('Lean-To');
    expect($inputData['canopies'][0]['location'])->toBe('Front');
    expect($inputData['canopies'][0]['width'])->toBe(3);
});

<?php

namespace Database\Factories;

use App\Models\Estimation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Estimation>
 */
class EstimationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'quote_number' => 'HQ-O-'.fake()->numerify('#####'),
            'revision_no' => 'R'.fake()->numerify('##'),
            'building_name' => fake()->word().' Building',
            'building_no' => fake()->numerify('B-##'),
            'project_name' => fake()->sentence(3),
            'customer_name' => fake()->company(),
            'salesperson_code' => strtoupper(fake()->lexify('???')),
            'estimation_date' => fake()->date(),
            'status' => 'draft',
            'input_data' => [],
            'results_data' => null,
            'total_weight_mt' => null,
            'total_price_aed' => null,
        ];
    }

    public function calculated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'calculated',
            'total_weight_mt' => fake()->randomFloat(4, 10, 200),
            'total_price_aed' => fake()->randomFloat(2, 100000, 5000000),
        ]);
    }

    public function revision(Estimation $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'quote_number' => $parent->quote_number,
            'input_data' => $parent->input_data,
        ]);
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'finalized',
            'total_weight_mt' => fake()->randomFloat(4, 10, 200),
            'total_price_aed' => fake()->randomFloat(2, 100000, 5000000),
        ]);
    }

    /**
     * State with realistic results_data and synced estimation items.
     * Used for tests that need a fully calculated estimation with items.
     */
    public function withItems(): static
    {
        return $this->withResults()->afterCreating(function (Estimation $estimation): void {
            $estimation->syncEstimationItems($estimation->results_data['detail'] ?? []);
        });
    }

    /**
     * State with realistic results_data including BOQ, JAF, and summary sheet data.
     * Used for PDF export tests without running the full calculation pipeline.
     */
    public function withResults(): static
    {
        return $this->calculated()->state(fn (array $attributes) => [
            'total_weight_mt' => 49.54,
            'total_price_aed' => 424933.00,
            'results_data' => [
                'boq' => [
                    'items' => [
                        ['sl_no' => 1, 'description' => 'Primary steel', 'unit' => 'MT', 'quantity' => 15.5, 'unit_rate' => 8500.00, 'total_price' => 131750.00],
                        ['sl_no' => 2, 'description' => 'Secondary members', 'unit' => 'MT', 'quantity' => 8.2, 'unit_rate' => 7200.00, 'total_price' => 59040.00],
                        ['sl_no' => 3, 'description' => 'Sandwich panels', 'unit' => 'MT', 'quantity' => 12.0, 'unit_rate' => 6500.00, 'total_price' => 78000.00],
                    ],
                    'total_weight_mt' => 49.54,
                    'total_price' => 424933.00,
                    'price_breakdown' => [],
                    'transport_breakdown' => [],
                    'charges_breakdown' => [],
                ],
                'jaf' => [
                    'project_info' => [
                        'quote_number' => 'HQ-TEST-001',
                        'building_name' => 'Test Warehouse',
                        'building_number' => 1,
                        'project_name' => 'Test Project',
                        'customer_name' => 'Test Customer',
                        'salesperson_code' => 'SP01',
                        'revision_number' => 0,
                        'date' => '2026-01-15',
                        'sales_office' => '',
                    ],
                    'pricing' => [
                        'bottom_line_markup' => 0.97,
                        'value_added_l' => 1659.00,
                        'value_added_r' => 2978.00,
                        'total_weight_mt' => 49.54,
                        'primary_weight_mt' => 15.5,
                        'supply_price_aed' => 424933.00,
                        'erection_price_aed' => 0,
                        'total_contract_aed' => 424933.00,
                        'contract_value_usd' => 115787,
                        'price_per_mt' => 8577.21,
                        'min_delivery_weeks' => 11,
                    ],
                    'building_info' => [
                        'num_non_identical_buildings' => 1,
                        'num_all_buildings' => 1,
                        'scope' => 'Both',
                    ],
                    'special_requirements' => [
                        1 => 'Non standard inventory',
                        2 => 'More than 2 coats paint system',
                    ],
                    'revision_history' => [],
                ],
                'summary' => [
                    'total_weight_kg' => 49540,
                    'total_weight_mt' => 49.54,
                    'total_price_aed' => 424933.00,
                    'price_per_mt' => 8577.21,
                    'fob_price_aed' => 380000.00,
                    'steel_weight_kg' => 27200,
                    'panels_weight_kg' => 22340,
                ],
                'detail' => [
                    ['description' => 'PRIMARY FRAMING', 'code' => '', 'sales_code' => '', 'cost_code' => '', 'size' => 0, 'qty' => 0, 'unit' => '', 'weight_per_unit' => 0, 'rate' => 0, 'is_header' => true, 'sort_order' => 1],
                    ['description' => 'Main Frame Rafters', 'code' => 'MFR', 'sales_code' => 1, 'cost_code' => 'A1', 'size' => 28.5, 'qty' => 5, 'unit' => 'm', 'weight_per_unit' => 35.2, 'rate' => 3.50, 'is_header' => false, 'sort_order' => 2],
                    ['description' => 'Main Frame Columns', 'code' => 'MFC', 'sales_code' => 1, 'cost_code' => 'A2', 'size' => 7.5, 'qty' => 10, 'unit' => 'm', 'weight_per_unit' => 28.4, 'rate' => 3.50, 'is_header' => false, 'sort_order' => 3],
                    ['description' => 'SECONDARY MEMBERS', 'code' => '', 'sales_code' => '', 'cost_code' => '', 'size' => 0, 'qty' => 0, 'unit' => '', 'weight_per_unit' => 0, 'rate' => 0, 'is_header' => true, 'sort_order' => 4],
                    ['description' => 'Roof Purlins Z200', 'code' => 'RP', 'sales_code' => 1, 'cost_code' => 'B1', 'size' => 9.1, 'qty' => 40, 'unit' => 'm', 'weight_per_unit' => 4.88, 'rate' => 3.20, 'is_header' => false, 'sort_order' => 5],
                ],
                'fcpbs' => [
                    'categories' => [
                        'A' => ['key' => 'A', 'name' => 'Main Frames', 'quantity' => 1, 'weight_kg' => 15500, 'weight_pct' => 31.3, 'material_cost' => 52700, 'manufacturing_cost' => 15500, 'overhead_cost' => 7750, 'total_cost' => 75950, 'markup' => 0.970, 'selling_price' => 85600, 'selling_price_pct' => 20.1, 'price_per_mt' => 5523, 'value_added' => 9650, 'va_per_mt' => 623],
                        'B' => ['key' => 'B', 'name' => 'Endwall Frames', 'quantity' => 2, 'weight_kg' => 3200, 'weight_pct' => 6.5, 'material_cost' => 10880, 'manufacturing_cost' => 3200, 'overhead_cost' => 1600, 'total_cost' => 15680, 'markup' => 0.970, 'selling_price' => 17660, 'selling_price_pct' => 4.2, 'price_per_mt' => 5519, 'value_added' => 1980, 'va_per_mt' => 619],
                    ],
                    'steel_subtotal' => ['weight_kg' => 27200, 'material_cost' => 92480, 'manufacturing_cost' => 27200, 'overhead_cost' => 13600, 'total_cost' => 133280, 'selling_price' => 150100, 'value_added' => 16820],
                    'panels_subtotal' => ['weight_kg' => 22340, 'material_cost' => 78190, 'manufacturing_cost' => 11170, 'overhead_cost' => 5585, 'total_cost' => 94945, 'selling_price' => 106933, 'value_added' => 11988],
                    'fob_price' => 380000,
                    'total_price' => 424933,
                    'total_weight_kg' => 49540,
                    'total_weight_mt' => 49.54,
                ],
                'sal' => [
                    'lines' => [
                        ['code' => 1, 'description' => 'Building Area', 'weight_kg' => 27200, 'cost' => 133280, 'markup' => 0.970, 'price' => 150100, 'price_per_mt' => 5519],
                        ['code' => 2, 'description' => 'Mezzanine', 'weight_kg' => 5400, 'cost' => 32400, 'markup' => 0.970, 'price' => 36490, 'price_per_mt' => 6757],
                        ['code' => 3, 'description' => 'Canopy', 'weight_kg' => 2100, 'cost' => 12600, 'markup' => 0.970, 'price' => 14190, 'price_per_mt' => 6757],
                    ],
                    'total_weight_kg' => 49540,
                    'total_cost' => 228280,
                    'total_price' => 424933,
                    'markup_ratio' => 0.970,
                    'price_per_mt' => 8577.21,
                ],
                'rawmat' => [
                    'items' => [
                        ['no' => 1, 'code' => 'BU200', 'cost_code' => 'A1', 'description' => 'Built-up Section', 'unit' => 'm', 'quantity' => 142.5, 'unit_weight' => 35.2, 'total_weight' => 5016.0, 'category' => 'Primary Steel', 'sources' => '1'],
                        ['no' => 2, 'code' => 'Z20G', 'cost_code' => 'B1', 'description' => 'Z-Purlin 200', 'unit' => 'm', 'quantity' => 364.0, 'unit_weight' => 4.88, 'total_weight' => 1776.32, 'category' => 'Secondary Steel', 'sources' => '1'],
                    ],
                    'summary' => [
                        'total_items_before' => 5,
                        'unique_materials' => 2,
                        'total_weight_kg' => 6792.32,
                        'category_count' => 2,
                    ],
                    'categories' => [
                        'Primary Steel' => ['count' => 1, 'weight_kg' => 5016.0],
                        'Secondary Steel' => ['count' => 1, 'weight_kg' => 1776.32],
                    ],
                ],
            ],
        ]);
    }
}

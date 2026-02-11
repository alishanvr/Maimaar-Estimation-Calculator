<?php

namespace Database\Factories;

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

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'finalized',
            'total_weight_mt' => fake()->randomFloat(4, 10, 200),
            'total_price_aed' => fake()->randomFloat(2, 100000, 5000000),
        ]);
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
            ],
        ]);
    }
}

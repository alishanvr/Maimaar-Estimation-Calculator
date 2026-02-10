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
}

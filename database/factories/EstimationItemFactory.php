<?php

namespace Database\Factories;

use App\Models\Estimation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EstimationItem>
 */
class EstimationItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'estimation_id' => Estimation::factory(),
            'item_code' => strtoupper(fake()->lexify('???-###')),
            'description' => fake()->sentence(4),
            'unit' => fake()->randomElement(['kg', 'm²', 'pc', 'set', 'lot']),
            'quantity' => fake()->randomFloat(4, 1, 1000),
            'weight_kg' => fake()->randomFloat(4, 0, 50000),
            'rate' => fake()->randomFloat(4, 1, 100),
            'amount' => fake()->randomFloat(2, 100, 500000),
            'category' => fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}

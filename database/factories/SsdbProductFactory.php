<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SsdbProduct>
 */
class SsdbProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('SS-????')),
            'description' => fake()->sentence(3),
            'unit' => fake()->randomElement(['kg', 'm²', 'pc', 'set']),
            'category' => fake()->randomElement(['HotRolled', 'Tube', 'BuiltUp', 'Plate', 'HandRail', 'Truss']),
            'rate' => fake()->randomFloat(4, 0.1, 50),
            'grade' => fake()->randomElement(['Light', 'Medium', 'Heavy']),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MbsdbProduct>
 */
class MbsdbProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('MBS-????')),
            'description' => fake()->sentence(3),
            'unit' => fake()->randomElement(['kg', 'm²', 'pc', 'set']),
            'category' => fake()->randomElement(['Gang', 'Panel', 'BuiltUp', 'HotRolled', 'Bolt', 'Special']),
            'rate' => fake()->randomFloat(4, 0.1, 50),
            'rate_type' => fake()->randomElement(['kg', 'm²', 'unit']),
        ];
    }
}

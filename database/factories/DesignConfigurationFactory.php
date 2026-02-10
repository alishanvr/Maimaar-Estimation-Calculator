<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DesignConfiguration>
 */
class DesignConfigurationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(['frame_type', 'base_condition', 'paint_system', 'freight_code']),
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
            'label' => fake()->sentence(2),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}

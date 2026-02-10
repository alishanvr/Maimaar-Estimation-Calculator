<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RawMaterial>
 */
class RawMaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('RM-????')),
            'description' => fake()->sentence(3),
            'weight_per_sqm' => fake()->randomFloat(4, 0.5, 10),
            'unit' => 'kg/m²',
        ];
    }
}

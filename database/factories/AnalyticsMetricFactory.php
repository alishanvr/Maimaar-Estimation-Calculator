<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsMetric>
 */
class AnalyticsMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'metric_name' => fake()->randomElement([
                'monthly_estimations',
                'total_weight',
                'total_revenue',
                'avg_price_per_mt',
            ]),
            'metric_value' => fake()->randomFloat(4, 0, 10000),
            'period' => now()->format('Y-m'),
            'metadata' => null,
        ];
    }

    /**
     * Metric for a specific user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(fn () => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }
}

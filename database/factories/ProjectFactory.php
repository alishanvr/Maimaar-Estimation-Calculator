<?php

namespace Database\Factories;

use App\Models\Estimation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_number' => 'PRJ-'.fake()->numerify('#####'),
            'project_name' => fake()->sentence(3),
            'customer_name' => fake()->company(),
            'location' => fake()->city().', '.fake()->country(),
            'description' => fake()->optional()->paragraph(),
            'status' => 'draft',
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    public function withEstimations(int $count = 2): static
    {
        return $this->afterCreating(function (Project $project) use ($count) {
            Estimation::factory()
                ->count($count)
                ->for($project->user)
                ->create(['project_id' => $project->id]);
        });
    }
}

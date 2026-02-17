<?php

namespace Database\Factories;

use App\Models\Estimation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sheetName = fake()->randomElement(['boq', 'jaf', 'recap', 'detail', 'fcpbs', 'sal', 'rawmat']);

        return [
            'user_id' => User::factory(),
            'estimation_id' => Estimation::factory(),
            'report_type' => 'pdf',
            'sheet_name' => $sheetName,
            'filename' => strtoupper($sheetName).'-'.fake()->numerify('HQ-O-#####').'.pdf',
            'file_size' => fake()->numberBetween(10000, 5000000),
        ];
    }

    /**
     * Report for a CSV export.
     */
    public function csv(): static
    {
        return $this->state(fn () => [
            'report_type' => 'csv',
            'sheet_name' => 'dashboard',
            'filename' => 'estimations-report-'.now()->format('Y-m-d').'.csv',
        ]);
    }

    /**
     * Report for a bulk ZIP export.
     */
    public function zip(): static
    {
        return $this->state(fn () => [
            'report_type' => 'zip',
            'sheet_name' => 'bulk',
            'estimation_id' => null,
            'filename' => 'estimations-export.zip',
        ]);
    }
}

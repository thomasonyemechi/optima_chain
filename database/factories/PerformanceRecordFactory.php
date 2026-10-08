<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\PerformanceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PerformanceRecord>
 */
class PerformanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accuracy = fake()->randomFloat(2, 50, 100);
        $target = fake()->randomFloat(2, 40, 100);
        $timeliness = fake()->randomFloat(2, 50, 100);
        $payment = fake()->randomFloat(2, 50, 100);

        return [
            'distributor_id' => Distributor::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => now()->year,
            'accuracy_score' => $accuracy,
            'target_score' => $target,
            'timeliness_score' => $timeliness,
            'payment_score' => $payment,
            'total_score' => round((0.35 * $accuracy) + (0.30 * $target) + (0.20 * $timeliness) + (0.15 * $payment), 2),
            'verification_code' => (string) Str::uuid(),
        ];
    }
}

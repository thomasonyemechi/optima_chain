<?php

namespace Database\Factories;

use App\Models\ForecastBand;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForecastBand>
 */
class ForecastBandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'week_number' => fake()->numberBetween(1, 52),
            'year' => now()->isoWeekYear,
            'low_band' => fake()->numberBetween(50, 150),
            'expected_band' => fake()->numberBetween(151, 300),
            'high_band' => fake()->numberBetween(301, 500),
        ];
    }
}

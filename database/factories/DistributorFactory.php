<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Distributor>
 */
class DistributorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = fake()->unique()->company().' Distribution';

        return [
            'user_id' => User::factory()->distributor(),
            'company_name' => $companyName,
            'account_number' => 'DIST-'.fake()->unique()->numerify('########'),
            'monthly_target' => fake()->numberBetween(1000, 50000),
        ];
    }
}

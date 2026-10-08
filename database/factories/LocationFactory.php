<?php

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locations = [
            ['Lagos Central', 'LGC'],
            ['Abuja North', 'ABN'],
            ['Port Harcourt South', 'PHS'],
            ['Kano West', 'KNW'],
            ['Ibadan East', 'IBE'],
            ['Enugu Main', 'ENG'],
        ];
        $location = fake()->randomElement($locations);

        return [
            'distributor_id' => Distributor::factory(),
            'name' => $location[0],
            'code' => $location[1].'-'.fake()->unique()->numerify('##'),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\DemandRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'demand_request_id' => DemandRequest::factory()->discrepancy(),
            'type' => 'quantity_discrepancy',
            'description' => 'Confirmed receipt quantity differs from the dispatched quantity.',
            'status' => 'open',
        ];
    }
}

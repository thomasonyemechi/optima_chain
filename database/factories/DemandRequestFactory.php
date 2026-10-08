<?php

namespace Database\Factories;

use App\Models\DemandRequest;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandRequest>
 */
class DemandRequestFactory extends Factory
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
            'distributor_id' => fn (array $attributes): int => (int) Location::query()
                ->findOrFail($attributes['location_id'])
                ->distributor_id,
            'week_number' => now()->subWeek()->isoWeek,
            'year' => now()->subWeek()->isoWeekYear,
            'requested_qty' => fake()->numberBetween(100, 500),
            'approved_qty' => null,
            'dispatched_qty' => null,
            'confirmed_qty' => null,
            'sales_qty' => null,
            'status' => 'pending',
            'company_short_supply' => false,
            'flag_reason' => null,
            'confirmation_code' => null,
            'confirmed_at' => null,
        ];
    }

    public function autoApproved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'auto_approved',
            'approved_qty' => $attributes['requested_qty'],
            'flag_reason' => null,
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'flagged',
            'approved_qty' => null,
            'flag_reason' => 'Requested quantity exceeds the forecast high band.',
        ]);
    }

    public function stockoutRisk(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
            'approved_qty' => null,
            'flag_reason' => 'Requested quantity is below the forecast low band; review stockout risk.',
        ]);
    }

    public function fulfilled(): static
    {
        return $this->state(function (array $attributes): array {
            $requested = (int) $attributes['requested_qty'];
            $approved = (int) ($attributes['approved_qty'] ?? $requested);
            $dispatched = max(0, $approved - fake()->numberBetween(0, max(0, (int) floor($approved * 0.05))));
            $confirmed = max(0, $dispatched - fake()->numberBetween(0, max(0, (int) floor($dispatched * 0.05))));

            return [
                'approved_qty' => $approved,
                'dispatched_qty' => $dispatched,
                'confirmed_qty' => $confirmed,
                'sales_qty' => fake()->numberBetween(0, $confirmed),
                'status' => 'completed',
                'confirmation_code' => str_pad((string) fake()->numberBetween(0, 999999), 6, '0', STR_PAD_LEFT),
                'confirmed_at' => now(),
            ];
        });
    }

    public function discrepancy(): static
    {
        return $this->state(function (array $attributes): array {
            $requested = (int) $attributes['requested_qty'];

            return [
                'approved_qty' => $requested,
                'dispatched_qty' => $requested,
                'confirmed_qty' => max(0, $requested - fake()->numberBetween(1, max(1, (int) floor($requested * 0.2)))),
                'sales_qty' => 0,
                'status' => 'completed',
                'company_short_supply' => false,
                'confirmation_code' => str_pad((string) fake()->numberBetween(0, 999999), 6, '0', STR_PAD_LEFT),
                'confirmed_at' => now(),
            ];
        });
    }
}

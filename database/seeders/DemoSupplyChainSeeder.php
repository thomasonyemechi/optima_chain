<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\DemandRequest;
use App\Models\Distributor;
use App\Models\ForecastBand;
use App\Models\Location;
use App\Models\MarketContext;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DemoSupplyChainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo credentials cannot be seeded in production.');
        }

        $password = Hash::make('OptimaDemo!2026');
        $accounts = [
            'distributor' => ['Avery Morgan', 'demo.distributor@optima-chain.test'],
            'manager' => ['Jordan Lee', 'demo.manager@optima-chain.test'],
            'logistics' => ['Casey Taylor', 'demo.logistics@optima-chain.test'],
            'admin' => ['Riley Quinn', 'demo.admin@optima-chain.test'],
            'bank' => ['Morgan Ellis', 'demo.bank@optima-chain.test'],
        ];
        $users = [];

        foreach ($accounts as $role => [$name, $email]) {
            $users[$role] = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password,
                    'role' => $role,
                    'email_verified_at' => now(),
                ],
            );
        }

        $distributor = Distributor::query()->updateOrCreate(
            ['user_id' => $users['distributor']->id],
            [
                'company_name' => 'Northstar Distribution Co.',
                'account_number' => 'DEMO-ACCT-1001',
                'monthly_target' => 1000,
            ],
        );

        $locations = collect([
            ['name' => 'Central Depot', 'code' => 'CENTRAL'],
            ['name' => 'Riverside Depot', 'code' => 'RIVER'],
        ])->mapWithKeys(function (array $attributes) use ($distributor): array {
            $location = Location::query()->updateOrCreate(
                ['distributor_id' => $distributor->id, 'code' => $attributes['code']],
                ['name' => $attributes['name']],
            );

            return [$attributes['code'] => $location];
        });

        $currentWeek = now()->isoWeek;
        $currentYear = now()->isoWeekYear;
        $periods = [
            ['week' => $currentWeek, 'year' => $currentYear, 'low' => 70, 'expected' => 95, 'high' => 120],
            ['week' => now()->subWeek()->isoWeek, 'year' => now()->subWeek()->isoWeekYear, 'low' => 65, 'expected' => 90, 'high' => 115],
            ['week' => now()->subWeeks(2)->isoWeek, 'year' => now()->subWeeks(2)->isoWeekYear, 'low' => 60, 'expected' => 85, 'high' => 110],
            ['week' => now()->subWeeks(3)->isoWeek, 'year' => now()->subWeeks(3)->isoWeekYear, 'low' => 60, 'expected' => 80, 'high' => 105],
        ];

        foreach ($locations as $location) {
            foreach ($periods as $period) {
                ForecastBand::query()->updateOrCreate(
                    [
                        'location_id' => $location->id,
                        'week_number' => $period['week'],
                        'year' => $period['year'],
                    ],
                    [
                        'low_band' => $period['low'],
                        'expected_band' => $period['expected'],
                        'high_band' => $period['high'],
                    ],
                );
            }

            foreach ([
                ['days_ago' => 2, 'weather' => 'Light rain', 'temperature' => 18.5, 'inflation' => 2.7],
                ['days_ago' => 8, 'weather' => 'Partly cloudy', 'temperature' => 21.0, 'inflation' => 2.6],
                ['days_ago' => 15, 'weather' => 'Clear skies', 'temperature' => 23.4, 'inflation' => 2.5],
            ] as $context) {
                MarketContext::query()->updateOrCreate(
                    [
                        'location_id' => $location->id,
                        'observed_on' => now()->subDays($context['days_ago'])->toDateString(),
                    ],
                    [
                        'weather_summary' => $context['weather'],
                        'temperature_celsius' => $context['temperature'],
                        'inflation_rate' => $context['inflation'],
                        'inflation_region' => 'North Region',
                    ],
                );
            }
        }

        $requestRows = [
            [
                'key' => 'current-auto-approved', 'week' => $periods[0], 'location' => 'CENTRAL',
                'requested' => 100, 'approved' => 100, 'dispatched' => null, 'confirmed' => null,
                'sales' => null, 'status' => 'auto_approved', 'short_supply' => false,
            ],
            [
                'key' => 'current-flagged', 'week' => $periods[0], 'location' => 'CENTRAL',
                'requested' => 145, 'approved' => null, 'dispatched' => null, 'confirmed' => null,
                'sales' => null, 'status' => 'flagged', 'short_supply' => false,
                'flag_reason' => 'Requested quantity exceeds the forecast high band.',
            ],
            [
                'key' => 'current-adjusted', 'week' => $periods[0], 'location' => 'RIVER',
                'requested' => 130, 'approved' => 110, 'dispatched' => 105, 'confirmed' => null,
                'sales' => null, 'status' => 'adjusted', 'short_supply' => true,
                'confirmation_code' => '482916',
            ],
            [
                'key' => 'previous-delivered', 'week' => $periods[1], 'location' => 'CENTRAL',
                'requested' => 95, 'approved' => 95, 'dispatched' => 92, 'confirmed' => 88,
                'sales' => null, 'status' => 'delivered', 'short_supply' => false,
                'confirmation_code' => '735204', 'confirmed_at' => now()->subDays(2),
            ],
            [
                'key' => 'older-completed', 'week' => $periods[2], 'location' => 'CENTRAL',
                'requested' => 90, 'approved' => 90, 'dispatched' => 90, 'confirmed' => 90,
                'sales' => 82, 'status' => 'completed', 'short_supply' => false,
                'confirmation_code' => '164830', 'confirmed_at' => now()->subDays(9),
            ],
            [
                'key' => 'older-short-supply-completed', 'week' => $periods[3], 'location' => 'RIVER',
                'requested' => 120, 'approved' => 95, 'dispatched' => 95, 'confirmed' => 93,
                'sales' => 90, 'status' => 'completed', 'short_supply' => true,
                'confirmation_code' => '906271', 'confirmed_at' => now()->subDays(16),
            ],
        ];
        $requests = [];

        foreach ($requestRows as $row) {
            $demandRequest = DemandRequest::query()->updateOrCreate(
                [
                    'distributor_id' => $distributor->id,
                    'location_id' => $locations[$row['location']]->id,
                    'year' => $row['week']['year'],
                    'week_number' => $row['week']['week'],
                    'requested_qty' => $row['requested'],
                ],
                [
                    'requested_qty' => $row['requested'],
                    'approved_qty' => $row['approved'],
                    'dispatched_qty' => $row['dispatched'],
                    'confirmed_qty' => $row['confirmed'],
                    'sales_qty' => $row['sales'],
                    'status' => $row['status'],
                    'company_short_supply' => $row['short_supply'],
                    'flag_reason' => $row['flag_reason'] ?? null,
                    'confirmation_code' => $row['confirmation_code'] ?? null,
                    'confirmed_at' => $row['confirmed_at'] ?? null,
                ],
            );

            $requests[$row['key']] = $demandRequest;
        }

        Complaint::query()->updateOrCreate(
            ['demand_request_id' => $requests['previous-delivered']->id],
            [
                'type' => 'quantity_discrepancy',
                'description' => 'Dispatched quantity was 92 units and confirmed receipt was 88 units.',
                'status' => 'open',
            ],
        );

        Complaint::query()->updateOrCreate(
            ['demand_request_id' => $requests['older-short-supply-completed']->id],
            [
                'type' => 'quantity_discrepancy',
                'description' => 'Dispatched quantity was 95 units and confirmed receipt was 93 units.',
                'status' => 'resolved',
            ],
        );

        $record = PerformanceRecord::query()->firstOrNew([
            'distributor_id' => $distributor->id,
            'month' => now()->month,
            'year' => now()->year,
        ]);
        $record->fill([
            'accuracy_score' => 94,
            'target_score' => 88,
            'timeliness_score' => 91,
            'payment_score' => 100,
            'total_score' => 92.4,
            'verification_code' => $record->verification_code ?? (string) Str::uuid(),
        ])->save();

        $this->command?->info('Demo supply-chain data seeded.');
        $this->command?->line('All demo accounts use password: OptimaDemo!2026');
    }
}

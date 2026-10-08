<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\DemandRequest;
use App\Models\Distributor;
use App\Models\ForecastBand;
use App\Models\Location;
use App\Models\MarketContext;
use App\Models\User;
use App\Services\PerformanceScoringService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(PerformanceScoringService $scoringService): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo credentials cannot be seeded in production.');
        }

        DB::transaction(function () use ($scoringService): void {
            $users = $this->seedUsers();
            $distributors = $this->seedDistributors($users);
            $this->seedForecastHistoryAndDemand($distributors);

            foreach ($distributors as $distributor) {
                foreach (range(0, 11) as $monthOffset) {
                    $period = CarbonImmutable::now()->startOfMonth()->subMonths($monthOffset);
                    $scoringService->calculateForPeriod($distributor, $period->month, $period->year, 90, 85);
                }
            }
        });
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $accounts = [
            ['key' => 'admin', 'name' => 'Super Admin', 'email' => 'demo.admin@optima-chain.test', 'role' => 'admin', 'password' => 'OptimaDemo!2026'],
            ['key' => 'manager_demo', 'name' => 'Demo Manager', 'email' => 'manager@demo.com', 'role' => 'manager', 'password' => 'password'],
            ['key' => 'manager_legacy', 'name' => 'Jordan Lee', 'email' => 'demo.manager@optima-chain.test', 'role' => 'manager', 'password' => 'OptimaDemo!2026'],
            ['key' => 'logistics_demo', 'name' => 'Demo Logistics', 'email' => 'logistics@demo.com', 'role' => 'logistics', 'password' => 'password'],
            ['key' => 'logistics_legacy', 'name' => 'Casey Taylor', 'email' => 'demo.logistics@optima-chain.test', 'role' => 'logistics', 'password' => 'OptimaDemo!2026'],
            ['key' => 'bank_demo', 'name' => 'Demo Bank Auditor', 'email' => 'bank@demo.com', 'role' => 'bank', 'password' => 'password'],
            ['key' => 'bank_legacy', 'name' => 'Morgan Ellis', 'email' => 'demo.bank@optima-chain.test', 'role' => 'bank', 'password' => 'OptimaDemo!2026'],
            ['key' => 'distributor_demo', 'name' => 'Demo Distributor', 'email' => 'distributor@demo.com', 'role' => 'distributor', 'password' => 'password'],
            ['key' => 'distributor_legacy', 'name' => 'Avery Morgan', 'email' => 'demo.distributor@optima-chain.test', 'role' => 'distributor', 'password' => 'OptimaDemo!2026'],
        ];

        $accounts[] = [
            'key' => 'manager_3',
            'name' => 'Manager 3',
            'email' => 'manager.03@demo.optima-chain.test',
            'role' => 'manager',
            'password' => 'password',
        ];

        foreach (range(3, 5) as $number) {
            $accounts[] = [
                'key' => 'logistics_'.$number,
                'name' => 'Logistics User '.$number,
                'email' => sprintf('logistics.%02d@demo.optima-chain.test', $number),
                'role' => 'logistics',
                'password' => 'password',
            ];
        }

        $accounts[] = [
            'key' => 'bank_3',
            'name' => 'Bank Auditor 3',
            'email' => 'bank.03@demo.optima-chain.test',
            'role' => 'bank',
            'password' => 'password',
        ];

        $users = [];

        foreach ($accounts as $account) {
            $users[$account['key']] = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => $account['password'],
                    'email_verified_at' => now(),
                ],
            );
        }

        return $users;
    }

    /**
     * @param  array<string, User>  $users
     * @return list<Distributor>
     */
    private function seedDistributors(array $users): array
    {
        $distributors = [];
        $distributors[] = $this->createDistributor(
            $users['distributor_demo'],
            'Demo Distributor Supply Ltd.',
            'DEMO-ACCT-1002',
            12000,
        );
        $distributors[] = $this->createDistributor(
            $users['distributor_legacy'],
            'Northstar Distribution Co.',
            'DEMO-ACCT-1001',
            10000,
        );

        foreach (range(3, 15) as $number) {
            $user = User::query()->updateOrCreate(
                ['email' => sprintf('distributor.%02d@demo.optima-chain.test', $number)],
                [
                    'name' => 'Distributor Account '.$number,
                    'role' => 'distributor',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );
            $companyName = fake()->unique()->company().' Distribution';

            $distributors[] = $this->createDistributor(
                $user,
                $companyName,
                sprintf('OPT-DIST-%04d', $number),
                fake()->numberBetween(1000, 50000),
            );
        }

        foreach ($distributors as $index => $distributor) {
            $locationCount = $index === 0 ? 3 : ($index === 1 ? 2 : (($index % 3) + 2));
            $this->seedLocations($distributor, $locationCount, $index);
        }

        return $distributors;
    }

    private function createDistributor(User $user, string $companyName, string $accountNumber, int $monthlyTarget): Distributor
    {
        return Distributor::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $companyName,
                'account_number' => $accountNumber,
                'monthly_target' => $monthlyTarget,
            ],
        );
    }

    private function seedLocations(Distributor $distributor, int $locationCount, int $distributorIndex): void
    {
        $knownLocations = [
            ['Lagos Central', 'LGC'],
            ['Abuja North', 'ABN'],
            ['Port Harcourt South', 'PHS'],
            ['Kano West', 'KNW'],
            ['Ibadan East', 'IBE'],
            ['Enugu Main', 'ENG'],
        ];

        if ($distributorIndex === 1) {
            $knownLocations = [
                ['Central Depot', 'CENTRAL'],
                ['Riverside Depot', 'RIVER'],
                ...$knownLocations,
            ];
        }

        foreach (range(0, $locationCount - 1) as $locationIndex) {
            $known = $knownLocations[$locationIndex % count($knownLocations)];
            $locationCode = $distributorIndex === 1 && $locationIndex < 2
                ? $known[1]
                : $known[1].'-'.str_pad((string) ($distributorIndex + 1), 2, '0', STR_PAD_LEFT);
            $location = Location::query()->updateOrCreate(
                [
                    'distributor_id' => $distributor->id,
                    'code' => $locationCode,
                ],
                ['name' => $known[0]],
            );

            $this->seedMarketContext($location);
        }
    }

    private function seedMarketContext(Location $location): void
    {
        foreach (range(0, 11) as $monthOffset) {
            $observedOn = CarbonImmutable::now()->startOfMonth()->subMonths($monthOffset)->toDateString();
            MarketContext::query()->updateOrCreate(
                [
                    'location_id' => $location->id,
                    'observed_on' => $observedOn,
                ],
                [
                    'temperature_celsius' => fake()->randomFloat(2, 20, 34),
                    'weather_summary' => fake()->randomElement(['Clear skies', 'Partly cloudy', 'Light rain', 'Showers']),
                    'inflation_rate' => fake()->randomFloat(2, 2, 8),
                    'inflation_region' => fake()->randomElement(['Lagos', 'Abuja', 'South-South', 'North-Central']),
                ],
            );
        }
    }

    /**
     * @param  list<Distributor>  $distributors
     */
    private function seedForecastHistoryAndDemand(array $distributors): void
    {
        $currentYear = CarbonImmutable::now()->isoWeekYear;
        $forecastYears = [$currentYear - 1, $currentYear];
        $historyStart = CarbonImmutable::now()->startOfWeek()->subWeeks(52);
        $weeks = [];

        foreach (range(0, 51) as $weekOffset) {
            $weeks[] = $historyStart->addWeeks($weekOffset);
        }

        $distributionIndex = 0;

        foreach ($distributors as $distributor) {
            $locations = $distributor->locations()->orderBy('id')->get();
            $locationCount = max(1, $locations->count());
            $baseExpected = max(50, (int) round(((float) $distributor->monthly_target) / ($locationCount * 4.3)));

            foreach ($locations as $location) {
                $forecastRows = [];
                $forecastLookup = [];

                foreach ($forecastYears as $year) {
                    foreach (range(1, 52) as $weekNumber) {
                        $seasonalFactor = [0.92, 0.98, 1.04, 1.08, 1.02, 0.96, 1.00][$weekNumber % 7];
                        $expected = max(1, (int) round($baseExpected * $seasonalFactor));
                        $low = max(0, (int) floor($expected * 0.8));
                        $high = max($expected, (int) ceil($expected * 1.2));
                        $key = $year.'-'.$weekNumber;
                        $forecastLookup[$key] = ['low' => $low, 'expected' => $expected, 'high' => $high];
                        $forecastRows[] = [
                            'location_id' => $location->id,
                            'week_number' => $weekNumber,
                            'year' => $year,
                            'low_band' => $low,
                            'expected_band' => $expected,
                            'high_band' => $high,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                ForecastBand::query()->upsert(
                    $forecastRows,
                    ['location_id', 'year', 'week_number'],
                    ['low_band', 'expected_band', 'high_band', 'updated_at'],
                );

                $existingRequests = $distributor->demandRequests()
                    ->whereBelongsTo($location)
                    ->get()
                    ->keyBy(fn (DemandRequest $request): string => $request->year.'-'.$request->week_number);

                foreach ($weeks as $weekStart) {
                    $weekNumber = $weekStart->isoWeek;
                    $year = $weekStart->isoWeekYear;
                    $forecast = $forecastLookup[$year.'-'.$weekNumber] ?? null;

                    if (! $forecast) {
                        continue;
                    }

                    $type = $distributionIndex % 10;
                    $requested = match (true) {
                        $type <= 6 => fake()->numberBetween($forecast['low'], $forecast['high']),
                        $type <= 8 => $forecast['high'] + fake()->numberBetween(1, max(1, (int) ceil($forecast['high'] * 0.2))),
                        default => max(0, $forecast['low'] - fake()->numberBetween(1, max(1, (int) ceil($forecast['low'] * 0.2)))),
                    };
                    $approved = $type >= 7 && $type <= 8
                        ? fake()->numberBetween(max(0, (int) floor($requested * 0.8)), $requested)
                        : $requested;
                    $dispatched = max(0, $approved - fake()->numberBetween(0, max(1, (int) floor($approved * 0.05))));
                    $hasDiscrepancy = $distributionIndex % 17 === 0;
                    $confirmed = $hasDiscrepancy
                        ? max(0, $dispatched - fake()->numberBetween(1, max(1, (int) floor(max(1, $dispatched) * 0.2))))
                        : $dispatched;
                    $sales = fake()->numberBetween(0, $confirmed);
                    $request = $existingRequests->get($year.'-'.$weekNumber) ?? new DemandRequest;
                    $request->fill([
                        'distributor_id' => $distributor->id,
                        'location_id' => $location->id,
                        'week_number' => $weekNumber,
                        'year' => $year,
                        'requested_qty' => $requested,
                        'approved_qty' => $approved,
                        'dispatched_qty' => $dispatched,
                        'confirmed_qty' => $confirmed,
                        'sales_qty' => $sales,
                        'status' => 'completed',
                        'company_short_supply' => fake()->boolean(12),
                        'flag_reason' => match (true) {
                            $type <= 6 => null,
                            $type <= 8 => 'Historical request exceeded the forecast high band and was reviewed.',
                            default => 'Historical request was below the forecast low band; stockout risk was noted.',
                        },
                        'confirmation_code' => str_pad((string) fake()->numberBetween(0, 999999), 6, '0', STR_PAD_LEFT),
                        'confirmed_at' => $weekStart->addDays(5)->setTime(12, 0),
                        'created_at' => $weekStart->setTime(9, 0),
                        'updated_at' => $weekStart->addDays(5)->setTime(12, 0),
                    ])->save();

                    $existingRequests->put($year.'-'.$weekNumber, $request);

                    if ($hasDiscrepancy) {
                        Complaint::query()->updateOrCreate(
                            ['demand_request_id' => $request->id],
                            [
                                'type' => 'quantity_discrepancy',
                                'description' => sprintf(
                                    'Dispatched quantity was %d units and confirmed receipt was %d units.',
                                    $dispatched,
                                    $confirmed,
                                ),
                                'status' => 'open',
                            ],
                        );
                    }

                    $distributionIndex++;
                }
            }
        }
    }
}

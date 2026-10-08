<?php

use App\Livewire\DistributorForecastSession;
use App\Models\Distributor;
use App\Models\Location;
use App\Models\MarketContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the forecast portal for a distributor and stores forecast bands', function (): void {
    $this->travelTo('2026-10-08 12:00:00');
    Http::fake([
        'localhost:8000/api/v1/predict-band' => Http::response([
            'low_band' => 100,
            'expected_band' => 125,
            'high_band' => 160,
            'weather_summary' => 'Rainy',
        ]),
    ]);
    Http::preventStrayRequests();
    $fixture = distributorForecastFixture();

    $this->actingAs($fixture['user'])
        ->get(route('demand.request'))
        ->assertOk()
        ->assertSee('Rainy')
        ->assertSee('Plan your weekly demand');

    $this->assertDatabaseHas('forecast_bands', [
        'location_id' => $fixture['location']->getKey(),
        'week_number' => 42,
        'year' => 2026,
        'low_band' => 100,
        'expected_band' => 125,
        'high_band' => 160,
    ]);
});

it('updates the request status for low, in-band, and high quantities', function (): void {
    $this->travelTo('2026-10-08 12:00:00');
    Http::fake([
        'localhost:8000/api/v1/predict-band' => Http::response([
            'low_band' => 100,
            'expected_band' => 125,
            'high_band' => 160,
            'weather_summary' => 'Dry',
        ]),
    ]);
    Http::preventStrayRequests();
    $fixture = distributorForecastFixture();
    $this->actingAs($fixture['user']);

    Livewire::test(DistributorForecastSession::class)
        ->set('requestedQuantity', 90)
        ->assertSee('Stockout Risk Note')
        ->set('requestedQuantity', 100)
        ->assertSee('Auto-Approve Eligible')
        ->set('requestedQuantity', 125)
        ->assertSee('Auto-Approve Eligible')
        ->set('requestedQuantity', 160)
        ->assertSee('Auto-Approve Eligible')
        ->set('requestedQuantity', 180)
        ->assertSee('Flagged for Manager Review');
});

it('submits the selected weekly request through the existing request service', function (): void {
    $this->travelTo('2026-10-08 12:00:00');
    Http::fake([
        'localhost:8000/api/v1/predict-band' => Http::response([
            'low_band' => 100,
            'expected_band' => 125,
            'high_band' => 160,
            'weather_summary' => 'Cloudy',
        ]),
    ]);
    Http::preventStrayRequests();
    $fixture = distributorForecastFixture();
    $this->actingAs($fixture['user']);

    Livewire::test(DistributorForecastSession::class)
        ->set('requestedQuantity', 130)
        ->call('submitRequest')
        ->assertHasNoErrors()
        ->assertSee('Weekly request #1 submitted with status: auto approved.');

    $this->assertDatabaseHas('demand_requests', [
        'distributor_id' => $fixture['distributor']->getKey(),
        'location_id' => $fixture['location']->getKey(),
        'week_number' => 42,
        'year' => 2026,
        'requested_qty' => 130,
        'approved_qty' => 130,
        'status' => 'auto_approved',
    ]);
});

it('saves and restores a session draft for the selected location and cycle', function (): void {
    $this->travelTo('2026-10-08 12:00:00');
    Http::fake([
        'localhost:8000/api/v1/predict-band' => Http::response([
            'low_band' => 100,
            'expected_band' => 125,
            'high_band' => 160,
            'weather_summary' => 'Dry',
        ]),
    ]);
    Http::preventStrayRequests();
    $fixture = distributorForecastFixture();
    $this->actingAs($fixture['user']);

    Livewire::test(DistributorForecastSession::class)
        ->set('requestedQuantity', 140)
        ->call('saveDraft')
        ->assertSee('Draft saved for this session.');

    Livewire::test(DistributorForecastSession::class)
        ->assertSet('requestedQuantity', 140);
});

it('forbids users without a distributor role from viewing the portal', function (): void {
    $manager = User::factory()->create(['role' => 'manager']);
    $this->actingAs($manager)
        ->get(route('demand.request'))
        ->assertForbidden();
});

it('does not allow a distributor to submit a request for another distributor location', function (): void {
    $this->travelTo('2026-10-08 12:00:00');
    Http::fake([
        'localhost:8000/api/v1/predict-band' => Http::response([
            'low_band' => 100,
            'expected_band' => 125,
            'high_band' => 160,
            'weather_summary' => 'Dry',
        ]),
    ]);
    Http::preventStrayRequests();
    $fixture = distributorForecastFixture();
    $otherDistributor = Distributor::query()->create([
        'user_id' => User::factory()->create(['role' => 'distributor'])->getKey(),
        'company_name' => 'Other Forecast Portal Distributor',
        'account_number' => 'OT-'.Str::upper(Str::random(8)),
        'monthly_target' => 1000,
    ]);
    $otherLocation = $otherDistributor->locations()->create([
        'name' => 'Other Forecast Depot',
        'code' => 'OT-'.Str::upper(Str::random(8)),
    ]);
    $this->actingAs($fixture['user']);

    Livewire::test(DistributorForecastSession::class)
        ->set('locationId', $otherLocation->getKey())
        ->set('requestedQuantity', 125)
        ->call('submitRequest')
        ->assertHasErrors('locationId');

    $this->assertDatabaseMissing('demand_requests', [
        'distributor_id' => $fixture['distributor']->getKey(),
        'location_id' => $otherLocation->getKey(),
    ]);
});

function distributorForecastFixture(): array
{
    $user = User::factory()->create(['role' => 'distributor']);
    $distributor = Distributor::query()->create([
        'user_id' => $user->getKey(),
        'company_name' => 'Forecast Portal Distributor',
        'account_number' => 'FP-'.Str::upper(Str::random(8)),
        'monthly_target' => 1000,
    ]);
    $location = Location::query()->create([
        'distributor_id' => $distributor->getKey(),
        'name' => 'Forecast Portal Depot',
        'code' => 'FP-'.Str::upper(Str::random(8)),
    ]);
    MarketContext::query()->create([
        'location_id' => $location->getKey(),
        'observed_on' => '2026-10-08',
        'inflation_rate' => 0.12,
    ]);

    return compact('user', 'distributor', 'location');
}

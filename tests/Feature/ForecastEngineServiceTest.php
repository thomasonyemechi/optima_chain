<?php

use App\Models\Distributor;
use App\Models\ForecastBand;
use App\Models\Location;
use App\Models\MarketContext;
use App\Models\User;
use App\Services\ForecastEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('sends model features and returns the AI forecast bands', function (): void {
    Http::fake([
        'localhost:8000/api/v1/predict-band' => Http::response([
            'low_band' => 80,
            'expected_band' => 120,
            'high_band' => 150,
            'weather_summary' => 'Rainy',
        ]),
    ]);
    Http::preventStrayRequests();
    $this->travelTo('2026-10-08 12:00:00');

    $location = forecastEngineLocation();
    MarketContext::query()->create([
        'location_id' => (int) $location->getKey(),
        'observed_on' => '2026-10-01',
        'inflation_rate' => 0.12,
    ]);
    foreach ([100, 200, 300, 400] as $offset => $sales) {
        $weekNumber = 39 + $offset;
        $location->demandRequests()->create([
            'distributor_id' => (int) $location->getAttribute('distributor_id'),
            'week_number' => $weekNumber,
            'year' => 2026,
            'requested_qty' => $sales,
            'sales_qty' => $sales,
            'status' => 'completed',
        ]);
    }

    $bands = app(ForecastEngineService::class)->fetchForecastBands($location, 43, 2026);

    expect($bands)->toBe([
        'low' => 80.0,
        'expected' => 120.0,
        'high' => 150.0,
    ]);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://localhost:8000/api/v1/predict-band'
        && $request->data() === [
            'location_id' => (int) $location->getKey(),
            'latitude' => 6.74716,
            'longitude' => 4.8761,
            'week_number' => 43,
            'year' => 2026,
            'inflation_rate' => 0.12,
            'past_sales_avg' => 250.0,
        ]);
});

it('returns the four-week fallback when the AI service cannot be reached', function (): void {
    Http::fake([
        'localhost:8000/api/v1/predict-band' => Http::failedConnection(),
    ]);
    Http::preventStrayRequests();
    $this->travelTo('2026-10-08 12:00:00');

    $location = forecastEngineLocation();
    MarketContext::query()->create([
        'location_id' => (int) $location->getKey(),
        'observed_on' => '2026-10-01',
        'inflation_rate' => 0.12,
    ]);
    foreach ([100, 200, 300, 400] as $offset => $sales) {
        $location->demandRequests()->create([
            'distributor_id' => (int) $location->getAttribute('distributor_id'),
            'week_number' => 39 + $offset,
            'year' => 2026,
            'requested_qty' => $sales,
            'sales_qty' => $sales,
            'status' => 'completed',
        ]);
    }
    $location->demandRequests()->create([
        'distributor_id' => (int) $location->getAttribute('distributor_id'),
        'week_number' => 42,
        'year' => 2026,
        'requested_qty' => 900,
        'sales_qty' => 900,
        'company_short_supply' => true,
        'status' => 'completed',
    ]);

    $bands = app(ForecastEngineService::class)->fetchForecastBands($location, 43, 2026);

    expect($bands)->toBe([
        'low' => 212.5,
        'expected' => 250.0,
        'high' => 287.5,
    ]);
});

it('sends completed monthly sales for drift evaluation and logs alerts', function (): void {
    Http::fake([
        'localhost:8000/api/v1/evaluate-drift' => Http::response([
            'status' => 'alert',
            'message' => 'Forecast drift detected. Retraining required.',
        ]),
    ]);
    Http::preventStrayRequests();
    Log::shouldReceive('critical')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'drift')
            && $context['month'] === 10
            && $context['year'] === 2026
            && $context['sample_count'] === 1);
    $this->travelTo('2026-10-15 12:00:00');

    $location = forecastEngineLocation();
    ForecastBand::query()->create([
        'location_id' => (int) $location->getKey(),
        'week_number' => 40,
        'year' => 2026,
        'low_band' => 80,
        'expected_band' => 100,
        'high_band' => 120,
    ]);
    $location->demandRequests()->create([
        'distributor_id' => (int) $location->getAttribute('distributor_id'),
        'week_number' => 40,
        'year' => 2026,
        'requested_qty' => 120,
        'sales_qty' => 150,
        'status' => 'completed',
    ]);
    $this->travelTo('2026-09-30 12:00:00');
    $location->demandRequests()->create([
        'distributor_id' => (int) $location->getAttribute('distributor_id'),
        'week_number' => 40,
        'year' => 2026,
        'requested_qty' => 120,
        'sales_qty' => 120,
        'status' => 'completed',
    ]);
    $this->travelTo('2026-10-15 12:00:00');
    $location->demandRequests()->create([
        'distributor_id' => (int) $location->getAttribute('distributor_id'),
        'week_number' => 40,
        'year' => 2026,
        'requested_qty' => 120,
        'sales_qty' => 120,
        'status' => 'delivered',
    ]);

    $result = app(ForecastEngineService::class)->checkMonthlyModelHealth(10, 2026);

    expect($result)->toBe([
        'status' => 'alert',
        'message' => 'Forecast drift detected. Retraining required.',
        'sample_count' => 1,
    ]);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://localhost:8000/api/v1/evaluate-drift'
        && $request->data() === [
            'actual_sales' => [150.0],
            'predicted_sales' => [100.0],
        ]);
});

function forecastEngineLocation(): Location
{
    $user = User::factory()->create(['role' => 'distributor']);
    $distributor = Distributor::query()->create([
        'user_id' => (int) $user->getKey(),
        'company_name' => 'Forecast Engine Distributor',
        'account_number' => 'FC-'.Str::upper(Str::random(8)),
        'monthly_target' => 1000,
    ]);

    return $distributor->locations()->create([
        'name' => 'Forecast Engine Depot',
        'code' => 'FE-'.Str::upper(Str::random(8)),
    ]);
}

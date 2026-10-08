<?php

use App\Livewire\ManagerForecastOverview;
use App\Models\DemandRequest;
use App\Models\Distributor;
use App\Models\ForecastBand;
use App\Models\Location;
use App\Models\MarketContext;
use App\Models\PerformanceRecord;
use App\Models\User;
use App\Services\DemandRequestService;
use App\Services\PerformanceScoringService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function supplyChainFixture(): array
{
    $user = User::factory()->create(['role' => 'distributor']);
    $distributor = Distributor::query()->create([
        'user_id' => $user->id,
        'company_name' => 'Northstar Distribution',
        'account_number' => 'ACCT-'.Str::upper(Str::random(8)),
        'monthly_target' => 100,
    ]);
    $location = Location::query()->create([
        'distributor_id' => $distributor->id,
        'name' => 'Central Depot',
        'code' => 'CENTRAL',
    ]);
    $forecast = ForecastBand::query()->create([
        'location_id' => $location->id,
        'week_number' => now()->isoWeek,
        'year' => now()->isoWeekYear,
        'low_band' => 5,
        'expected_band' => 10,
        'high_band' => 15,
    ]);

    return compact('user', 'distributor', 'location', 'forecast');
}

it('automatically approves requests inside the forecast and flags requests above it', function (): void {
    $fixture = supplyChainFixture();
    $service = app(DemandRequestService::class);

    $approved = $service->createRequest($fixture['distributor'], $fixture['location'], $fixture['forecast']->week_number, $fixture['forecast']->year, 10);
    $flagged = $service->createRequest($fixture['distributor'], $fixture['location'], $fixture['forecast']->week_number, $fixture['forecast']->year, 18);

    expect($approved->status)->toBe('auto_approved')
        ->and($approved->approved_qty)->toBe(10)
        ->and($flagged->status)->toBe('flagged')
        ->and($flagged->approved_qty)->toBeNull()
        ->and($flagged->flag_reason)->toContain('forecast high');
});

it('creates one discrepancy complaint when a receipt differs from dispatched quantity', function (): void {
    $fixture = supplyChainFixture();
    $service = app(DemandRequestService::class);
    $demandRequest = $service->createRequest($fixture['distributor'], $fixture['location'], $fixture['forecast']->week_number, $fixture['forecast']->year, 10);
    $demandRequest = $service->dispatch($demandRequest, 9);

    $service->confirmReceipt($demandRequest, $demandRequest->confirmation_code, 8);

    expect($demandRequest->refresh()->confirmed_qty)->toBe(8)
        ->and($demandRequest->complaint()->value('type'))->toBe('quantity_discrepancy')
        ->and($demandRequest->complaint()->count())->toBe(1);
});

it('persists the weekly request, dispatch, receipt, and sales route workflow', function (): void {
    $fixture = supplyChainFixture();

    $this->actingAs($fixture['user'])
        ->post(route('demand.request.store'), [
            'location_id' => $fixture['location']->id,
            'requested_qty' => 12,
        ])
        ->assertRedirect(route('demand.request'));

    $demandRequest = DemandRequest::query()->latest('id')->firstOrFail();
    expect($demandRequest->status)->toBe('auto_approved');

    $manager = User::factory()->create(['role' => 'manager']);
    $this->actingAs($manager)
        ->post(route('manager.dispatch.store', $demandRequest), ['dispatched_qty' => 11])
        ->assertRedirect(route('manager.dispatch'));

    $demandRequest->refresh();
    expect($demandRequest->confirmation_code)->toMatch('/^[0-9]{6}$/');

    $this->actingAs($fixture['user'])
        ->post(route('receipt.confirm.store'), [
            'demand_request_id' => $demandRequest->id,
            'confirmation_code' => $demandRequest->confirmation_code,
            'confirmed_qty' => 10,
        ])
        ->assertRedirect(route('receipt.confirm'));

    $this->post(route('sales.entry.store'), [
        'location_id' => $fixture['location']->id,
        'week_ending' => now()->toDateString(),
        'sales_qty' => 9,
    ])->assertRedirect(route('sales.entry'));

    expect($demandRequest->refresh()->sales_qty)->toBe(9)
        ->and($demandRequest->status)->toBe('completed')
        ->and($demandRequest->complaint()->exists())->toBeTrue();
});

it('allows managers to approve flagged requests with a short-supply exemption', function (): void {
    $fixture = supplyChainFixture();
    $demandRequest = app(DemandRequestService::class)->createRequest(
        $fixture['distributor'],
        $fixture['location'],
        $fixture['forecast']->week_number,
        $fixture['forecast']->year,
        18,
    );
    $manager = User::factory()->create(['role' => 'manager']);

    $this->actingAs($manager)
        ->post(route('manager.review-queue.review'), [
            'demand_request_id' => $demandRequest->id,
            'decision' => 'adjust',
            'approved_qty' => 12,
            'company_short_supply' => '1',
        ])
        ->assertRedirect(route('manager.review-queue'));

    expect($demandRequest->refresh()->status)->toBe('adjusted')
        ->and($demandRequest->approved_qty)->toBe(12)
        ->and($demandRequest->company_short_supply)->toBeTrue()
        ->and($demandRequest->flag_reason)->toBeNull();
});

it('uses approved quantity for short-supply accuracy and applies weighted scoring', function (): void {
    $fixture = supplyChainFixture();
    $fixture['distributor']->demandRequests()->create([
        'location_id' => $fixture['location']->id,
        'week_number' => $fixture['forecast']->week_number,
        'year' => $fixture['forecast']->year,
        'requested_qty' => 160,
        'approved_qty' => 100,
        'sales_qty' => 100,
        'company_short_supply' => true,
        'status' => 'completed',
    ]);

    $record = app(PerformanceScoringService::class)->calculateForPeriod(
        $fixture['distributor'],
        now()->month,
        now()->year,
        80,
        60,
    );

    expect((float) $record->accuracy_score)->toBe(100.0)
        ->and((float) $record->target_score)->toBe(100.0)
        ->and((float) $record->total_score)->toBe(90.0)
        ->and(Str::isUuid($record->verification_code))->toBeTrue();
});

it('restricts distributor pages by authentication and role', function (): void {
    supplyChainFixture();
    $this->get('/demand/request')->assertRedirect(route('login'));

    $manager = User::factory()->create(['role' => 'manager']);

    $this->actingAs($manager)->get('/demand/request')->assertForbidden();
});

it('does not allow one distributor to confirm another distributor receipt', function (): void {
    $fixture = supplyChainFixture();
    $demandRequest = app(DemandRequestService::class)->createRequest(
        $fixture['distributor'],
        $fixture['location'],
        $fixture['forecast']->week_number,
        $fixture['forecast']->year,
        10,
    );
    $demandRequest = app(DemandRequestService::class)->dispatch($demandRequest, 10);
    $otherUser = User::factory()->create(['role' => 'distributor']);
    Distributor::query()->create([
        'user_id' => $otherUser->id,
        'company_name' => 'Other Distribution',
        'account_number' => 'ACCT-'.Str::upper(Str::random(8)),
        'monthly_target' => 100,
    ]);

    $this->actingAs($otherUser)
        ->post(route('receipt.confirm.store'), [
            'demand_request_id' => $demandRequest->id,
            'confirmation_code' => $demandRequest->confirmation_code,
            'confirmed_qty' => 10,
        ])
        ->assertNotFound();
});

it('renders a non-verified response for an unknown public record code', function (): void {
    $this->get('/verify/'.Str::uuid())
        ->assertOk()
        ->assertSee('RECORD NOT VERIFIED');
});

it('renders a verified public record with an integrity hash', function (): void {
    $fixture = supplyChainFixture();
    $record = PerformanceRecord::query()->create([
        'distributor_id' => $fixture['distributor']->id,
        'month' => now()->month,
        'year' => now()->year,
        'accuracy_score' => 91,
        'target_score' => 88,
        'timeliness_score' => 95,
        'payment_score' => 100,
        'total_score' => 92,
    ]);

    $this->get(route('verify.record', $record->verification_code))
        ->assertSee('VERIFIED RECORD')
        ->assertSee('Northstar Distribution')
        ->assertSee(hash('sha256', implode('|', [
            $record->id,
            $record->verification_code,
            $record->distributor_id,
            $record->month,
            $record->year,
            $record->accuracy_score,
            $record->target_score,
            $record->timeliness_score,
            $record->payment_score,
            $record->total_score,
            $record->updated_at->toIso8601String(),
        ])));
});

it('seeds demo logins that can authenticate', function (): void {
    $this->seed(DatabaseSeeder::class);

    $this->post(route('login.store'), [
        'email' => 'demo.distributor@optima-chain.test',
        'password' => 'OptimaDemo!2026',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs(User::query()->where('email', 'demo.distributor@optima-chain.test')->firstOrFail());
});

it('shows current forecasts with search, risk filters, and historical detail context', function (): void {
    $fixture = supplyChainFixture();
    $manager = User::factory()->create(['role' => 'manager']);
    $previousWeek = now()->subWeek();

    ForecastBand::query()->create([
        'location_id' => $fixture['location']->id,
        'week_number' => $previousWeek->isoWeek,
        'year' => $previousWeek->isoWeekYear,
        'low_band' => 60,
        'expected_band' => 80,
        'high_band' => 100,
    ]);
    $fixture['distributor']->demandRequests()->create([
        'location_id' => $fixture['location']->id,
        'week_number' => $previousWeek->isoWeek,
        'year' => $previousWeek->isoWeekYear,
        'requested_qty' => 80,
        'approved_qty' => 80,
        'dispatched_qty' => 80,
        'confirmed_qty' => 80,
        'sales_qty' => 75,
        'status' => 'completed',
    ]);
    MarketContext::query()->create([
        'location_id' => $fixture['location']->id,
        'observed_on' => now()->subDay()->toDateString(),
        'temperature_celsius' => 19.5,
        'weather_summary' => 'Light rain',
        'inflation_rate' => 2.7,
        'inflation_region' => 'North Region',
    ]);

    Livewire::actingAs($manager)
        ->test(ManagerForecastOverview::class)
        ->assertSee('Distributor Demand Forecasts')
        ->assertSee('Northstar Distribution')
        ->assertSee('Auto-approved')
        ->set('search', 'no-match')
        ->assertSee('No forecasts match these filters')
        ->set('search', 'Northstar')
        ->set('riskFilter', 'within')
        ->assertSee('Auto-approved')
        ->call('openDetails', $fixture['location']->id)
        ->assertSet('showDetails', true)
        ->assertSee('Historical sales vs forecast')
        ->assertSee('Light rain')
        ->assertSee('North Region');
});

it('filters current forecasts by saved request status and high risk', function (): void {
    $fixture = supplyChainFixture();
    $manager = User::factory()->create(['role' => 'manager']);
    $fixture['distributor']->demandRequests()->create([
        'location_id' => $fixture['location']->id,
        'week_number' => $fixture['forecast']->week_number,
        'year' => $fixture['forecast']->year,
        'requested_qty' => 20,
        'status' => 'flagged',
    ]);
    $secondLocation = Location::query()->create([
        'distributor_id' => $fixture['distributor']->id,
        'name' => 'Harbor Depot',
        'code' => 'HARBOR',
    ]);
    ForecastBand::query()->create([
        'location_id' => $secondLocation->id,
        'week_number' => $fixture['forecast']->week_number,
        'year' => $fixture['forecast']->year,
        'low_band' => 5,
        'expected_band' => 10,
        'high_band' => 15,
    ]);
    $fixture['distributor']->demandRequests()->create([
        'location_id' => $secondLocation->id,
        'week_number' => $fixture['forecast']->week_number,
        'year' => $fixture['forecast']->year,
        'requested_qty' => 10,
        'approved_qty' => 10,
        'status' => 'auto_approved',
    ]);

    $component = Livewire::actingAs($manager)
        ->test(ManagerForecastOverview::class)
        ->set('statusFilter', 'flagged');

    expect($component->instance()->forecastRows->pluck('location.id')->all())->toBe([$fixture['location']->id]);
    $component->assertSee('CENTRAL');

    $component->set('statusFilter', '')
        ->set('highRiskOnly', true);

    expect($component->instance()->forecastRows->pluck('location.id')->all())->toBe([$fixture['location']->id]);
    $component->assertSee('CENTRAL');
});

it('shows five-year seasonal and four-week request analytics in location details', function (): void {
    $fixture = supplyChainFixture();
    $manager = User::factory()->create(['role' => 'manager']);

    foreach (range(now()->isoWeekYear - 4, now()->isoWeekYear) as $year) {
        $fixture['distributor']->demandRequests()->create([
            'location_id' => $fixture['location']->id,
            'week_number' => 10,
            'year' => $year,
            'requested_qty' => 14,
            'dispatched_qty' => 12,
            'confirmed_qty' => 11,
            'sales_qty' => 9,
            'status' => 'completed',
        ]);
    }

    Livewire::actingAs($manager)
        ->test(ManagerForecastOverview::class)
        ->call('openDetails', $fixture['location']->id)
        ->assertSee('Five-year seasonal sales comparison')
        ->assertSee('Past 4 weeks: requested vs delivered vs sold')
        ->assertSee('DELIVERED (D)')
        ->assertSee('9.0');
});

it('allows managers to approve or override requests awaiting review', function (): void {
    $fixture = supplyChainFixture();
    $manager = User::factory()->create(['role' => 'manager']);
    $flaggedRequest = $fixture['distributor']->demandRequests()->create([
        'location_id' => $fixture['location']->id,
        'week_number' => $fixture['forecast']->week_number,
        'year' => $fixture['forecast']->year,
        'requested_qty' => 20,
        'status' => 'flagged',
    ]);

    Livewire::actingAs($manager)
        ->test(ManagerForecastOverview::class)
        ->call('openDetails', $fixture['location']->id, $flaggedRequest->id)
        ->set('companyShortSupply', true)
        ->call('approveRequested')
        ->assertSee('Request approved at the requested quantity.');

    expect($flaggedRequest->refresh()->status)->toBe('auto_approved')
        ->and($flaggedRequest->approved_qty)->toBe(20)
        ->and($flaggedRequest->company_short_supply)->toBeTrue();

    $secondRequest = $fixture['distributor']->demandRequests()->create([
        'location_id' => $fixture['location']->id,
        'week_number' => $fixture['forecast']->week_number,
        'year' => $fixture['forecast']->year,
        'requested_qty' => 18,
        'status' => 'flagged',
    ]);

    Livewire::actingAs($manager)
        ->test(ManagerForecastOverview::class)
        ->call('openDetails', $fixture['location']->id, $secondRequest->id)
        ->set('approvedQuantity', 12)
        ->call('overrideRequest')
        ->assertHasNoErrors()
        ->assertSee('The adjusted request was saved.');

    expect($secondRequest->refresh()->status)->toBe('adjusted')
        ->and($secondRequest->approved_qty)->toBe(12);
});

it('rejects overrides above the request and prevents review by non-managers', function (): void {
    $fixture = supplyChainFixture();
    $manager = User::factory()->create(['role' => 'manager']);
    $flaggedRequest = $fixture['distributor']->demandRequests()->create([
        'location_id' => $fixture['location']->id,
        'week_number' => $fixture['forecast']->week_number,
        'year' => $fixture['forecast']->year,
        'requested_qty' => 20,
        'status' => 'flagged',
    ]);

    Livewire::actingAs($manager)
        ->test(ManagerForecastOverview::class)
        ->call('openDetails', $fixture['location']->id, $flaggedRequest->id)
        ->set('approvedQuantity', 21)
        ->call('overrideRequest')
        ->assertHasErrors(['approvedQuantity' => 'max']);

    expect($flaggedRequest->refresh()->status)->toBe('flagged')
        ->and($flaggedRequest->approved_qty)->toBeNull();

});

it('forbids non-manager roles from mounting the forecast component', function (): void {
    $fixture = supplyChainFixture();

    Livewire::actingAs($fixture['user'])
        ->test(ManagerForecastOverview::class)
        ->assertForbidden();
});

<?php

use App\Models\Complaint;
use App\Models\DemandRequest;
use App\Models\Distributor;
use App\Models\ForecastBand;
use App\Models\Location;
use App\Models\PerformanceRecord;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds demo accounts with the requested roles and credentials', function (): void {
    $this->travelTo('2026-10-08 12:00:00');

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('role', 'admin')->count())->toBe(1)
        ->and(User::query()->where('role', 'manager')->count())->toBe(3)
        ->and(User::query()->where('role', 'logistics')->count())->toBe(5)
        ->and(User::query()->where('role', 'bank')->count())->toBe(3)
        ->and(User::query()->where('role', 'distributor')->count())->toBe(15);

    foreach ([
        'manager@demo.com' => 'manager',
        'distributor@demo.com' => 'distributor',
        'logistics@demo.com' => 'logistics',
        'bank@demo.com' => 'bank',
    ] as $email => $role) {
        $user = User::query()->where('email', $email)->firstOrFail();

        expect($user->role)->toBe($role)
            ->and(Hash::check('password', $user->password))->toBeTrue();
    }
});

it('seeds year-round forecasts, a year of fulfilled requests, complaints, and monthly scores', function (): void {
    $this->travelTo('2026-10-08 12:00:00');

    $this->seed(DatabaseSeeder::class);

    $locationCount = Location::query()->count();
    $currentYear = now()->isoWeekYear;
    $previousYear = $currentYear - 1;

    expect(Distributor::query()->count())->toBe(15)
        ->and(ForecastBand::query()->where('year', $currentYear)->count())->toBe($locationCount * 52)
        ->and(ForecastBand::query()->where('year', $previousYear)->count())->toBe($locationCount * 52)
        ->and(DemandRequest::query()->count())->toBe($locationCount * 52)
        ->and(DemandRequest::query()->where('status', 'completed')->whereNotNull('approved_qty')->whereNotNull('dispatched_qty')->whereNotNull('confirmed_qty')->whereNotNull('sales_qty')->count())->toBe($locationCount * 52)
        ->and(Complaint::query()->count())->toBeGreaterThan(0)
        ->and(PerformanceRecord::query()->count())->toBe(15 * 12);

    $lowRiskRequests = DemandRequest::query()
        ->where('flag_reason', 'like', '%below the forecast low band%')
        ->count();

    expect($lowRiskRequests)->toBeGreaterThan(0);
});

it('provides model factories and role or fulfillment states for demo data', function (): void {
    $users = [
        User::factory()->admin()->create(),
        User::factory()->manager()->create(),
        User::factory()->logistics()->create(),
        User::factory()->bank()->create(),
        User::factory()->distributor()->create(),
    ];
    $distributor = Distributor::factory()->create();
    $location = Location::factory()->for($distributor)->create();
    $forecast = ForecastBand::factory()->for($location)->create();
    $autoApproved = DemandRequest::factory()->for($distributor)->for($location)->autoApproved()->create();
    $flagged = DemandRequest::factory()->for($distributor)->for($location)->flagged()->create();
    $stockoutRisk = DemandRequest::factory()->for($distributor)->for($location)->stockoutRisk()->create();
    $fulfilled = DemandRequest::factory()->for($distributor)->for($location)->fulfilled()->create();
    $complaint = Complaint::factory()->create();
    $performance = PerformanceRecord::factory()->for($distributor)->create();

    expect(array_map(fn (User $user): string => $user->role, $users))
        ->toBe(['admin', 'manager', 'logistics', 'bank', 'distributor'])
        ->and($distributor->account_number)->toStartWith('DIST-')
        ->and((float) $distributor->monthly_target)->toBeGreaterThanOrEqual(1000)
        ->and((float) $distributor->monthly_target)->toBeLessThanOrEqual(50000)
        ->and($forecast->location_id)->toBe($location->id)
        ->and($autoApproved->status)->toBe('auto_approved')
        ->and($autoApproved->approved_qty)->toBe($autoApproved->requested_qty)
        ->and($flagged->status)->toBe('flagged')
        ->and($stockoutRisk->status)->toBe('pending')
        ->and($stockoutRisk->flag_reason)->toContain('below the forecast low band')
        ->and($fulfilled->status)->toBe('completed')
        ->and($fulfilled->confirmed_qty)->not->toBeNull()
        ->and($complaint->demandRequest->dispatched_qty)->not->toBe($complaint->demandRequest->confirmed_qty)
        ->and($performance->verification_code)->toMatch('/^[0-9a-f-]{36}$/');
});

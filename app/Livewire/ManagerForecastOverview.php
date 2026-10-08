<?php

namespace App\Livewire;

use App\Models\DemandRequest;
use App\Models\ForecastBand;
use App\Models\Location;
use App\Services\DemandRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ManagerForecastOverview extends Component
{
    public string $search = '';

    public string $locationFilter = '';

    public string $riskFilter = '';

    public string $statusFilter = '';

    public bool $highRiskOnly = false;

    public bool $showDetails = false;

    public ?int $selectedLocationId = null;

    public ?int $selectedDemandRequestId = null;

    public int $approvedQuantity = 0;

    public bool $companyShortSupply = false;

    public ?string $reviewNotice = null;

    public function boot(): void
    {
        abort_unless(
            auth()->check() && in_array(auth()->user()->role, ['manager', 'admin'], true),
            403,
        );
    }

    public function openDetails(int $locationId, ?int $demandRequestId = null): void
    {
        abort_unless(
            Location::query()->whereKey($locationId)->exists(),
            404,
        );

        $request = $demandRequestId
            ? DemandRequest::query()
                ->where('location_id', $locationId)
                ->findOrFail($demandRequestId)
            : null;

        $this->selectedLocationId = $locationId;
        $this->selectedDemandRequestId = $request?->id;
        $this->approvedQuantity = $request?->requested_qty ?? 0;
        $this->companyShortSupply = $request?->company_short_supply ?? false;
        $this->reviewNotice = null;
        $this->showDetails = true;
    }

    public function approveRequested(DemandRequestService $service): void
    {
        $request = $this->selectedReviewRequest();

        $service->review(
            $request,
            'approve',
            $request->requested_qty,
            $this->companyShortSupply,
        );

        $this->reviewNotice = 'Request approved at the requested quantity.';
    }

    public function overrideRequest(DemandRequestService $service): void
    {
        $request = $this->selectedReviewRequest();

        $this->validate([
            'approvedQuantity' => ['required', 'integer', 'min:0', 'max:'.$request->requested_qty],
            'companyShortSupply' => ['boolean'],
        ]);

        $service->review(
            $request,
            'adjust',
            $this->approvedQuantity,
            $this->companyShortSupply,
        );

        $this->reviewNotice = 'The adjusted request was saved.';
    }

    public function closeDetails(): void
    {
        $this->showDetails = false;
        $this->selectedLocationId = null;
        $this->selectedDemandRequestId = null;
        $this->reviewNotice = null;
        $this->resetValidation();
    }

    #[Computed]
    public function forecastRows(): Collection
    {
        $period = now();

        $forecasts = ForecastBand::query()
            ->with([
                'location.distributor',
                'location.demandRequests' => fn (HasMany $query) => $query
                    ->where('year', $period->isoWeekYear)
                    ->where('week_number', $period->isoWeek)
                    ->latest('id'),
            ])
            ->where('week_number', $period->isoWeek)
            ->where('year', $period->isoWeekYear)
            ->whereHas('location.distributor')
            ->when($this->locationFilter !== '', function (Builder $query): void {
                $query->where('location_id', $this->locationFilter);
            })
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.addcslashes(trim($this->search), '%_\\').'%';

                $query->whereHas('location', function (Builder $locationQuery) use ($search): void {
                    $locationQuery
                        ->where('code', 'like', $search)
                        ->orWhereHas('distributor', fn (Builder $distributorQuery) => $distributorQuery
                            ->where('company_name', 'like', $search));
                });
            })
            ->orderBy('location_id')
            ->get();

        return $forecasts
            ->map(function (ForecastBand $forecast): array {
                $request = $forecast->location->demandRequests->first();
                $requestedQuantity = $request?->requested_qty;
                $risk = $requestedQuantity === null
                    ? 'pending'
                    : (($requestedQuantity < $forecast->low_band || $requestedQuantity > $forecast->high_band) ? 'outside' : 'within');
                $status = $request?->status ?? 'pending';
                $statusCategory = match ($status) {
                    'flagged' => 'flagged',
                    'pending' => 'pending',
                    default => 'auto_approved',
                };

                return [
                    'forecast' => $forecast,
                    'location' => $forecast->location,
                    'distributor' => $forecast->location->distributor,
                    'request' => $request,
                    'requested_quantity' => $requestedQuantity,
                    'risk' => $risk,
                    'outside_side' => $requestedQuantity === null
                        ? null
                        : ($requestedQuantity < $forecast->low_band ? 'low' : ($requestedQuantity > $forecast->high_band ? 'high' : null)),
                    'status' => $status,
                    'status_category' => $statusCategory,
                    'reviewable' => $request && in_array($request->status, ['flagged', 'pending'], true),
                    'high_risk' => $requestedQuantity !== null && $requestedQuantity > $forecast->high_band,
                ];
            })
            ->filter(function (array $row): bool {
                if ($this->riskFilter !== '' && $row['risk'] !== $this->riskFilter) {
                    return false;
                }

                if ($this->statusFilter !== '' && $row['status_category'] !== $this->statusFilter) {
                    return false;
                }

                return ! $this->highRiskOnly || $row['high_risk'];
            })
            ->values();
    }

    #[Computed]
    public function locationOptions(): Collection
    {
        return Location::query()
            ->whereHas('forecastBands', fn (Builder $query) => $query
                ->where('week_number', now()->isoWeek)
                ->where('year', now()->isoWeekYear))
            ->with('distributor:id,company_name')
            ->orderBy('code')
            ->get(['id', 'distributor_id', 'name', 'code']);
    }

    #[Computed]
    public function selectedLocationDetails(): ?array
    {
        if (! $this->selectedLocationId) {
            return null;
        }

        $location = Location::query()
            ->with('distributor:id,company_name')
            ->find($this->selectedLocationId);

        if (! $location) {
            return null;
        }

        $year = now()->isoWeekYear;
        $seasonalYears = range($year - 4, $year);
        $seasonalSales = $location->demandRequests()
            ->whereNotNull('sales_qty')
            ->whereBetween('year', [$year - 4, $year])
            ->get(['year', 'week_number', 'sales_qty'])
            ->groupBy(function (DemandRequest $request): string {
                return sprintf('%d-%02d', now()->setISODate($request->year, $request->week_number, 1)->year, now()->setISODate($request->year, $request->week_number, 1)->month);
            });

        $seasonalComparison = collect(range(1, 12))
            ->map(function (int $month) use ($seasonalSales, $seasonalYears): array {
                $values = [];

                foreach ($seasonalYears as $seasonalYear) {
                    $key = sprintf('%d-%02d', $seasonalYear, $month);
                    $sales = $seasonalSales->get($key, collect())->pluck('sales_qty');
                    $values[$seasonalYear] = $sales->isEmpty() ? null : round((float) $sales->avg(), 1);
                }

                return [
                    'month' => now()->month($month)->format('M'),
                    'values' => $values,
                ];
            });

        $weekPeriods = collect(range(0, 3))
            ->map(fn (int $weeksAgo) => now()->subWeeks($weeksAgo))
            ->values();
        $recentRequests = $location->demandRequests()
            ->where(function (Builder $query) use ($weekPeriods): void {
                foreach ($weekPeriods as $index => $period) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}(function (Builder $periodQuery) use ($period): void {
                        $periodQuery
                            ->where('year', $period->isoWeekYear)
                            ->where('week_number', $period->isoWeek);
                    });
                }
            })
            ->get(['year', 'week_number', 'requested_qty', 'confirmed_qty', 'sales_qty'])
            ->groupBy(fn (DemandRequest $request): string => sprintf('%d-%02d', $request->year, $request->week_number));

        $weeklyMetrics = $weekPeriods
            ->reverse()
            ->map(function ($period) use ($recentRequests): array {
                $requests = $recentRequests->get(sprintf('%d-%02d', $period->isoWeekYear, $period->isoWeek), collect());

                return [
                    'label' => sprintf('W%02d · %d', $period->isoWeek, $period->isoWeekYear),
                    'requested' => $requests->isEmpty() ? null : (int) $requests->sum('requested_qty'),
                    'delivered' => $requests->isEmpty() || $requests->every(fn (DemandRequest $request): bool => $request->confirmed_qty === null)
                        ? null
                        : (int) $requests->sum('confirmed_qty'),
                    'sold' => $requests->isEmpty() || $requests->every(fn (DemandRequest $request): bool => $request->sales_qty === null)
                        ? null
                        : (int) $requests->sum('sales_qty'),
                ];
            })
            ->values();

        $history = $location->demandRequests()
            ->with('location.forecastBands')
            ->whereNotNull('sales_qty')
            ->whereBetween('year', [$year - 4, $year])
            ->orderByDesc('year')
            ->orderByDesc('week_number')
            ->limit(52)
            ->get()
            ->map(function ($request): array {
                $forecast = $request->location->forecastBands->first(fn ($band): bool => $band->year === $request->year && $band->week_number === $request->week_number
                );

                return [
                    'label' => sprintf('W%02d', $request->week_number),
                    'sales' => (int) $request->sales_qty,
                    'forecast' => (int) ($forecast?->expected_band ?? 0),
                    'accuracy' => $request->sales_qty > 0
                        ? max(0, round((1 - (abs(($forecast?->expected_band ?? 0) - $request->sales_qty) / $request->sales_qty)) * 100, 1))
                        : 0,
                ];
            })
            ->reverse()
            ->values();

        $maximum = max(1, (int) $history->max(fn (array $point): int => max($point['sales'], $point['forecast'])));
        $history = $history->map(function (array $point) use ($maximum): array {
            $point['sales_y'] = ($point['sales'] / $maximum) * 100;
            $point['forecast_y'] = ($point['forecast'] / $maximum) * 100;

            return $point;
        });

        return [
            'location' => $location,
            'seasonal_years' => $seasonalYears,
            'seasonal_comparison' => $seasonalComparison,
            'weekly_metrics' => $weeklyMetrics,
            'history' => $history,
            'review_request' => $this->selectedDemandRequestId
                ? $location->demandRequests()->find($this->selectedDemandRequestId)
                : null,
            'contexts' => $location->marketContexts()
                ->latest('observed_on')
                ->limit(5)
                ->get(),
        ];
    }

    public function render(): View
    {
        return view('livewire.manager-forecast-overview')
            ->layout('layouts.app', ['title' => 'Distributor Demand Forecasts']);
    }

    private function selectedReviewRequest(): DemandRequest
    {
        if (! $this->selectedLocationId || ! $this->selectedDemandRequestId) {
            throw ValidationException::withMessages([
                'demand_request' => 'Select a submitted request before reviewing it.',
            ]);
        }

        return DemandRequest::query()
            ->where('location_id', $this->selectedLocationId)
            ->findOrFail($this->selectedDemandRequestId);
    }
}

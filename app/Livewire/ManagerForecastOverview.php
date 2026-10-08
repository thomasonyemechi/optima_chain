<?php

namespace App\Livewire;

use App\Models\ForecastBand;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ManagerForecastOverview extends Component
{
    public string $search = '';

    public string $locationFilter = '';

    public string $riskFilter = '';

    public bool $showDetails = false;

    public ?int $selectedLocationId = null;

    public function boot(): void
    {
        abort_unless(
            auth()->check() && in_array(auth()->user()->role, ['manager', 'admin'], true),
            403,
        );
    }

    public function openDetails(int $locationId): void
    {
        abort_unless(
            Location::query()->whereKey($locationId)->exists(),
            404,
        );

        $this->selectedLocationId = $locationId;
        $this->showDetails = true;
    }

    public function closeDetails(): void
    {
        $this->showDetails = false;
        $this->selectedLocationId = null;
    }

    #[Computed]
    public function forecastRows(): Collection
    {
        $period = now();

        $forecasts = ForecastBand::query()
            ->with([
                'location.distributor',
                'location.demandRequests' => fn (HasMany $query) => $query->latest('year')->latest('week_number')->latest('id'),
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
                $request = $forecast->location->demandRequests
                    ->sortByDesc(fn ($demandRequest): array => [$demandRequest->year, $demandRequest->week_number, $demandRequest->id])
                    ->first();

                $requestForecast = $request
                    ? $forecast->location->forecastBands
                        ->first(fn ($band): bool => $band->year === $request->year && $band->week_number === $request->week_number)
                    : $forecast;

                $requestedQuantity = $request?->requested_qty;
                $comparisonBand = $requestForecast ?? $forecast;
                $risk = $requestedQuantity === null
                    ? 'pending'
                    : (($requestedQuantity < $comparisonBand->low_band || $requestedQuantity > $comparisonBand->high_band) ? 'outside' : 'within');

                $status = $requestedQuantity === null
                    ? 'pending'
                    : ($risk === 'within' ? 'auto_approved' : 'flagged');

                return [
                    'forecast' => $forecast,
                    'location' => $forecast->location,
                    'distributor' => $forecast->location->distributor,
                    'request' => $request,
                    'requested_quantity' => $requestedQuantity,
                    'risk' => $risk,
                    'outside_side' => $requestedQuantity === null
                        ? null
                        : ($requestedQuantity < $comparisonBand->low_band ? 'low' : ($requestedQuantity > $comparisonBand->high_band ? 'high' : null)),
                    'status' => $status,
                ];
            })
            ->filter(function (array $row): bool {
                return $this->riskFilter === '' || $row['risk'] === $this->riskFilter;
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

        $history = $location->demandRequests()
            ->with('location.forecastBands')
            ->whereNotNull('sales_qty')
            ->orderByDesc('year')
            ->orderByDesc('week_number')
            ->limit(12)
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
            'history' => $history,
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
}

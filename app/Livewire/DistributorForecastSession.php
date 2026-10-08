<?php

namespace App\Livewire;

use App\Models\Location;
use App\Models\PerformanceRecord;
use App\Services\DemandRequestService;
use App\Services\ForecastEngineService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;
use Throwable;

class DistributorForecastSession extends Component
{
    private const WEEK_START_DAY = 1;

    protected ForecastEngineService $forecastEngine;

    public ?int $locationId = null;

    public int $weekNumber;

    public int $year;

    public ?int $requestedQuantity = null;

    public ?array $forecast = null;

    public ?string $weatherSummary = null;

    public ?string $forecastError = null;

    public ?string $statusMessage = null;

    public function boot(ForecastEngineService $forecastEngine): void
    {
        $this->forecastEngine = $forecastEngine;
        abort_unless(
            auth()->check()
                && auth()->user()->role === 'distributor'
                && auth()->user()->distributor()->exists(),
            403,
        );
    }

    public function mount(): void
    {
        $targetWeek = now()->addWeek();
        $this->weekNumber = $targetWeek->isoWeek;
        $this->year = $targetWeek->isoWeekYear;

        $distributor = auth()->user()->distributor;
        $firstLocation = $distributor->locations()->orderBy('name')->first();
        $this->locationId = $firstLocation ? (int) $firstLocation->getKey() : null;

        $this->restoreDraft();
        $this->refreshForecast();
    }

    public function updatedLocationId(): void
    {
        $this->locationId = $this->locationId ? (int) $this->locationId : null;
        $this->requestedQuantity = null;
        $this->statusMessage = null;
        $this->restoreDraft();
        $this->refreshForecast();
    }

    public function updatedWeekNumber(): void
    {
        $this->weekNumber = (int) $this->weekNumber;
        $availableWeeks = $this->availableWeekNumbers();
        if (! \in_array($this->weekNumber, $availableWeeks, true)) {
            $this->weekNumber = $availableWeeks[0] ?? now()->addWeek()->isoWeek;
            $this->addError('weekNumber', 'Choose a valid upcoming weekly demand cycle.');
            $this->requestedQuantity = null;
            $this->restoreDraft();
            $this->refreshForecast();

            return;
        }

        $this->resetErrorBag('weekNumber');
        $this->requestedQuantity = null;
        $this->statusMessage = null;
        $this->restoreDraft();
        $this->refreshForecast();
    }

    public function updatedYear(): void
    {
        $this->year = (int) $this->year;
        if (! \in_array($this->year, $this->years(), true)) {
            $this->year = $this->years()[0];
            $this->addError('year', 'Choose one of the available upcoming years.');
        } else {
            $this->resetErrorBag('year');
        }

        $availableWeeks = $this->availableWeekNumbers();
        if (! \in_array($this->weekNumber, $availableWeeks, true)) {
            $this->weekNumber = $availableWeeks[0] ?? 1;
        }

        $this->requestedQuantity = null;
        $this->statusMessage = null;
        $this->restoreDraft();
        $this->refreshForecast();
    }

    #[Computed]
    public function locations(): Collection
    {
        return auth()->user()->distributor
            ->locations()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function years(): array
    {
        $currentYear = now()->isoWeekYear;

        return [$currentYear, $currentYear + 1];
    }

    #[Computed]
    public function weekOptions(): array
    {
        return $this->availableWeekNumbers();
    }

    #[Computed]
    public function requestStatus(): ?array
    {
        if (
            $this->forecast === null
            || $this->requestedQuantity === null
            || $this->requestedQuantity < 1
        ) {
            return null;
        }

        return match (true) {
            $this->requestedQuantity < $this->forecast['low'] => [
                'label' => 'Stockout Risk Note',
                'classes' => 'border-sky-200 bg-sky-50 text-sky-800',
            ],
            $this->requestedQuantity > $this->forecast['high'] => [
                'label' => 'Flagged for Manager Review',
                'classes' => 'border-amber-200 bg-amber-50 text-amber-800',
            ],
            default => [
                'label' => 'Auto-Approve Eligible',
                'classes' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            ],
        };
    }

    #[Computed]
    public function performanceContext(): array
    {
        $distributor = auth()->user()->distributor;
        $previousMonth = now()->subMonth();
        $location = $this->ownedLocation();
        $weeklyAverage = $location
            ? $this->forecastEngine->fourWeekMovingAverage(
                $location,
                $this->weekNumber,
                $this->year,
            )
            : 0.0;

        $accuracyScore = PerformanceRecord::query()
            ->whereBelongsTo($distributor)
            ->where('month', $previousMonth->month)
            ->where('year', $previousMonth->year)
            ->value('accuracy_score');

        $unmetDemandAlert = $distributor->demandRequests()
            ->where('company_short_supply', true)
            ->where('created_at', '>=', now()->subWeeks(4))
            ->exists();

        return [
            'weekly_average' => $weeklyAverage,
            'accuracy_score' => $accuracyScore === null ? null : (float) $accuracyScore,
            'unmet_demand_alert' => $unmetDemandAlert,
        ];
    }

    public function saveDraft(): void
    {
        $this->validate([
            'locationId' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('distributor_id', auth()->user()->distributor->getKey()),
            ],
            'weekNumber' => ['required', 'integer', 'between:1,53'],
            'year' => ['required', 'integer', 'between:1,9999'],
            'requestedQuantity' => ['nullable', 'integer', 'min:1'],
        ]);
        $this->ownedLocationOrFail((int) $this->locationId);

        session()->put($this->draftSessionKey(), $this->requestedQuantity);
        $this->statusMessage = 'Draft saved for this session.';
    }

    public function submitRequest(DemandRequestService $requestService): void
    {
        $validated = $this->validate([
            'locationId' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('distributor_id', auth()->user()->distributor->getKey()),
            ],
            'weekNumber' => ['required', 'integer', 'between:1,53'],
            'year' => ['required', 'integer', 'between:1,9999'],
            'requestedQuantity' => ['required', 'integer', 'min:1'],
        ]);
        $location = $this->ownedLocationOrFail((int) $validated['locationId']);
        $targetWeek = Carbon::now()->setISODate(
            (int) $validated['year'],
            (int) $validated['weekNumber'],
            1,
        )->startOfDay();
        $nextWeek = now()->addWeek()->startOfWeek(self::WEEK_START_DAY);

        if (
            $targetWeek->isoWeekYear() !== (int) $validated['year']
            || $targetWeek->isoWeek() !== (int) $validated['weekNumber']
            || $targetWeek->lt($nextWeek)
        ) {
            throw ValidationException::withMessages([
                'weekNumber' => 'Choose a valid upcoming weekly demand cycle.',
            ]);
        }

        $request = $requestService->createRequest(
            auth()->user()->distributor,
            $location,
            (int) $validated['weekNumber'],
            (int) $validated['year'],
            (int) $validated['requestedQuantity'],
        );

        session()->forget($this->draftSessionKey());
        $this->requestedQuantity = null;
        $this->statusMessage = \sprintf(
            'Weekly request #%d submitted with status: %s.',
            $request->getKey(),
            str_replace('_', ' ', $request->getAttribute('status')),
        );
    }

    public function refreshForecast(): void
    {
        $this->forecast = null;
        $this->weatherSummary = null;
        $this->forecastError = null;

        if (! $this->locationId || ! \in_array($this->weekNumber, $this->availableWeekNumbers(), true)) {
            return;
        }

        try {
            $location = $this->ownedLocationOrFail($this->locationId);
            $forecast = $this->forecastEngine->fetchForecastDetails(
                $location,
                $this->weekNumber,
                $this->year,
            );
            $bands = [
                'low' => max(0, (int) round($forecast['low'])),
                'expected' => max(0, (int) round($forecast['expected'])),
                'high' => max(0, (int) round($forecast['high'])),
            ];

            if ($bands['low'] > $bands['expected'] || $bands['expected'] > $bands['high']) {
                throw new RuntimeException('Forecast bands are not ordered.');
            }

            $location->forecastBands()->updateOrCreate(
                ['week_number' => $this->weekNumber, 'year' => $this->year],
                [
                    'low_band' => $bands['low'],
                    'expected_band' => $bands['expected'],
                    'high_band' => $bands['high'],
                ],
            );

            $this->forecast = $bands;
            $this->weatherSummary = $forecast['weather_summary'];
        } catch (Throwable $exception) {
            Log::error('Unable to load the distributor forecast session.', [
                'user_id' => auth()->id(),
                'location_id' => $this->locationId,
                'week_number' => $this->weekNumber,
                'year' => $this->year,
                'exception' => $exception->getMessage(),
            ]);
            $this->forecastError = 'The forecast is unavailable right now. Please try again shortly.';
        }
    }

    public function render(): View
    {
        return view('livewire.distributor-forecast-session')
            ->layout('layouts.app', ['title' => 'Weekly demand request']);
    }

    private function restoreDraft(): void
    {
        if (! $this->locationId) {
            $this->requestedQuantity = null;

            return;
        }

        $draft = session()->get($this->draftSessionKey());
        $this->requestedQuantity = $draft === null ? null : (int) $draft;
    }

    private function draftSessionKey(): string
    {
        return \sprintf(
            'distributor_forecast_draft.%d.%d.%d.%d',
            auth()->id(),
            $this->locationId ?? 0,
            $this->year,
            $this->weekNumber,
        );
    }

    /**
     * @return list<int>
     */
    private function availableWeekNumbers(): array
    {
        $nextWeek = now()->addWeek()->startOfWeek(self::WEEK_START_DAY);
        $weekNumbers = [];

        for ($weekNumber = 1; $weekNumber <= 53; $weekNumber++) {
            $candidate = Carbon::now()->setISODate($this->year, $weekNumber, 1)->startOfDay();
            if (
                $candidate->isoWeekYear() === $this->year
                && $candidate->isoWeek() === $weekNumber
                && $candidate->greaterThanOrEqualTo($nextWeek)
            ) {
                $weekNumbers[] = $weekNumber;
            }
        }

        return $weekNumbers;
    }

    private function ownedLocation(): ?Location
    {
        if (! $this->locationId) {
            return null;
        }

        return auth()->user()->distributor
            ->locations()
            ->whereKey($this->locationId)
            ->first();
    }

    private function ownedLocationOrFail(int $locationId): Location
    {
        return auth()->user()->distributor
            ->locations()
            ->whereKey($locationId)
            ->firstOrFail();
    }
}

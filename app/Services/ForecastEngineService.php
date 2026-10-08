<?php

namespace App\Services;

use App\Models\DemandRequest;
use App\Models\Location;
use App\Models\MarketContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class ForecastEngineService
{
    private const AI_SERVICE_URL = 'http://localhost:8000';

    private const LOCATION_LATITUDE = 6.74716;

    private const LOCATION_LONGITUDE = 4.87610;

    private const HTTP_CONNECT_TIMEOUT_SECONDS = 2;

    private const HTTP_TIMEOUT_SECONDS = 10;

    /**
     * @return array{low: float, expected: float, high: float}
     */
    public function fetchForecastBands(Location $location, int $weekNumber, int $year): array
    {
        $forecast = $this->fetchForecastDetails($location, $weekNumber, $year);

        return [
            'low' => $forecast['low'],
            'expected' => $forecast['expected'],
            'high' => $forecast['high'],
        ];
    }

    /**
     * @return array{low: float, expected: float, high: float, weather_summary: string|null}
     */
    public function fetchForecastDetails(Location $location, int $weekNumber, int $year): array
    {
        $weekStart = $this->weekStart($weekNumber, $year);
        $pastSalesAverage = $this->fourWeekMovingAverage($location, $weekNumber, $year);
        $inflationRate = $this->currentInflationRate($location, $weekStart);

        try {
            $response = Http::connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::HTTP_TIMEOUT_SECONDS)
                ->acceptJson()
                ->post(self::AI_SERVICE_URL.'/api/v1/predict-band', [
                    'location_id' => $location->getKey(),
                    'latitude' => self::LOCATION_LATITUDE,
                    'longitude' => self::LOCATION_LONGITUDE,
                    'week_number' => $weekNumber,
                    'year' => $year,
                    'inflation_rate' => $inflationRate,
                    'past_sales_avg' => $pastSalesAverage,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Demand AI service is unreachable; using moving-average forecast.', [
                'location_id' => $location->getKey(),
                'week_number' => $weekNumber,
                'year' => $year,
                'exception' => $exception->getMessage(),
            ]);

            return $this->fallbackDetails($pastSalesAverage);
        }

        if ($response->serverError() || $response->status() === 429) {
            Log::warning('Demand AI service is unavailable; using moving-average forecast.', [
                'location_id' => $location->getKey(),
                'week_number' => $weekNumber,
                'year' => $year,
                'status' => $response->status(),
            ]);

            return $this->fallbackDetails($pastSalesAverage);
        }

        $response->throw();

        return $this->parseForecastDetails($response);
    }

    /**
     * @return array{status: string, message: string, sample_count: int}
     */
    public function checkMonthlyModelHealth(int $month, int $year): array
    {
        if ($month < 1 || $month > 12 || $year < 1 || $year > 9999) {
            throw new InvalidArgumentException('A valid month and year are required.');
        }

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        $rows = DemandRequest::query()
            ->leftJoin('forecast_bands', function (JoinClause $join): void {
                $join->on('forecast_bands.location_id', '=', 'demand_requests.location_id')
                    ->on('forecast_bands.year', '=', 'demand_requests.year')
                    ->on('forecast_bands.week_number', '=', 'demand_requests.week_number');
            })
            ->where('demand_requests.status', 'completed')
            ->whereNotNull('demand_requests.sales_qty')
            ->whereBetween('demand_requests.updated_at', [$monthStart, $monthEnd])
            ->orderBy('demand_requests.id')
            ->get([
                'demand_requests.id',
                'demand_requests.sales_qty',
                'forecast_bands.expected_band',
            ]);

        if ($rows->isEmpty()) {
            return [
                'status' => 'skipped',
                'message' => 'No completed sales with forecasts were found for this month.',
                'sample_count' => 0,
            ];
        }

        $requestsWithoutForecasts = $rows->filter(
            fn (object $row): bool => $row->expected_band === null,
        );
        if ($requestsWithoutForecasts->isNotEmpty()) {
            throw new RuntimeException(\sprintf(
                '%d completed demand request(s) for %02d/%d have no stored forecast band.',
                $requestsWithoutForecasts->count(),
                $month,
                $year,
            ));
        }

        $response = Http::connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::HTTP_TIMEOUT_SECONDS)
            ->acceptJson()
            ->post(self::AI_SERVICE_URL.'/api/v1/evaluate-drift', [
                'actual_sales' => $rows->pluck('sales_qty')->map(
                    fn (int|string $sales): float => (float) $sales,
                )->all(),
                'predicted_sales' => $rows->pluck('expected_band')->map(
                    fn (int|string $forecast): float => (float) $forecast,
                )->all(),
            ]);

        $response->throw();
        $result = $response->json();
        if (
            ! \is_array($result)
            || ! \in_array($result['status'] ?? null, ['ok', 'alert'], true)
            || ! \is_string($result['message'] ?? null)
        ) {
            throw new RuntimeException('Demand AI service returned an invalid drift response.');
        }

        if ($result['status'] === 'alert') {
            Log::critical('Demand forecast drift detected; model retraining is required.', [
                'month' => $month,
                'year' => $year,
                'sample_count' => $rows->count(),
                'message' => $result['message'],
            ]);
        }

        return [
            'status' => $result['status'],
            'message' => $result['message'],
            'sample_count' => $rows->count(),
        ];
    }

    private function weekStart(int $weekNumber, int $year): Carbon
    {
        if ($year < 1 || $year > 9999 || $weekNumber < 1 || $weekNumber > 53) {
            throw new InvalidArgumentException('A valid ISO week number and year are required.');
        }

        $weekStart = Carbon::now()->setISODate($year, $weekNumber, 1)->startOfDay();
        if ($weekStart->isoWeekYear() !== $year || $weekStart->isoWeek() !== $weekNumber) {
            throw new InvalidArgumentException(\sprintf(
                'Week %d is not a valid ISO week in %d.',
                $weekNumber,
                $year,
            ));
        }

        return $weekStart;
    }

    public function fourWeekMovingAverage(Location $location, int $weekNumber, int $year): float
    {
        $weekStart = $this->weekStart($weekNumber, $year);
        $firstWeek = $weekStart->copy()->subWeeks(4);
        $lastWeek = $weekStart->copy()->subWeek();

        $weeklySales = DemandRequest::query()
            ->whereBelongsTo($location)
            ->where('status', 'completed')
            ->whereNotNull('sales_qty')
            ->where('company_short_supply', false)
            ->where(function (Builder $query) use ($firstWeek, $lastWeek): void {
                $query->where(function (Builder $startQuery) use ($firstWeek): void {
                    $startQuery->where('year', '>', $firstWeek->isoWeekYear())
                        ->orWhere(function (Builder $sameYearQuery) use ($firstWeek): void {
                            $sameYearQuery->where('year', $firstWeek->isoWeekYear())
                                ->where('week_number', '>=', $firstWeek->isoWeek());
                        });
                })->where(function (Builder $endQuery) use ($lastWeek): void {
                    $endQuery->where('year', '<', $lastWeek->isoWeekYear())
                        ->orWhere(function (Builder $sameYearQuery) use ($lastWeek): void {
                            $sameYearQuery->where('year', $lastWeek->isoWeekYear())
                                ->where('week_number', '<=', $lastWeek->isoWeek());
                        });
                });
            })
            ->selectRaw('year, week_number, SUM(sales_qty) as weekly_sales')
            ->groupBy('year', 'week_number')
            ->orderBy('year')
            ->orderBy('week_number')
            ->get();

        return (float) ($weeklySales->avg('weekly_sales') ?? 0.0);
    }

    private function currentInflationRate(Location $location, Carbon $weekStart): float
    {
        $inflationRate = MarketContext::query()
            ->whereBelongsTo($location)
            ->whereNotNull('inflation_rate')
            ->whereDate('observed_on', '<=', $weekStart->toDateString())
            ->orderByDesc('observed_on')
            ->value('inflation_rate');

        if ($inflationRate === null) {
            throw new RuntimeException(\sprintf(
                'No inflation indicator is available for location %s on or before %s.',
                $location->getKey(),
                $weekStart->toDateString(),
            ));
        }

        return (float) $inflationRate;
    }

    /**
     * @return array{low: float, expected: float, high: float}
     */
    private function fallbackBands(float $average): array
    {
        return [
            'low' => $average * 0.85,
            'expected' => $average,
            'high' => $average * 1.15,
        ];
    }

    /**
     * @return array{low: float, expected: float, high: float, weather_summary: null}
     */
    private function fallbackDetails(float $average): array
    {
        return $this->fallbackBands($average) + ['weather_summary' => null];
    }

    /**
     * @return array{low: float, expected: float, high: float, weather_summary: string|null}
     */
    private function parseForecastDetails(Response $response): array
    {
        $payload = $response->json();
        if (! \is_array($payload)) {
            throw new RuntimeException('Demand AI service returned an invalid forecast response.');
        }

        $bands = [
            'low' => $payload['low_band'] ?? null,
            'expected' => $payload['expected_band'] ?? null,
            'high' => $payload['high_band'] ?? null,
        ];

        foreach ($bands as $name => $value) {
            if (! \is_numeric($value) || ! \is_finite((float) $value) || (float) $value < 0) {
                throw new RuntimeException(\sprintf(
                    'Demand AI service returned an invalid %s forecast band.',
                    $name,
                ));
            }

            $bands[$name] = (float) $value;
        }

        if ($bands['low'] > $bands['expected'] || $bands['expected'] > $bands['high']) {
            throw new RuntimeException('Demand AI service returned unordered forecast bands.');
        }

        $weatherSummary = $payload['weather_summary'] ?? null;
        if ($weatherSummary !== null && ! \is_string($weatherSummary)) {
            throw new RuntimeException('Demand AI service returned an invalid weather summary.');
        }

        return $bands + ['weather_summary' => $weatherSummary];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DemandRequest;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminMonitoringController extends Controller
{
    private const MAPE_THRESHOLD = 15.0;

    private const RMSE_THRESHOLD = 100.0;

    public function show(): View
    {
        $evaluations = DemandRequest::query()
            ->join('forecast_bands', function (JoinClause $join): void {
                $join->on('forecast_bands.location_id', '=', 'demand_requests.location_id')
                    ->on('forecast_bands.year', '=', 'demand_requests.year')
                    ->on('forecast_bands.week_number', '=', 'demand_requests.week_number');
            })
            ->whereNotNull('demand_requests.sales_qty')
            ->orderBy('demand_requests.year')
            ->orderBy('demand_requests.week_number')
            ->limit(500)
            ->get([
                'demand_requests.year',
                'demand_requests.week_number',
                'demand_requests.sales_qty',
                'forecast_bands.expected_band',
            ])
            ->groupBy(fn ($row): string => $row->year.'-'.$row->week_number)
            ->map(fn (Collection $rows, string $period): array => $this->calculateMetrics($rows, $period))
            ->values();

        $latestEvaluation = $evaluations->last();
        $model = [
            'name' => 'Demand forecast',
            'version' => 'Current forecast bands',
            'mape' => $latestEvaluation['mape'] ?? 0,
            'rmse' => $latestEvaluation['rmse'] ?? 0,
            'last_trained_at' => 'Not tracked',
            'training_records' => $evaluations->sum('sample_count'),
            'drift_detected' => ($latestEvaluation['mape'] ?? 0) > self::MAPE_THRESHOLD
                || ($latestEvaluation['rmse'] ?? 0) > self::RMSE_THRESHOLD,
        ];
        $thresholds = [
            'mape' => self::MAPE_THRESHOLD,
            'rmse' => self::RMSE_THRESHOLD,
        ];
        $forecastHistory = $evaluations->all();

        return view('pages.admin.ai-monitoring', compact('model', 'thresholds', 'forecastHistory'));
    }

    public function triggerRetraining(): RedirectResponse
    {
        return back()->with('retraining-status', 'Retraining is unavailable because no model training provider is configured.');
    }

    /** @param Collection<int, object> $rows
     * @return array{period: string, sample_count: int, mape: float, rmse: float, drift: bool}
     */
    private function calculateMetrics(Collection $rows, string $period): array
    {
        $absolutePercentageErrors = [];
        $squaredErrors = [];

        foreach ($rows as $row) {
            $actual = (float) $row->sales_qty;
            $forecast = (float) $row->expected_band;
            $absoluteError = abs($forecast - $actual);
            $absolutePercentageErrors[] = $actual === 0.0
                ? ($forecast === 0.0 ? 0.0 : 100.0)
                : ($absoluteError / $actual) * 100;
            $squaredErrors[] = ($forecast - $actual) ** 2;
        }

        $mape = array_sum($absolutePercentageErrors) / count($rows);
        $rmse = sqrt(array_sum($squaredErrors) / count($rows));
        [$year, $week] = explode('-', $period);

        return [
            'period' => sprintf('W%s %s', str_pad($week, 2, '0', STR_PAD_LEFT), $year),
            'sample_count' => count($rows),
            'mape' => round($mape, 2),
            'rmse' => round($rmse, 2),
            'drift' => $mape > self::MAPE_THRESHOLD || $rmse > self::RMSE_THRESHOLD,
        ];
    }
}

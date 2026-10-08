<?php

namespace App\Services;

use App\Models\Distributor;
use App\Models\PerformanceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerformanceScoringService
{
    public function calculateForPeriod(
        Distributor $distributor,
        int $month,
        int $year,
        float $timelinessScore = 0,
        float $paymentScore = 0,
    ): PerformanceRecord {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return DB::transaction(function () use ($distributor, $month, $year, $periodStart, $periodEnd, $timelinessScore, $paymentScore): PerformanceRecord {
            $requests = $distributor->demandRequests()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->whereNotNull('sales_qty')
                ->get(['requested_qty', 'approved_qty', 'sales_qty', 'company_short_supply']);

            $accuracyScores = $requests->map(function ($request): float {
                $comparisonQuantity = $request->company_short_supply
                    ? (int) ($request->approved_qty ?? $request->requested_qty)
                    : $request->requested_qty;
                $salesQuantity = (int) $request->sales_qty;

                if ($salesQuantity === 0) {
                    return $comparisonQuantity === 0 ? 100.0 : 0.0;
                }

                return $this->normalizeScore(
                    (1 - (abs($comparisonQuantity - $salesQuantity) / $salesQuantity)) * 100,
                );
            });

            $accuracyScore = $accuracyScores->isEmpty() ? 0.0 : (float) $accuracyScores->avg();
            $totalSales = (int) $requests->sum('sales_qty');
            $monthlyTarget = (float) $distributor->monthly_target;
            $targetScore = $monthlyTarget > 0
                ? $this->normalizeScore(($totalSales / $monthlyTarget) * 100)
                : 0.0;
            $timelinessScore = $this->normalizeScore($timelinessScore);
            $paymentScore = $this->normalizeScore($paymentScore);
            $totalScore = round(
                (0.35 * $accuracyScore)
                + (0.30 * $targetScore)
                + (0.20 * $timelinessScore)
                + (0.15 * $paymentScore),
                2,
            );

            $record = $distributor->performanceRecords()->firstOrNew([
                'month' => $month,
                'year' => $year,
            ]);

            $record->fill([
                'accuracy_score' => round($accuracyScore, 2),
                'target_score' => round($targetScore, 2),
                'timeliness_score' => $timelinessScore,
                'payment_score' => $paymentScore,
                'total_score' => $totalScore,
                'verification_code' => $record->verification_code ?: (string) Str::uuid(),
            ])->save();

            return $record->refresh();
        });
    }

    private function normalizeScore(float $score): float
    {
        return round(min(100, max(0, $score)), 2);
    }
}

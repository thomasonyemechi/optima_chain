<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->role !== 'distributor') {
            return redirect()->route(match ($request->user()->role) {
                'manager' => 'manager.review-queue',
                'logistics' => 'manager.dispatch',
                'admin' => 'admin.ai-monitoring',
                'bank' => 'bank.verification',
                default => 'login',
            });
        }

        $distributor = $request->user()->distributor;
        abort_unless($distributor, 403, 'This account has no distributor profile.');

        $requests = $distributor->demandRequests();
        $activeOrders = (clone $requests)->whereNotIn('status', ['completed'])->count();
        $dispatchTotals = (clone $requests)
            ->whereNotNull('dispatched_qty')
            ->selectRaw('COALESCE(SUM(confirmed_qty), 0) as confirmed_total, COALESCE(SUM(dispatched_qty), 0) as dispatched_total')
            ->first();
        $fulfillmentRate = (int) $dispatchTotals->dispatched_total > 0
            ? ((int) $dispatchTotals->confirmed_total / (int) $dispatchTotals->dispatched_total) * 100
            : 0;
        $latestScore = $distributor->performanceRecords()->latest('year')->latest('month')->first();

        $recentOrders = $distributor->demandRequests()
            ->with('location:id,name')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($order): array => [
                'reference' => 'DR-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                'location' => $order->location->name,
                'quantity' => $order->requested_qty,
                'status' => str_replace('_', ' ', ucfirst($order->status)),
                'updated_at' => $order->updated_at->diffForHumans(),
            ]);

        return view('pages.distributor.dashboard', [
            'activeOrders' => $activeOrders,
            'fulfillmentRate' => $fulfillmentRate,
            'creditGrade' => $this->grade((float) ($latestScore?->total_score ?? 0)),
            'creditScore' => (float) ($latestScore?->total_score ?? 0),
            'openDiscrepancyCount' => Complaint::query()
                ->where('status', 'open')
                ->whereHas('demandRequest', fn ($query) => $query->whereBelongsTo($distributor))
                ->count(),
            'recentOrders' => $recentOrders,
        ]);
    }

    private function grade(float $score): string
    {
        return match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'B+',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            default => 'D',
        };
    }
}

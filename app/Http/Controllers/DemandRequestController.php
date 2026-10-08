<?php

namespace App\Http\Controllers;

use App\Models\DemandRequest;
use App\Models\Location;
use App\Services\DemandRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DemandRequestController extends Controller
{
    public function create(Request $request): View
    {
        $distributor = $request->user()->distributor;
        abort_unless($distributor, 403, 'This account has no distributor profile.');

        $locations = $distributor->locations()->orderBy('name')->get();
        $location = $locations->firstWhere('id', $request->integer('location_id')) ?? $locations->first();
        $period = now();
        $forecastBand = $location?->forecastBands()
            ->where('week_number', $period->isoWeek)
            ->where('year', $period->isoWeekYear)
            ->first();

        return view('pages.distributor.weekly-request', [
            'locations' => $locations,
            'weekNumber' => $period->isoWeek,
            'year' => $period->isoWeekYear,
            'decision' => $this->requestDecision(old('requested_qty'), $forecastBand),
            'forecast' => [
                'low' => $forecastBand?->low_band ?? 0,
                'expected' => $forecastBand?->expected_band ?? 0,
                'high' => $forecastBand?->high_band ?? 0,
                'updated_at' => $forecastBand?->updated_at?->diffForHumans(),
            ],
        ]);
    }

    private function requestDecision(mixed $requestedQuantity, mixed $forecastBand): ?string
    {
        if ($requestedQuantity === null || ! $forecastBand) {
            return null;
        }

        return match (true) {
            (int) $requestedQuantity > $forecastBand->high_band => 'flagged',
            (int) $requestedQuantity < $forecastBand->low_band => 'below_forecast',
            default => 'auto_approve',
        };
    }

    public function store(Request $request, DemandRequestService $service): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'requested_qty' => ['required', 'integer', 'min:1'],
        ]);
        $distributor = $request->user()->distributor;
        abort_unless($distributor, 403, 'This account has no distributor profile.');
        $location = Location::query()->findOrFail($validated['location_id']);
        $period = now();

        $service->createRequest($distributor, $location, $period->isoWeek, $period->isoWeekYear, $validated['requested_qty']);

        return redirect()->route('demand.request')->with('status', 'Your weekly stock request was submitted.');
    }

    public function receiptForm(Request $request): View
    {
        $distributor = $request->user()->distributor;
        abort_unless($distributor, 403, 'This account has no distributor profile.');

        $pendingDispatches = $distributor->demandRequests()
            ->with('location:id,name')
            ->whereIn('status', ['auto_approved', 'adjusted'])
            ->whereNotNull('dispatched_qty')
            ->whereNull('confirmed_at')
            ->latest()
            ->get()
            ->map(fn (DemandRequest $demandRequest): array => [
                'id' => $demandRequest->id,
                'reference' => 'DR-'.str_pad((string) $demandRequest->id, 6, '0', STR_PAD_LEFT).' · '.$demandRequest->location->name,
                'dispatched_quantity' => $demandRequest->dispatched_qty,
            ]);

        return view('pages.distributor.confirm-receipt', compact('pendingDispatches'));
    }

    public function confirmReceipt(Request $request, DemandRequestService $service): RedirectResponse
    {
        $validated = $request->validate([
            'demand_request_id' => ['required', 'integer'],
            'confirmation_code' => ['required', 'digits:6'],
            'confirmed_qty' => ['required', 'integer', 'min:0'],
        ]);
        $distributor = $request->user()->distributor;
        abort_unless($distributor, 403, 'This account has no distributor profile.');

        $demandRequest = $distributor->demandRequests()->findOrFail($validated['demand_request_id']);
        $service->confirmReceipt($demandRequest, $validated['confirmation_code'], $validated['confirmed_qty']);

        return redirect()->route('receipt.confirm')->with('status', 'Delivery receipt confirmed. Any quantity difference was opened as a discrepancy ticket.');
    }

    public function salesForm(Request $request): View
    {
        $distributor = $request->user()->distributor;
        abort_unless($distributor, 403, 'This account has no distributor profile.');

        $locations = $distributor->locations()->orderBy('name')->get();
        $inventoryOnHand = (int) $distributor->demandRequests()
            ->whereNotNull('confirmed_qty')
            ->selectRaw('COALESCE(SUM(confirmed_qty - COALESCE(sales_qty, 0)), 0) as balance')
            ->value('balance');

        return view('pages.distributor.sales-entry', compact('locations', 'inventoryOnHand'));
    }

    public function recordSales(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'week_ending' => ['required', 'date', 'before_or_equal:today'],
            'sales_qty' => ['required', 'integer', 'min:0'],
        ]);
        $distributor = $request->user()->distributor;
        abort_unless($distributor, 403, 'This account has no distributor profile.');
        $location = $distributor->locations()->findOrFail($validated['location_id']);
        $weekEnding = Carbon::parse($validated['week_ending']);

        $demandRequest = $distributor->demandRequests()
            ->whereBelongsTo($location)
            ->where('week_number', $weekEnding->isoWeek)
            ->where('year', $weekEnding->isoWeekYear)
            ->whereIn('status', ['delivered', 'completed'])
            ->latest()
            ->firstOrFail();

        $demandRequest->update([
            'sales_qty' => $validated['sales_qty'],
            'status' => 'completed',
        ]);

        return redirect()->route('sales.entry')->with('status', 'Weekly sales were recorded.');
    }
}

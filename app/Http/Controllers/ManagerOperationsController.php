<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\DemandRequest;
use App\Services\DemandRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerOperationsController extends Controller
{
    public function reviewQueue(): View
    {
        $flaggedRequests = DemandRequest::query()
            ->with(['distributor', 'location.forecastBands'])
            ->where('status', 'flagged')
            ->latest()
            ->get()
            ->map(fn (DemandRequest $demandRequest): array => [
                'id' => $demandRequest->id,
                'reference' => $this->reference($demandRequest),
                'distributor_name' => $demandRequest->distributor->company_name,
                'location' => $demandRequest->location->name,
                'requested_quantity' => $demandRequest->requested_qty,
                'forecast_high' => $demandRequest->location->forecastBands
                    ->first(fn ($forecast): bool => $forecast->week_number === $demandRequest->week_number
                        && $forecast->year === $demandRequest->year)?->high_band ?? 0,
                'submitted_at' => $demandRequest->created_at->format('M j, Y'),
            ]);

        return view('pages.manager.review-queue', compact('flaggedRequests'));
    }

    public function review(Request $request, DemandRequestService $service): RedirectResponse
    {
        $validated = $request->validate([
            'demand_request_id' => ['required', 'integer', 'exists:demand_requests,id'],
            'decision' => ['required', 'in:approve,adjust'],
            'approved_qty' => ['required', 'integer', 'min:0'],
            'company_short_supply' => ['sometimes', 'boolean'],
        ]);

        $demandRequest = DemandRequest::query()->findOrFail($validated['demand_request_id']);
        $service->review(
            $demandRequest,
            $validated['decision'],
            (int) $validated['approved_qty'],
            $request->boolean('company_short_supply'),
        );

        return redirect()->route('manager.review-queue')->with('status', 'The request review was saved.');
    }

    public function dispatchQueue(): View
    {
        $approvedOrders = DemandRequest::query()
            ->with(['distributor', 'location'])
            ->whereIn('status', ['auto_approved', 'adjusted'])
            ->whereNull('confirmed_at')
            ->latest()
            ->get()
            ->map(fn (DemandRequest $demandRequest): array => [
                'id' => $demandRequest->id,
                'reference' => $this->reference($demandRequest),
                'distributor_name' => $demandRequest->distributor->company_name,
                'location' => $demandRequest->location->name,
                'approved_quantity' => $demandRequest->approved_qty,
                'dispatched_quantity' => $demandRequest->dispatched_qty,
                'approved_at' => $demandRequest->updated_at->format('M j, Y'),
                'confirmation_code' => $demandRequest->confirmation_code,
            ]);

        return view('pages.manager.dispatch-management', compact('approvedOrders'));
    }

    public function dispatch(Request $request, DemandRequest $demandRequest, DemandRequestService $service): RedirectResponse
    {
        $validated = $request->validate([
            'dispatched_qty' => ['required', 'integer', 'min:0', 'max:'.($demandRequest->approved_qty ?? 0)],
        ]);

        $service->dispatch($demandRequest, $validated['dispatched_qty']);

        return redirect()->route('manager.dispatch')->with('status', 'Dispatch recorded and confirmation code generated.');
    }

    public function complaints(): View
    {
        $complaints = Complaint::query()
            ->with(['demandRequest.distributor', 'demandRequest.location'])
            ->latest()
            ->get()
            ->map(fn (Complaint $complaint): array => [
                'id' => $complaint->id,
                'reference' => 'CT-'.str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT),
                'dispatch_reference' => $this->reference($complaint->demandRequest),
                'distributor_name' => $complaint->demandRequest->distributor->company_name,
                'dispatched_quantity' => $complaint->demandRequest->dispatched_qty,
                'received_quantity' => $complaint->demandRequest->confirmed_qty,
                'created_at' => $complaint->created_at->format('M j, Y'),
                'status' => $complaint->status,
            ]);

        return view('pages.manager.complaints', compact('complaints'));
    }

    public function resolveComplaint(Complaint $complaint): RedirectResponse
    {
        $complaint->update(['status' => 'resolved']);

        return redirect()->route('manager.complaints')->with('status', 'Discrepancy ticket marked resolved.');
    }

    private function reference(DemandRequest $demandRequest): string
    {
        return 'DR-'.str_pad((string) $demandRequest->id, 6, '0', STR_PAD_LEFT);
    }
}

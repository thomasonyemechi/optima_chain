<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\DemandRequest;
use App\Models\Distributor;
use App\Models\ForecastBand;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DemandRequestService
{
    public function createRequest(
        Distributor $distributor,
        Location $location,
        int $weekNumber,
        int $year,
        int $requestedQuantity,
    ): DemandRequest {
        if ($requestedQuantity < 1) {
            throw ValidationException::withMessages([
                'requested_qty' => 'The requested quantity must be at least one unit.',
            ]);
        }

        if ($location->distributor_id !== $distributor->id) {
            throw ValidationException::withMessages([
                'location_id' => 'The selected location does not belong to this distributor.',
            ]);
        }

        $forecast = ForecastBand::query()
            ->whereBelongsTo($location)
            ->where('week_number', $weekNumber)
            ->where('year', $year)
            ->first();

        if (! $forecast) {
            throw ValidationException::withMessages([
                'location_id' => 'No forecast is available for this location and week.',
            ]);
        }

        $withinForecast = $requestedQuantity >= $forecast->low_band
            && $requestedQuantity <= $forecast->high_band;
        $aboveForecast = $requestedQuantity > $forecast->high_band;

        return $distributor->demandRequests()->create([
            'location_id' => $location->id,
            'week_number' => $weekNumber,
            'year' => $year,
            'requested_qty' => $requestedQuantity,
            'approved_qty' => $withinForecast ? $requestedQuantity : null,
            'status' => $withinForecast ? 'auto_approved' : ($aboveForecast ? 'flagged' : 'pending'),
            'flag_reason' => $aboveForecast
                ? 'Requested quantity exceeds the forecast high band.'
                : ($withinForecast ? null : 'Requested quantity is below the forecast low band.'),
        ]);
    }

    public function dispatch(DemandRequest $demandRequest, int $dispatchedQuantity): DemandRequest
    {
        if ($dispatchedQuantity < 0) {
            throw ValidationException::withMessages([
                'dispatched_qty' => 'The dispatched quantity cannot be negative.',
            ]);
        }

        return DB::transaction(function () use ($demandRequest, $dispatchedQuantity): DemandRequest {
            $lockedRequest = DemandRequest::query()->lockForUpdate()->findOrFail($demandRequest->id);

            if (! in_array($lockedRequest->status, ['auto_approved', 'adjusted'], true)) {
                throw ValidationException::withMessages([
                    'demand_request' => 'Only approved or adjusted requests can be dispatched.',
                ]);
            }

            if ($lockedRequest->dispatched_qty !== null || $lockedRequest->confirmation_code !== null) {
                throw ValidationException::withMessages([
                    'demand_request' => 'This request has already been dispatched.',
                ]);
            }

            if ($dispatchedQuantity > (int) $lockedRequest->approved_qty) {
                throw ValidationException::withMessages([
                    'dispatched_qty' => 'The dispatched quantity cannot exceed the approved quantity.',
                ]);
            }

            $lockedRequest->forceFill([
                'dispatched_qty' => $dispatchedQuantity,
                'confirmation_code' => (string) random_int(100000, 999999),
            ])->save();

            return $lockedRequest->refresh();
        });
    }

    public function confirmReceipt(DemandRequest $demandRequest, string $confirmationCode, int $confirmedQuantity): DemandRequest
    {
        if ($confirmedQuantity < 0) {
            throw ValidationException::withMessages([
                'confirmed_qty' => 'The received quantity cannot be negative.',
            ]);
        }

        return DB::transaction(function () use ($demandRequest, $confirmationCode, $confirmedQuantity): DemandRequest {
            $lockedRequest = DemandRequest::query()->lockForUpdate()->findOrFail($demandRequest->id);

            if (! $lockedRequest->confirmation_code || ! hash_equals($lockedRequest->confirmation_code, $confirmationCode)) {
                throw ValidationException::withMessages([
                    'confirmation_code' => 'The confirmation code does not match this dispatch.',
                ]);
            }

            if ($lockedRequest->dispatched_qty === null || $lockedRequest->confirmed_at !== null) {
                throw ValidationException::withMessages([
                    'demand_request_id' => 'This dispatch is not awaiting receipt confirmation.',
                ]);
            }

            $lockedRequest->forceFill([
                'confirmed_qty' => $confirmedQuantity,
                'confirmed_at' => now(),
                'status' => 'delivered',
            ])->save();

            $this->createDiscrepancyComplaint($lockedRequest);

            return $lockedRequest->refresh();
        });
    }

    public function createDiscrepancyComplaint(DemandRequest $demandRequest): ?Complaint
    {
        if ($demandRequest->confirmed_qty === null || $demandRequest->dispatched_qty === null) {
            return null;
        }

        if ($demandRequest->confirmed_qty === $demandRequest->dispatched_qty) {
            return null;
        }

        return $demandRequest->complaint()->firstOrCreate([], [
            'type' => 'quantity_discrepancy',
            'description' => sprintf(
                'Dispatched quantity was %d units and confirmed receipt was %d units.',
                $demandRequest->dispatched_qty,
                $demandRequest->confirmed_qty,
            ),
            'status' => 'open',
        ]);
    }
}

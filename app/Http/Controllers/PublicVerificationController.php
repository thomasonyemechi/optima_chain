<?php

namespace App\Http\Controllers;

use App\Models\PerformanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicVerificationController extends Controller
{
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'uuid'],
        ]);

        return redirect()->route('verify.record', ['code' => $validated['code']]);
    }

    public function show(string $code): View
    {
        $performanceRecord = PerformanceRecord::query()
            ->with(['distributor.user', 'distributor.locations'])
            ->where('verification_code', $code)
            ->first();

        $isVerified = $performanceRecord !== null;
        $score = (float) ($performanceRecord?->total_score ?? 0);
        $record = $performanceRecord ? [
            'distributor_name' => $performanceRecord->distributor->company_name,
            'legal_entity' => $performanceRecord->distributor->company_name,
            'registration_id' => $performanceRecord->distributor->account_number,
            'region' => $performanceRecord->distributor->locations->pluck('name')->join(', '),
            'period' => sprintf('%02d/%d', $performanceRecord->month, $performanceRecord->year),
            'verified_at' => $performanceRecord->updated_at->format('M j, Y'),
            'verification_code' => $performanceRecord->verification_code,
            'grade' => match (true) {
                $score >= 95 => 'A+',
                $score >= 90 => 'A',
                $score >= 85 => 'B+',
                $score >= 80 => 'B',
                $score >= 70 => 'C',
                default => 'D',
            },
            'score' => $score,
            'accuracy' => (float) $performanceRecord->accuracy_score,
            'fulfillment_rate' => (float) $performanceRecord->target_score,
            'on_time_rate' => (float) $performanceRecord->timeliness_score,
            'cryptographic_hash' => hash('sha256', implode('|', [
                $performanceRecord->getKey(),
                $performanceRecord->verification_code,
                $performanceRecord->distributor_id,
                $performanceRecord->month,
                $performanceRecord->year,
                $performanceRecord->accuracy_score,
                $performanceRecord->target_score,
                $performanceRecord->timeliness_score,
                $performanceRecord->payment_score,
                $performanceRecord->total_score,
                $performanceRecord->updated_at->toIso8601String(),
            ])),
        ] : [
            'verification_code' => $code,
        ];

        return view('pages.public.verify-record', compact('record', 'isVerified'));
    }
}

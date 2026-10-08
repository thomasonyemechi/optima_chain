<?php

namespace App\Http\Controllers;

use App\Models\DemandRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScorecardController extends Controller
{
    public function show(Request $request): View
    {
        $distributor = $request->user()->distributor;
        abort_unless($distributor, 403, 'This account has no distributor profile.');

        $record = $distributor->performanceRecords()->latest('year')->latest('month')->first();
        $scoreValue = (float) ($record?->total_score ?? 0);
        $grade = match (true) {
            $scoreValue >= 95 => 'A+',
            $scoreValue >= 90 => 'A',
            $scoreValue >= 85 => 'B+',
            $scoreValue >= 80 => 'B',
            $scoreValue >= 70 => 'C',
            default => 'D',
        };

        $score = [
            'grade' => $grade,
            'total' => $scoreValue,
            'accuracy' => (float) ($record?->accuracy_score ?? 0),
            'targets' => (float) ($record?->target_score ?? 0),
            'timeliness' => (float) ($record?->timeliness_score ?? 0),
            'payment' => (float) ($record?->payment_score ?? 0),
            'period' => $record ? sprintf('%02d/%d', $record->month, $record->year) : 'No scoring period available',
            'updated_at' => $record?->updated_at?->format('M j, Y'),
            'verification_status' => $record ? 'Verified record' : 'Not yet scored',
            'verification_code' => $record?->verification_code,
        ];

        $score['completed_orders'] = DemandRequest::query()
            ->whereBelongsTo($distributor)
            ->where('status', 'completed')
            ->count();

        return view('pages.analytics.distributor-scorecard', compact('score'));
    }
}

@extends('layouts.app')

@section('title', 'Distributor scorecard')

@section('content')
    @php
        $score = $score ?? ['grade' => '—', 'total' => 0, 'accuracy' => 0, 'targets' => 0, 'timeliness' => 0, 'payment' => 0];
        $components = [
            ['label' => 'Order accuracy', 'key' => 'accuracy', 'weight' => 35, 'color' => 'bg-indigo-500'],
            ['label' => 'Target achievement', 'key' => 'targets', 'weight' => 30, 'color' => 'bg-emerald-500'],
            ['label' => 'Delivery timeliness', 'key' => 'timeliness', 'weight' => 20, 'color' => 'bg-amber-500'],
            ['label' => 'Payment reliability', 'key' => 'payment', 'weight' => 15, 'color' => 'bg-sky-500'],
        ];
    @endphp
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-medium text-indigo-600">PERFORMANCE · STEP 7</p><h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-[28px]">Distributor scorecard</h1><p class="mt-1 text-sm text-slate-500">A transparent view of the behaviors that shape your credit standing.</p></div><button type="button" onclick="window.print()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">↓ Print / save statement</button></div>
    <section class="mt-6 grid gap-5 lg:grid-cols-[minmax(250px,0.72fr)_minmax(0,1.5fr)]">
        <article class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm"><p class="text-sm font-medium text-slate-500">Overall credit grade</p><div class="mt-5 flex items-center gap-4"><span class="grid size-20 place-items-center rounded-2xl bg-emerald-50 text-4xl font-semibold tracking-tight text-emerald-700">{{ $score['grade'] }}</span><span><span class="block text-3xl font-semibold tracking-tight tabular-nums">{{ number_format($score['total']) }}<span class="text-base font-medium text-slate-400">/100</span></span><span class="mt-1 block text-xs text-slate-500">{{ $score['period'] ?? 'Current scoring period' }}</span></span></div><div class="mt-6 rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold text-slate-700">What this means</p><p class="mt-1.5 text-xs leading-5 text-slate-500">Your grade reflects verified order accuracy, target achievement, delivery timing, and payment reliability.</p></div>@if (isset($score['updated_at']))<p class="mt-5 text-xs text-slate-400">Last updated {{ $score['updated_at'] }}</p>@endif</article>
        <article class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm"><div class="flex items-start justify-between gap-4"><div><h2 class="text-base font-semibold tracking-tight">Score components</h2><p class="mt-1 text-sm text-slate-500">Weighted contribution to your overall grade.</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">4 factors</span></div><div class="mt-6 space-y-5">
            @foreach ($components as $component)
                @php($componentScore = min(100, max(0, $score[$component['key']] ?? 0)))
                <div><div class="flex items-center justify-between gap-4"><span class="text-sm font-medium text-slate-700">{{ $component['label'] }} <span class="ml-1 text-xs font-normal text-slate-400">{{ $component['weight'] }}% weight</span></span><span class="text-sm font-semibold tabular-nums text-slate-800">{{ number_format($componentScore) }}<span class="font-normal text-slate-400">/100</span></span></div><div class="mt-2.5 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $component['color'] }} transition-all" style="width: {{ $componentScore }}%"></div></div></div>
            @endforeach
        </div><div class="mt-7 border-t border-slate-100 pt-5"><p class="text-xs leading-5 text-slate-500">Scores are calculated from completed, verified supply-chain activity. Disputed records are reviewed before affecting your standing.</p></div></article>
    </section>
    <section class="mt-5 rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm"><h2 class="text-base font-semibold tracking-tight">Verification record</h2><div class="mt-4 grid gap-4 sm:grid-cols-3"><div><p class="text-xs text-slate-500">Record status</p><p class="mt-1 text-sm font-medium text-emerald-700">{{ $score['verification_status'] ?? 'Current' }}</p></div><div><p class="text-xs text-slate-500">Scoring period</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $score['period'] ?? 'Current period' }}</p></div><div><p class="text-xs text-slate-500">Public verification</p>@if (!empty($score['verification_code']))<a href="{{ route('verify.record', $score['verification_code']) }}" class="mt-1 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-800">View official record <span class="ml-1">→</span></a>@else<p class="mt-1 text-sm text-slate-500">No published record</p>@endif</div></div></section>
@endsection
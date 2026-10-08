@extends('layouts.app')

@section('title', 'Distributor overview')

@section('content')
    @php
        $activeOrders = $activeOrders ?? 0;
        $fulfillmentRate = $fulfillmentRate ?? 0;
        $creditGrade = $creditGrade ?? '—';
        $creditScore = $creditScore ?? 0;
        $recentOrders = $recentOrders ?? collect();
    @endphp
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-indigo-600">DISTRIBUTOR WORKSPACE</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[28px]">Good {{ now()->format('H') < 12 ? 'morning' : 'afternoon' }}, {{ auth()->user()?->name ?? 'there' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Your supply activity and performance at a glance.</p>
        </div>
        <a href="{{ url('distributor/weekly-request') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">＋ New weekly request</a>
    </div>

    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Performance summary">
        <article class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm transition hover:shadow-md">
            <p class="text-sm font-medium text-slate-500">Active orders</p>
            <div class="mt-4 flex items-end justify-between"><p class="text-3xl font-semibold tracking-tight">{{ number_format($activeOrders) }}</p><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">In progress</span></div>
            <p class="mt-2 text-xs text-slate-400">Orders currently moving through fulfillment</p>
        </article>
        <article class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm transition hover:shadow-md">
            <p class="text-sm font-medium text-slate-500">Fulfillment rate</p>
            <div class="mt-4 flex items-end justify-between"><p class="text-3xl font-semibold tracking-tight">{{ number_format($fulfillmentRate, 1) }}<span class="text-xl">%</span></p><span class="text-xs font-medium text-emerald-700">{{ $fulfillmentRate >= 95 ? 'On target' : 'This period' }}</span></div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, max(0, $fulfillmentRate)) }}%"></div></div>
        </article>
        <article class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm transition hover:shadow-md">
            <p class="text-sm font-medium text-slate-500">Credit standing</p>
            <div class="mt-3 flex items-center gap-3"><span class="grid size-12 place-items-center rounded-xl bg-emerald-50 text-xl font-semibold text-emerald-700">{{ $creditGrade }}</span><span><span class="block text-sm font-semibold">{{ number_format($creditScore) }} <span class="font-normal text-slate-400">/ 100</span></span><span class="mt-0.5 block text-xs text-slate-500">Updated this week</span></span></div>
        </article>
        <article class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm transition hover:shadow-md">
            <p class="text-sm font-medium text-slate-500">Open discrepancies</p>
            <div class="mt-4 flex items-end justify-between"><p class="text-3xl font-semibold tracking-tight">{{ number_format($openDiscrepancyCount ?? 0) }}</p><span class="rounded-full {{ ($openDiscrepancyCount ?? 0) ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-2.5 py-1 text-xs font-medium">{{ ($openDiscrepancyCount ?? 0) ? 'Needs review' : 'All clear' }}</span></div>
            <a href="{{ url('manager/complaints') }}" class="mt-2 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-800">View ticket status <span aria-hidden="true">→</span></a>
        </article>
    </section>

    <section class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.8fr)]">
        <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3"><div><h2 class="text-base font-semibold tracking-tight">Recent orders</h2><p class="mt-1 text-sm text-slate-500">Your latest requests and fulfillment updates.</p></div><a href="{{ url('distributor/weekly-request') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">All activity <span aria-hidden="true">→</span></a></div>
            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="border-b border-slate-100 text-xs font-medium text-slate-400"><tr><th class="pb-3 pr-4">ORDER</th><th class="pb-3 pr-4">LOCATION</th><th class="pb-3 pr-4">QUANTITY</th><th class="pb-3 pr-4">STATUS</th><th class="pb-3">UPDATED</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentOrders as $order)
                            <tr><td class="py-3.5 pr-4 font-medium text-slate-800">{{ $order['reference'] ?? $order->reference }}</td><td class="py-3.5 pr-4 text-slate-600">{{ $order['location'] ?? $order->location }}</td><td class="py-3.5 pr-4 tabular-nums">{{ number_format($order['quantity'] ?? $order->quantity) }}</td><td class="py-3.5 pr-4"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ $order['status'] ?? $order->status }}</span></td><td class="py-3.5 text-slate-500">{{ $order['updated_at'] ?? $order->updated_at }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="py-12 text-center"><span class="mx-auto grid size-10 place-items-center rounded-full bg-slate-100 text-slate-500">↗</span><p class="mt-3 text-sm font-medium text-slate-700">No recent orders</p><p class="mt-1 text-xs text-slate-500">Your submitted requests will appear here.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold tracking-tight">Quick actions</h2>
            <p class="mt-1 text-sm text-slate-500">Keep your weekly operations moving.</p>
            <div class="mt-5 divide-y divide-slate-100">
                <a href="{{ url('distributor/weekly-request') }}" class="flex items-center gap-3 py-4 first:pt-0"><span class="grid size-10 place-items-center rounded-xl bg-indigo-50 text-lg text-indigo-700">＋</span><span class="flex-1"><span class="block text-sm font-semibold">Request stock</span><span class="mt-0.5 block text-xs text-slate-500">Submit a forecast-based request</span></span><span class="text-slate-400">→</span></a>
                <a href="{{ url('distributor/confirm-receipt') }}" class="flex items-center gap-3 py-4"><span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-lg text-emerald-700">↙</span><span class="flex-1"><span class="block text-sm font-semibold">Confirm delivery</span><span class="mt-0.5 block text-xs text-slate-500">Record a shipment received</span></span><span class="text-slate-400">→</span></a>
                <a href="{{ url('distributor/sales-entry') }}" class="flex items-center gap-3 py-4 last:pb-0"><span class="grid size-10 place-items-center rounded-xl bg-amber-50 text-lg text-amber-700">↗</span><span class="flex-1"><span class="block text-sm font-semibold">Enter weekly sales</span><span class="mt-0.5 block text-xs text-slate-500">Keep your scorecard current</span></span><span class="text-slate-400">→</span></a>
            </div>
        </div>
    </section>
@endsection
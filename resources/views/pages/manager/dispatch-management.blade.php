@extends('layouts.app')

@section('title', 'Dispatch management')

@section('content')
    @php($approvedOrders = $approvedOrders ?? collect())
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-medium text-indigo-600">LOGISTICS · STEP 4</p><h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-[28px]">Dispatch management</h1><p class="mt-1 text-sm text-slate-500">Prepare approved orders and register the quantity sent.</p></div><span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">{{ $approvedOrders->count() }} ready to dispatch</span></div>
    <section class="mt-6 rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5"><div><h2 class="text-base font-semibold tracking-tight">Approved orders</h2><p class="mt-1 text-sm text-slate-500">Enter dispatched quantity (D) and create a receipt code for the distributor.</p></div><span class="hidden rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-500 sm:inline-block">Sorted by approval date</span></div>
        <div class="mt-5 space-y-4">
            @forelse ($approvedOrders as $order)
                @php($record = is_array($order) ? $order : $order->toArray())
                <article class="grid gap-5 rounded-xl border border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(300px,0.85fr)] sm:items-center sm:p-5">
                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-slate-900">{{ $record['distributor_name'] ?? 'Distributor' }}</h3><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700">Approved</span></div><p class="mt-1 text-sm text-slate-500">{{ $record['reference'] ?? '—' }} · {{ $record['location'] ?? '—' }}</p><div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500"><span>Approved quantity (A): <strong class="font-semibold text-slate-700">{{ number_format($record['approved_quantity'] ?? 0) }}</strong></span><span>Approved {{ $record['approved_at'] ?? '—' }}</span></div></div>
                    @if (!empty($record['confirmation_code']))
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Dispatched quantity (D): <strong class="font-semibold text-slate-800">{{ number_format($record['dispatched_quantity']) }}</strong></p><div class="mt-2 flex items-center justify-between"><span class="text-xs text-slate-500">Receipt confirmation code</span><span class="font-mono text-sm font-semibold tracking-[0.2em] text-slate-900">{{ $record['confirmation_code'] }}</span></div></div>
                    @else
                        <form method="POST" action="{{ route('manager.dispatch.store', $record['id']) }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">@csrf<div><label for="dispatch-quantity-{{ $record['id'] }}" class="mb-1.5 block text-xs font-medium text-slate-600">Dispatched quantity (D)</label><input id="dispatch-quantity-{{ $record['id'] }}" name="dispatched_qty" type="number" min="0" max="{{ $record['approved_quantity'] ?? 0 }}" value="{{ old('dispatched_qty', $record['approved_quantity'] ?? 0) }}" required class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm tabular-nums outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('dispatched_qty')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><button type="submit" class="h-10 rounded-lg bg-indigo-600 px-3.5 text-xs font-semibold text-white hover:bg-indigo-700">Generate code</button></form>
                    @endif
                </article>
            @empty
                <div class="py-12 text-center"><span class="mx-auto grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500">⇢</span><p class="mt-3 text-sm font-semibold text-slate-800">No orders awaiting dispatch</p><p class="mt-1 text-xs text-slate-500">Approved requests will be listed here.</p></div>
            @endforelse
        </div>
    </section>
@endsection
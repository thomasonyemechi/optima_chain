@extends('layouts.app')

@section('title', 'Confirm delivery')

@section('content')
    @php($pendingDispatches = $pendingDispatches ?? collect())
    <div class="mx-auto max-w-3xl">
        <a href="{{ url('distributor/dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">← Overview</a>
        <div class="mt-4 flex items-start justify-between gap-4"><div><p class="text-sm font-medium text-emerald-700">FULFILLMENT · STEP 5</p><h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-[28px]">Confirm receipt</h1><p class="mt-1 text-sm text-slate-500">Match the delivery to its dispatch and record what arrived.</p></div><span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">Step 5 <span class="text-slate-400">/ 8</span></span></div>

        <form method="POST" action="{{ route('receipt.confirm.store') }}" class="mt-6 space-y-5">
            @csrf
            <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-base font-semibold tracking-tight">Find your dispatch</h2><p class="mt-1 text-sm text-slate-500">Use the six-digit code provided by your supplier.</p>
                <div class="mt-5 grid gap-5 sm:grid-cols-2"><div><label for="confirmation_code" class="mb-1.5 block text-sm font-medium text-slate-700">Confirmation code <span class="text-red-600">*</span></label><input id="confirmation_code" name="confirmation_code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required value="{{ old('confirmation_code') }}" placeholder="000000" class="h-11 w-full rounded-lg border {{ $errors->has('confirmation_code') ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200' }} px-3 font-mono text-lg tracking-[0.2em] outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('confirmation_code')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="demand_request_id" class="mb-1.5 block text-sm font-medium text-slate-700">Dispatch <span class="text-red-600">*</span></label><select id="demand_request_id" name="demand_request_id" required class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="">Select a matching dispatch</option>@foreach ($pendingDispatches as $dispatch)<option value="{{ $dispatch['id'] }}" @selected(old('demand_request_id') == $dispatch['id'])>{{ $dispatch['reference'] }} · {{ number_format($dispatch['dispatched_quantity']) }} units</option>@endforeach</select>@error('demand_request_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                </div>
            </section>
            <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-base font-semibold tracking-tight">Record received quantity</h2><p class="mt-1 text-sm text-slate-500">Enter the count verified at delivery (C).</p>
                <div class="mt-5 max-w-sm"><label for="confirmed_qty" class="mb-1.5 block text-sm font-medium text-slate-700">Quantity received (C) <span class="text-red-600">*</span></label><div class="relative"><input id="confirmed_qty" name="confirmed_qty" type="number" min="0" step="1" required value="{{ old('confirmed_qty') }}" class="h-11 w-full rounded-lg border {{ $errors->has('confirmed_qty') ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200' }} px-3 pr-14 text-sm tabular-nums outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400">units</span></div>@error('confirmed_qty')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @if (isset($dispatchedQuantity, $receivedQuantity) && (int) $dispatchedQuantity !== (int) $receivedQuantity)
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4" role="alert"><div class="flex gap-3"><span class="text-amber-700" aria-hidden="true">!</span><div><p class="text-sm font-semibold text-amber-900">Quantity discrepancy detected</p><p class="mt-1 text-sm text-amber-800">Dispatched {{ number_format($dispatchedQuantity) }} units; you recorded {{ number_format($receivedQuantity) }}. A discrepancy ticket will be created when you confirm.</p></div></div></div>
                @endif
                <details class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3"><summary class="cursor-pointer text-sm font-medium text-slate-700">What happens when quantities differ?</summary><p class="mt-2 text-sm leading-6 text-slate-600">A discrepancy ticket is recorded with the dispatch reference and received count, so both parties can reconcile the delivery.</p></details>
            </section>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between"><a href="{{ url('distributor/dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a><button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Confirm receipt</button></div>
        </form>
    </div>
@endsection
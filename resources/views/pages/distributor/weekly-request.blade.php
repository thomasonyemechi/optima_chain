@extends('layouts.app')

@section('title', 'Weekly stock request')

@section('content')
    @php
        $forecast = $forecast ?? ['low' => 0, 'expected' => 0, 'high' => 0];
        $decision = $decision ?? null;
        $locations = $locations ?? collect();
    @endphp
    <div class="mx-auto max-w-4xl">
        <a href="{{ url('distributor/dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">← Overview</a>
        <div class="mt-4 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-medium text-indigo-600">WEEKLY PLANNING</p><h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-[28px]">Request stock</h1><p class="mt-1 text-sm text-slate-500">Plan your next replenishment against the demand forecast.</p></div><span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">Step 1–3 <span class="text-slate-400">/ 8</span></span></div>
        <ol class="mt-6 grid grid-cols-3 overflow-hidden rounded-xl border border-slate-200 bg-white text-center text-xs font-medium sm:text-sm" aria-label="Request steps"><li class="border-r border-slate-200 bg-indigo-50 px-2 py-3 text-indigo-700"><span class="mr-1.5">01</span> Location</li><li class="border-r border-slate-200 px-2 py-3 text-slate-500"><span class="mr-1.5">02</span> Forecast</li><li class="px-2 py-3 text-slate-500"><span class="mr-1.5">03</span> Request</li></ol>

        <form method="POST" action="{{ route('demand.request.store') }}" data-weekly-request data-forecast-low="{{ $forecast['low'] }}" data-forecast-expected="{{ $forecast['expected'] }}" data-forecast-high="{{ $forecast['high'] }}" data-available-stock="{{ $forecast['available_stock'] ?? '' }}" class="mt-6 space-y-5">
            @csrf
            <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-indigo-50 text-sm font-semibold text-indigo-700">01</span><div><h2 class="text-base font-semibold tracking-tight">Choose a fulfillment location</h2><p class="mt-1 text-sm text-slate-500">Select the depot that serves this request.</p></div></div>
                <div class="mt-5 max-w-xl"><label for="location_id" class="mb-1.5 block text-sm font-medium text-slate-700">Location <span class="text-red-600">*</span></label><select id="location_id" name="location_id" onchange="window.location='{{ route('demand.request') }}?location_id='+encodeURIComponent(this.value)" required class="h-11 w-full rounded-lg border {{ $errors->has('location_id') ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200' }} bg-white px-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="">Select a location</option>@foreach ($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id', request('location_id', $locations->first()?->id)) == $location->id)>{{ $location->name }} · {{ $location->code }}</option>@endforeach</select>@error('location_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </section>

            <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-indigo-50 text-sm font-semibold text-indigo-700">02</span><div><h2 class="text-base font-semibold tracking-tight">Review demand forecast</h2><p class="mt-1 text-sm text-slate-500">Expected demand range for the selected location.</p></div></div>
                <div class="mt-5 rounded-xl bg-slate-50 p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-3"><span class="text-sm font-medium text-slate-700">Forecast band</span><span class="text-xs text-slate-500">Units for next week</span></div>
                    <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-slate-200"><span class="w-1/3 bg-emerald-400"></span><span class="w-1/3 bg-indigo-500"></span><span class="w-1/3 bg-amber-400"></span></div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-xs"><span class="text-left"><span class="block font-medium text-emerald-700">Low</span><span class="mt-1 block text-slate-500">{{ number_format($forecast['low']) }} units</span></span><span class="text-center"><span class="block font-medium text-indigo-700">Expected</span><span class="mt-1 block text-slate-500">{{ number_format($forecast['expected']) }} units</span></span><span class="text-right"><span class="block font-medium text-amber-700">High</span><span class="mt-1 block text-slate-500">{{ number_format($forecast['high']) }} units</span></span></div>
                    @if (isset($forecast['updated_at']))<p class="mt-4 border-t border-slate-200 pt-3 text-xs text-slate-400">Forecast updated {{ $forecast['updated_at'] }}</p>@endif
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-indigo-50 text-sm font-semibold text-indigo-700">03</span><div><h2 class="text-base font-semibold tracking-tight">Set requested quantity</h2><p class="mt-1 text-sm text-slate-500">Requests above the high forecast are sent for review.</p></div></div>
                <div class="mt-5 grid gap-5 sm:grid-cols-[minmax(0,1fr)_220px] sm:items-end"><div><label for="requested_qty" class="mb-1.5 block text-sm font-medium text-slate-700">Requested quantity (R) <span class="text-red-600">*</span></label><input id="requested_qty" name="requested_qty" type="number" min="1" step="1" value="{{ old('requested_qty') }}" required aria-describedby="quantity-help" class="h-11 w-full rounded-lg border {{ $errors->has('requested_qty') ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200' }} px-3 text-sm tabular-nums outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><p id="quantity-help" class="mt-1.5 text-xs text-slate-500">Enter whole units. Your request is checked against the forecast range.</p>@error('requested_qty')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="flex min-h-11 flex-wrap items-center gap-2" aria-live="polite">
                        <span data-request-status-badge class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $decision === 'auto_approve' ? 'bg-emerald-50 text-emerald-700' : (in_array($decision, ['flagged', 'below_forecast'], true) ? 'bg-amber-50 text-amber-700' : ($decision === 'stockout_risk' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600')) }}">{{ $decision === 'auto_approve' ? 'Auto-approve eligible' : ($decision === 'flagged' ? 'Flagged for review' : ($decision === 'below_forecast' ? 'Below forecast band' : ($decision === 'stockout_risk' ? 'Stockout risk' : 'Awaiting estimate'))) }}</span>
                    </div>
                </div>
            </section>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between"><a href="{{ url('distributor/dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a><button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Submit request <span class="ml-2" aria-hidden="true">→</span></button></div>
        </form>
    </div>
@endsection
<div class="space-y-6">
    @php
        $context = $this->performanceContext;
        $status = $this->requestStatus;
        $scaleMaximum = $forecast ? max(1, $forecast['high'] * 1.25) : 1;
        $bandStart = $forecast ? ($forecast['low'] / $scaleMaximum) * 100 : 0;
        $bandWidth = $forecast ? (($forecast['high'] - $forecast['low']) / $scaleMaximum) * 100 : 0;
        $expectedPosition = $forecast ? ($forecast['expected'] / $scaleMaximum) * 100 : 0;
    @endphp

    <section class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-medium tracking-wide text-indigo-600">DISTRIBUTOR WORKSPACE</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[28px]">Plan your weekly demand</h1>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Choose a branch and upcoming cycle to see its forecast range before submitting a stock request.</p>
        </div>

        <div class="grid gap-3 sm:min-w-[520px] sm:grid-cols-[minmax(180px,1fr)_150px_120px]">
            <label class="block">
                <span class="mb-1.5 block text-xs font-medium text-slate-600">Branch or region</span>
                <select wire:model.live="locationId" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Choose a location</option>
                    @foreach ($this->locations as $locationOption)
                        <option wire:key="location-option-{{ $locationOption->id }}" value="{{ $locationOption->id }}">{{ $locationOption->name }} · {{ $locationOption->code }}</option>
                    @endforeach
                </select>
                @error('locationId') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-medium text-slate-600">Target week</span>
                <select wire:model.live="weekNumber" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    @forelse ($this->weekOptions as $weekOption)
                        <option value="{{ $weekOption }}">Week {{ str_pad((string) $weekOption, 2, '0', STR_PAD_LEFT) }}</option>
                    @empty
                        <option value="{{ $weekNumber }}">No upcoming weeks</option>
                    @endforelse
                </select>
                @error('weekNumber') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-medium text-slate-600">Year</span>
                <select wire:model.live="year" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    @foreach ($this->years as $yearOption)
                        <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                    @endforeach
                </select>
                @error('year') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
            </label>
        </div>
    </section>

    @if ($statusMessage)
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ $statusMessage }}
        </div>
    @endif

    <div wire:loading wire:target="locationId,weekNumber,year,refreshForecast" class="rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-700">
        Updating forecast for Week {{ str_pad((string) $weekNumber, 2, '0', STR_PAD_LEFT) }}, {{ $year }}…
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white/90 shadow-sm shadow-slate-200/50 backdrop-blur" aria-labelledby="forecast-heading">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-start sm:px-7">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">AI demand forecast</p>
                <h2 id="forecast-heading" class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Weekly prediction band</h2>
                <p class="mt-1 text-sm text-slate-500">Low threshold, expected baseline, and upper demand boundary.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600">
                <span class="size-2 rounded-full {{ $weatherSummary ? 'bg-sky-500' : 'bg-slate-300' }}" aria-hidden="true"></span>
                {{ $weatherSummary ?? 'Weather outlook unavailable' }}
            </span>
        </div>

        <div class="space-y-6 px-5 py-6 sm:px-7">
            @if ($forecastError)
                <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ $forecastError }}
                </div>
                <button type="button" wire:click="refreshForecast" wire:loading.attr="disabled" wire:target="refreshForecast" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-60">
                    <span wire:loading.remove wire:target="refreshForecast">Retry forecast</span>
                    <span wire:loading wire:target="refreshForecast">Retrying…</span>
                </button>
            @elseif ($forecast)
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200/70 bg-slate-50/80 p-4">
                        <p class="text-xs font-medium text-slate-500">LOW BAND <span class="text-slate-400">(L)</span></p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums tracking-tight text-slate-800">{{ number_format($forecast['low']) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Lower demand threshold</p>
                    </div>
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4">
                        <p class="text-xs font-medium text-indigo-600">EXPECTED <span class="text-indigo-400">(E)</span></p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums tracking-tight text-indigo-950">{{ number_format($forecast['expected']) }}</p>
                        <p class="mt-1 text-xs text-indigo-700/70">Median demand baseline</p>
                    </div>
                    <div class="rounded-xl border border-slate-200/70 bg-slate-50/80 p-4">
                        <p class="text-xs font-medium text-slate-500">HIGH BAND <span class="text-slate-400">(H)</span></p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums tracking-tight text-slate-800">{{ number_format($forecast['high']) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Upper demand threshold</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-100 bg-white px-4 py-4 sm:px-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold text-slate-600">Forecast range position</p>
                        <p class="text-[11px] text-slate-400">Unit scale shown relative to upper band</p>
                    </div>
                    <div class="relative h-4 rounded-full bg-slate-100" role="img" aria-label="Low band {{ $forecast['low'] }}, expected demand {{ $forecast['expected'] }}, high band {{ $forecast['high'] }}">
                        <div class="absolute top-0 h-4 rounded-full bg-gradient-to-r from-sky-300 via-indigo-400 to-violet-500" style="left: {{ $bandStart }}%; width: {{ $bandWidth }}%"></div>
                        <span class="absolute top-1/2 size-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-[3px] border-white bg-indigo-700 shadow ring-1 ring-indigo-200" style="left: {{ $expectedPosition }}%" aria-hidden="true"></span>
                    </div>
                    <div class="mt-2 flex justify-between text-[11px] font-medium tabular-nums text-slate-500">
                        <span>L · {{ number_format($forecast['low']) }}</span>
                        <span>E · {{ number_format($forecast['expected']) }}</span>
                        <span>H · {{ number_format($forecast['high']) }}</span>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-8 text-center text-sm text-slate-500">
                    Select a location with an upcoming weekly cycle to load its forecast.
                </div>
            @endif
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.65fr)]">
        <section class="rounded-2xl border border-slate-200/60 bg-white/90 p-5 shadow-sm shadow-slate-200/50 backdrop-blur sm:p-7" aria-labelledby="request-heading">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Request planning</p>
                    <h2 id="request-heading" class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Weekly requested quantity (R)</h2>
                    <p class="mt-1 text-sm text-slate-500">Your request is checked against the selected forecast band.</p>
                </div>
                @if ($status)
                    <span role="status" class="inline-flex w-fit items-center rounded-full border px-3 py-1.5 text-xs font-semibold {{ $status['classes'] }}">
                        {{ $status['label'] }}
                    </span>
                @endif
            </div>

            <form wire:submit="submitRequest" class="mt-6 space-y-5">
                <label class="block max-w-md">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Weekly Requested Quantity (R)</span>
                    <div class="relative">
                        <input type="number" min="1" step="1" inputmode="numeric" wire:model.live.debounce.200ms="requestedQuantity" @disabled(! $forecast) aria-describedby="quantity-help" class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 pr-16 text-lg font-semibold tabular-nums text-slate-900 shadow-sm outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-slate-50">
                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-medium text-slate-400">units</span>
                    </div>
                    <span id="quantity-help" class="mt-1.5 block text-xs text-slate-500">Enter a whole number greater than zero.</span>
                    @error('requestedQuantity') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                </label>

                <div class="flex flex-col justify-between gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                    <p class="max-w-sm text-xs leading-5 text-slate-500">A below-band request is noted as stockout risk. Requests above the high band are sent for manager review.</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-wait disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveDraft">Save Draft</span>
                            <span wire:loading wire:target="saveDraft">Saving…</span>
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="submitRequest" @disabled(! $forecast) class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm shadow-indigo-200 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitRequest">Submit Weekly Request</span>
                            <span wire:loading wire:target="submitRequest">Submitting…</span>
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <aside class="rounded-2xl border border-slate-200/60 bg-white/90 p-5 shadow-sm shadow-slate-200/50 backdrop-blur sm:p-6" aria-labelledby="context-heading">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Past performance</p>
                <h2 id="context-heading" class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Your recent context</h2>
            </div>

            <div class="mt-5 space-y-3">
                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                    <span class="text-sm text-slate-600">4-week average sales (S)</span>
                    <span class="text-base font-semibold tabular-nums text-slate-900">{{ number_format($context['weekly_average'], 1) }}</span>
                </div>
                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                    <span class="text-sm text-slate-600">Last month request accuracy</span>
                    <span class="text-base font-semibold tabular-nums text-slate-900">
                        {{ $context['accuracy_score'] === null ? '—' : number_format($context['accuracy_score'], 1).'%' }}
                    </span>
                </div>
                <div class="rounded-xl border p-4 {{ $context['unmet_demand_alert'] ? 'border-amber-200 bg-amber-50' : 'border-emerald-100 bg-emerald-50/70' }}">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 size-2 shrink-0 rounded-full {{ $context['unmet_demand_alert'] ? 'bg-amber-500' : 'bg-emerald-500' }}" aria-hidden="true"></span>
                        <div>
                            <p class="text-sm font-semibold {{ $context['unmet_demand_alert'] ? 'text-amber-900' : 'text-emerald-900' }}">
                                {{ $context['unmet_demand_alert'] ? 'Recent short-supply alert' : 'No recent short-supply alerts' }}
                            </p>
                            <p class="mt-1 text-xs leading-5 {{ $context['unmet_demand_alert'] ? 'text-amber-800/80' : 'text-emerald-800/80' }}">
                                {{ $context['unmet_demand_alert'] ? 'Your company reported limited supply in the past four weeks.' : 'No company short-supply reports were recorded in the past four weeks.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

@php
    $details = $this->selectedLocationDetails;
@endphp

<div x-data="{ detailsOpen: @entangle('showDetails').live }" @keydown.escape.window="detailsOpen = false; $wire.closeDetails()" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
            <p class="text-sm font-medium text-indigo-600">SUPPLY INTELLIGENCE</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[28px]">Distributor Demand Forecasts</h1>
            <p class="mt-1 text-sm text-slate-500">Current week · W{{ str_pad((string) now()->isoWeek, 2, '0', STR_PAD_LEFT) }} {{ now()->isoWeekYear }}</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:w-[900px] xl:grid-cols-[minmax(220px,1fr)_180px_180px_180px]">
            <label class="relative">
                <span class="sr-only">Search distributor or location code</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400" aria-hidden="true">⌕</span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search distributor or code" class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label>
                <span class="sr-only">Filter by location</span>
                <select wire:model.live="locationFilter" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    <option value="">All locations</option>
                    @foreach ($this->locationOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->code }} · {{ $option->distributor->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">Filter by request status</span>
                <select wire:model.live="statusFilter" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    <option value="">All statuses</option>
                    <option value="auto_approved">Auto-approved</option>
                    <option value="flagged">Flagged</option>
                    <option value="pending">Pending</option>
                </select>
            </label>
            <label>
                <span class="sr-only">Filter by forecast risk</span>
                <select wire:model.live="riskFilter" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    <option value="">All range positions</option>
                    <option value="within">Within band</option>
                    <option value="outside">Outside band</option>
                    <option value="pending">No submission</option>
                </select>
            </label>
            <label class="flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700">
                <input type="checkbox" wire:model.live="highRiskOnly" class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span>High risk only (R &gt; H)</span>
            </label>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-sm" aria-label="Distributor forecast table">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
            <div>
                <h2 class="text-sm font-semibold tracking-tight text-slate-900">Forecast position by location</h2>
                <p class="mt-1 text-xs text-slate-500">Requested quantity (R) compared with the low and high forecast bounds.</p>
            </div>
            <span class="text-xs text-slate-400" wire:loading>Refreshing forecasts…</span>
            <span class="text-xs text-slate-500" wire:loading.remove>{{ $this->forecastRows->count() }} locations</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-semibold tracking-wide text-slate-400">
                    <tr>
                        <th scope="col" class="px-5 py-3.5 sm:px-6">DISTRIBUTOR / LOCATION</th>
                        <th scope="col" class="px-3 py-3.5 text-right">LOW (L)</th>
                        <th scope="col" class="px-3 py-3.5 text-right">EXPECTED (E)</th>
                        <th scope="col" class="px-3 py-3.5 text-right">HIGH (H)</th>
                        <th scope="col" class="px-3 py-3.5 text-right">REQUEST (R)</th>
                        <th scope="col" class="w-[280px] px-4 py-3.5">RANGE POSITION</th>
                        <th scope="col" class="px-5 py-3.5 sm:px-6">STATUS</th>
                        <th scope="col" class="px-5 py-3.5 text-right sm:px-6">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->forecastRows as $row)
                        @php
                            $forecast = $row['forecast'];
                            $requested = $row['requested_quantity'];
                            $range = max(1, $forecast->high_band - $forecast->low_band);
                            $marker = $requested === null
                                ? null
                                : min(100, max(0, (($requested - $forecast->low_band) / $range) * 100));
                            $status = $row['status'];
                        @endphp
                        <tr wire:key="forecast-{{ $forecast->id }}" class="transition-colors hover:bg-slate-50/70">
                            <td class="px-5 py-4 sm:px-6">
                                <button type="button" wire:click="openDetails({{ $row['location']->id }}, {{ $row['request']?->id ?? 'null' }})" class="group text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                                    <span class="block font-semibold text-slate-800 group-hover:text-indigo-700">{{ $row['distributor']->company_name }}</span>
                                    <span class="mt-1 flex items-center gap-1.5 text-xs text-slate-500"><span class="font-mono font-medium text-slate-600">{{ $row['location']->code }}</span><span aria-hidden="true">·</span>{{ $row['location']->name }}<span class="text-indigo-500" aria-hidden="true">↗</span></span>
                                </button>
                            </td>
                            <td class="px-3 py-4 text-right tabular-nums text-slate-600">{{ number_format($forecast->low_band) }}</td>
                            <td class="px-3 py-4 text-right font-medium tabular-nums text-slate-800">{{ number_format($forecast->expected_band) }}</td>
                            <td class="px-3 py-4 text-right tabular-nums text-slate-600">{{ number_format($forecast->high_band) }}</td>
                            <td class="px-3 py-4 text-right font-semibold tabular-nums {{ $row['risk'] === 'outside' ? 'text-amber-700' : 'text-slate-900' }}">{{ $requested === null ? '—' : number_format($requested) }}</td>
                            <td class="px-4 py-4">
                                <div class="relative h-7" aria-label="{{ $requested === null ? 'No request submitted' : 'Requested quantity '.number_format($requested).($row['risk'] === 'outside' ? ', outside forecast range' : ', within forecast range') }}">
                                    <div class="absolute inset-x-0 top-2.5 h-2 rounded-full bg-slate-100"></div>
                                    <div class="absolute left-0 top-2.5 h-2 w-full rounded-full bg-gradient-to-r from-amber-100 via-emerald-100 to-amber-100"></div>
                                    <div class="absolute left-1/2 top-0 h-7 border-l border-dashed border-slate-300"></div>
                                    @if ($marker !== null)
                                        <span class="absolute top-0.5 size-5 -translate-x-1/2 rounded-full border-[3px] border-white shadow ring-1 {{ $row['risk'] === 'outside' ? 'bg-amber-500 ring-amber-200' : 'bg-indigo-600 ring-indigo-200' }}" style="left: {{ $marker }}%"></span>
                                        @if ($row['outside_side'] === 'low')<span class="absolute left-0 top-[-2px] text-[9px] font-semibold text-amber-700">OUT</span>@elseif ($row['outside_side'] === 'high')<span class="absolute right-0 top-[-2px] text-[9px] font-semibold text-amber-700">OUT</span>@endif
                                    @else
                                        <span class="absolute left-1/2 top-1 size-3 -translate-x-1/2 rounded-full bg-slate-300 ring-4 ring-white"></span>
                                    @endif
                                    <div class="absolute inset-x-0 bottom-0 flex justify-between text-[9px] tabular-nums text-slate-400"><span>L</span><span>E</span><span>H</span></div>
                                </div>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                @if ($status === 'auto_approved')
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Auto-approved</span>
                                @elseif ($status === 'flagged')
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Flagged</span>
                                @elseif ($status === 'pending')
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $row['request'] ? 'Pending review' : 'Pending submission' }}</span>
                                @elseif ($status === 'adjusted')
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">Adjusted</span>
                                @else
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <div class="inline-flex items-center gap-2">
                                    @if ($row['reviewable'])
                                        <button type="button" wire:click="openDetails({{ $row['location']->id }}, {{ $row['request']->id }})" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Review Request</button>
                                    @endif
                                    <button type="button" wire:click="openDetails({{ $row['location']->id }})" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">View Analytics</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <span class="mx-auto grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500" aria-hidden="true">⌕</span>
                                <p class="mt-3 text-sm font-semibold text-slate-800">No forecasts match these filters</p>
                                <p class="mt-1 text-xs text-slate-500">Try another location, risk level, or search term.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <span>Forecast ranges shown for the current ISO week. Delivered quantities use confirmed receipts.</span>
            <span class="flex items-center gap-3"><span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-indigo-600"></i>Request</span><span class="inline-flex items-center gap-1.5"><i class="size-2 rounded-full bg-amber-500"></i>Outside range</span></span>
        </div>
    </section>

    <div x-cloak x-show="detailsOpen" class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="forecast-detail-title" role="dialog" aria-modal="true">
        <div x-show="detailsOpen" x-transition.opacity class="absolute inset-0 bg-slate-950/35" @click="detailsOpen = false; $wire.closeDetails()"></div>
        <section x-show="detailsOpen" x-transition:enter="transform transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col border-l border-slate-200 bg-slate-50 shadow-2xl">
            <header class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-5 sm:px-7">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-indigo-600">LOCATION PERFORMANCE</p>
                    <h2 id="forecast-detail-title" class="mt-1 text-xl font-semibold tracking-tight text-slate-950">{{ $details['location']->distributor->company_name ?? 'Forecast details' }}</h2>
                    @if ($details)<p class="mt-1 text-sm text-slate-500">{{ $details['location']->name }} · <span class="font-mono">{{ $details['location']->code }}</span></p>@endif
                </div>
                <button type="button" @click="detailsOpen = false; $wire.closeDetails()" class="grid size-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-lg text-slate-500 hover:bg-slate-50" aria-label="Close forecast details">×</button>
            </header>

            <div class="flex-1 space-y-5 overflow-y-auto p-5 sm:p-7">
                @if ($details)
                    @if ($reviewNotice)
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ $reviewNotice }}</div>
                    @endif

                    @if ($details['review_request'] && in_array($details['review_request']->status, ['flagged', 'pending'], true))
                        <article class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-5 shadow-sm sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-[10px] font-semibold tracking-wide text-indigo-600">MANAGER REVIEW</p>
                                    <h3 class="mt-1 text-sm font-semibold text-slate-900">Request {{ number_format($details['review_request']->requested_qty) }} units</h3>
                                    <p class="mt-1 text-xs text-slate-500">Choose the requested quantity or set an override of up to the submitted quantity.</p>
                                </div>
                                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-indigo-700">{{ ucfirst($details['review_request']->status) }}</span>
                            </div>
                            <label class="mt-4 block text-xs font-medium text-slate-600">
                                Approved quantity
                                <input type="number" min="0" max="{{ $details['review_request']->requested_qty }}" wire:model="approvedQuantity" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                                @error('approvedQuantity')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="mt-3 flex items-start gap-2 text-xs text-slate-600">
                                <input type="checkbox" wire:model="companyShortSupply" class="mt-0.5 size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>Company short-supply exemption</span>
                            </label>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" wire:click="approveRequested" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">Approve Requested (A = R)</button>
                                <button type="button" wire:click="overrideRequest" wire:loading.attr="disabled" class="rounded-lg border border-indigo-200 bg-white px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-60">Save Custom Override</button>
                            </div>
                            @error('demand_request')<p class="mt-3 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </article>
                    @endif

                    <article class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><h3 class="text-sm font-semibold text-slate-900">Past 4 weeks: requested vs delivered vs sold</h3><p class="mt-1 text-xs text-slate-500">Delivered is based on receipt-confirmed quantities.</p></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-medium text-slate-600">R / D / S · units</span>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[420px] text-left text-xs">
                                <thead class="text-[10px] font-semibold tracking-wide text-slate-400"><tr><th class="py-2">WEEK</th><th class="px-2 py-2 text-right">REQUESTED (R)</th><th class="px-2 py-2 text-right">DELIVERED (D)</th><th class="py-2 text-right">SOLD (S)</th></tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($details['weekly_metrics'] as $metric)
                                        <tr><th class="py-2.5 font-medium text-slate-600">{{ $metric['label'] }}</th><td class="px-2 py-2.5 text-right tabular-nums text-slate-700">{{ $metric['requested'] === null ? '—' : number_format($metric['requested']) }}</td><td class="px-2 py-2.5 text-right tabular-nums text-slate-700">{{ $metric['delivered'] === null ? '—' : number_format($metric['delivered']) }}</td><td class="py-2.5 text-right tabular-nums text-slate-700">{{ $metric['sold'] === null ? '—' : number_format($metric['sold']) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="text-sm font-semibold text-slate-900">Five-year seasonal sales comparison</h3><p class="mt-1 text-xs text-slate-500">Average weekly sold units by calendar month.</p></div><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-medium text-indigo-700">{{ min(5, count($details['seasonal_years'])) }} years</span></div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[600px] text-left text-xs">
                                <thead class="text-[10px] font-semibold tracking-wide text-slate-400"><tr><th class="py-2">MONTH</th>@foreach ($details['seasonal_years'] as $seasonalYear)<th class="px-2 py-2 text-right">{{ $seasonalYear }}</th>@endforeach</tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($details['seasonal_comparison'] as $month)
                                        <tr><th class="py-2 font-medium text-slate-600">{{ $month['month'] }}</th>@foreach ($details['seasonal_years'] as $seasonalYear)<td class="px-2 py-2 text-right tabular-nums text-slate-700">{{ $month['values'][$seasonalYear] === null ? '—' : number_format($month['values'][$seasonalYear], 1) }}</td>@endforeach</tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="text-sm font-semibold text-slate-900">Historical sales vs forecast</h3><p class="mt-1 text-xs text-slate-500">Completed request periods from up to five years of history · units</p></div><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-medium text-indigo-700">{{ $details['history']->count() }} weeks</span></div>
                        @if ($details['history']->isNotEmpty())
                            @php
                                $salesPoints = $details['history']->map(fn (array $point, int $index): string => round(32 + ($index * (636 / max(1, $details['history']->count() - 1))), 1).','.round(176 - (min(100, $point['sales_y']) * 1.35), 1))->implode(' ');
                                $forecastPoints = $details['history']->map(fn (array $point, int $index): string => round(32 + ($index * (636 / max(1, $details['history']->count() - 1))), 1).','.round(176 - (min(100, $point['forecast_y']) * 1.35), 1))->implode(' ');
                            @endphp
                            <div class="mt-5 overflow-x-auto">
                                <div class="min-w-[480px]">
                                    <svg viewBox="0 0 700 205" class="h-48 w-full" role="img" aria-label="Historical sales compared with forecast expected values">
                                        <line x1="32" y1="176" x2="668" y2="176" stroke="#e2e8f0" />
                                        <line x1="32" y1="108" x2="668" y2="108" stroke="#f1f5f9" />
                                        <line x1="32" y1="40" x2="668" y2="40" stroke="#f1f5f9" />
                                        <polyline points="{{ $forecastPoints }}" fill="none" stroke="#a5b4fc" stroke-width="3" stroke-dasharray="7 6" stroke-linecap="round" stroke-linejoin="round" />
                                        <polyline points="{{ $salesPoints }}" fill="none" stroke="#4f46e5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                        @foreach ($details['history'] as $index => $point)
                                            <circle cx="{{ round(32 + ($index * (636 / max(1, $details['history']->count() - 1))), 1) }}" cy="{{ round(176 - (min(100, $point['sales_y']) * 1.35), 1) }}" r="4" fill="#4f46e5" />
                                        @endforeach
                                    </svg>
                                    <div class="flex justify-between px-2 text-[10px] text-slate-400">@foreach ($details['history'] as $point)<span>{{ $point['label'] }}</span>@endforeach</div>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs"><span class="inline-flex items-center gap-1.5 text-slate-600"><i class="h-0.5 w-4 bg-indigo-600"></i>Actual sales</span><span class="inline-flex items-center gap-1.5 text-slate-600"><i class="h-0.5 w-4 border-t-2 border-dashed border-indigo-300"></i>Expected forecast</span><span class="ml-auto font-medium text-slate-700">Mean accuracy {{ number_format($details['history']->avg('accuracy'), 1) }}%</span></div>
                        @else
                            <div class="mt-5 grid h-44 place-items-center rounded-xl bg-slate-50 text-center"><div><span class="mx-auto grid size-9 place-items-center rounded-full bg-white text-slate-400">⌁</span><p class="mt-2 text-sm font-medium text-slate-700">No completed sales periods</p><p class="mt-1 text-xs text-slate-500">History appears after sales have been recorded.</p></div></div>
                        @endif
                    </article>

                    <article class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="text-sm font-semibold text-slate-900">Recent market context</h3><p class="mt-1 text-xs text-slate-500">Weather and regional inflation observations</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-medium text-slate-600">Context only</span></div>
                        @if ($details['contexts']->isNotEmpty())
                            <div class="mt-4 divide-y divide-slate-100">
                                @foreach ($details['contexts'] as $context)
                                    <div class="flex flex-col justify-between gap-2 py-3 first:pt-0 sm:flex-row sm:items-center">
                                        <span class="text-xs font-medium text-slate-500">{{ $context->observed_on->format('M j, Y') }}</span>
                                        <div class="flex flex-wrap gap-2">
                                            @if ($context->weather_summary)
                                                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">
                                                    {{ $context->weather_summary }}
                                                    @if ($context->temperature_celsius !== null)
                                                        · {{ number_format((float) $context->temperature_celsius, 1) }}°C
                                                    @endif
                                                </span>
                                            @endif
                                            @if ($context->inflation_rate !== null)
                                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                                    Inflation {{ number_format((float) $context->inflation_rate, 1) }}%
                                                    @if ($context->inflation_region)
                                                        · {{ $context->inflation_region }}
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-5 rounded-xl bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">No weather or inflation observations have been recorded for this location.</p>
                        @endif
                    </article>
                @else
                    <div class="grid h-64 place-items-center rounded-2xl border border-dashed border-slate-300 bg-white text-center"><p class="text-sm text-slate-500">Select a distributor location to inspect its performance.</p></div>
                @endif
            </div>
        </section>
    </div>
</div>
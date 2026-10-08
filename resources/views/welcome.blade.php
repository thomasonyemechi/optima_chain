<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <meta name="description" content="Optima Chain connects AI-powered demand forecasting, supply-chain fulfillment, and verifiable distributor credit scoring.">
    <title>Optima Chain · Supply intelligence and verifiable credit</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-x-hidden bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute left-1/2 top-[-32rem] size-[58rem] -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.11),transparent_68%)]"></div>
        <div class="absolute right-[-24rem] top-[36rem] size-[48rem] rounded-full bg-[radial-gradient(ellipse_at_center,rgba(16,185,129,0.08),transparent_68%)]"></div>
    </div>

    <header class="sticky top-0 z-40 border-b border-slate-200/70 bg-slate-50/85 backdrop-blur-xl">
        <nav class="mx-auto flex h-[72px] max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8" aria-label="Main navigation">
            <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2.5" aria-label="Optima Chain home">
                <span class="relative grid size-9 place-items-center overflow-hidden rounded-xl bg-indigo-600 shadow-lg shadow-indigo-600/20">
                    <span class="absolute -right-1 -top-1 size-5 rounded-full bg-emerald-400"></span>
                    <svg class="relative size-5 text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="m8 12 2.5 2.5L16.5 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="text-[15px] font-bold tracking-tight text-slate-950">Optima<span class="text-indigo-600">Chain</span></span>
            </a>

            <div class="hidden items-center gap-7 lg:flex">
                <a href="#features" class="text-sm font-medium text-slate-600 transition hover:text-indigo-700">Features</a>
                <a href="#how-it-works" class="text-sm font-medium text-slate-600 transition hover:text-indigo-700">How it works</a>
                <a href="#credit-scoring" class="text-sm font-medium text-slate-600 transition hover:text-indigo-700">Credit scoring</a>
                <a href="#bank-verification" class="text-sm font-medium text-slate-600 transition hover:text-indigo-700">Bank verification</a>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}" class="hidden rounded-lg px-3.5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-white hover:text-slate-900 sm:inline-flex">Log in</a>
                <a href="{{ route('login') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm shadow-indigo-600/15 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Access portal <span aria-hidden="true">↗</span>
                </a>
            </div>
        </nav>
    </header>

    <main>
        <section class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 pb-20 pt-16 sm:px-6 sm:pb-24 sm:pt-20 lg:grid-cols-[1.02fr_0.98fr] lg:gap-10 lg:px-8 lg:pb-28 lg:pt-24">
            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white/80 px-3.5 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm backdrop-blur">
                    <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                    AI-powered supply chain intelligence
                    <span aria-hidden="true">✦</span>
                </div>

                <h1 class="mt-7 max-w-3xl text-4xl font-bold leading-[1.08] tracking-[-0.045em] text-slate-950 sm:text-5xl lg:text-[3.65rem]">
                    Data-driven demand forecasting
                    <span class="bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-500 bg-clip-text text-transparent">and instant bank credit scoring.</span>
                </h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-slate-600 sm:text-lg sm:leading-8">
                    Eliminate stockouts, streamline weekly fulfillment, and build verifiable creditworthiness backed by real operating performance.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Distributor portal <span aria-hidden="true">→</span>
                    </a>
                    <a href="#bank-verification" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Bank verification <span aria-hidden="true">↗</span>
                    </a>
                </div>

                <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-3 text-xs font-medium text-slate-500">
                    <span class="inline-flex items-center gap-2"><svg class="size-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.97a.75.75 0 0 0-1.06-1.06L9 10.69 7.28 8.97a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.06 0l4.25-4.25Z" clip-rule="evenodd"/></svg>One connected operating record</span>
                    <span class="inline-flex items-center gap-2"><svg class="size-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.97a.75.75 0 0 0-1.06-1.06L9 10.69 7.28 8.97a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.06 0l4.25-4.25Z" clip-rule="evenodd"/></svg>Forecasts grounded in performance</span>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-[570px] lg:ml-auto">
                <div class="absolute -inset-6 rounded-[2.5rem] bg-gradient-to-br from-indigo-200/40 via-white/30 to-emerald-100/50 blur-2xl" aria-hidden="true"></div>
                <div class="relative rounded-[1.75rem] border border-slate-200/80 bg-white/75 p-3 shadow-[0_32px_90px_-38px_rgba(30,41,59,0.35)] backdrop-blur-xl sm:p-4">
                    <div class="overflow-hidden rounded-[1.25rem] border border-slate-200/80 bg-white">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-5">
                            <div class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-xl bg-indigo-50 text-indigo-600"><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 17.5 8.5 12l4 3.5L21 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 6h6v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <div><p class="text-xs font-semibold text-slate-900">Weekly demand outlook</p><p class="mt-0.5 text-[10px] text-slate-500">Lagos Central · Week 42</p></div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700"><span class="size-1.5 rounded-full bg-emerald-500"></span>Live forecast</span>
                        </div>

                        <div class="p-4 sm:p-5">
                            <div class="grid grid-cols-3 gap-3">
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3"><p class="text-[10px] font-medium text-slate-500">Low band · L</p><p class="mt-1.5 text-xl font-bold tracking-tight text-slate-800">1,180</p><p class="mt-1 text-[10px] text-slate-400">Lower threshold</p></div>
                                <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 ring-1 ring-indigo-100/60"><p class="text-[10px] font-semibold text-indigo-600">Expected · E</p><p class="mt-1.5 text-xl font-bold tracking-tight text-indigo-700">1,460</p><p class="mt-1 text-[10px] text-indigo-500">Baseline target</p></div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3"><p class="text-[10px] font-medium text-slate-500">High band · H</p><p class="mt-1.5 text-xl font-bold tracking-tight text-slate-800">1,820</p><p class="mt-1 text-[10px] text-slate-400">Upper threshold</p></div>
                            </div>

                            <div class="mt-5 rounded-xl border border-slate-100 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div><p class="text-xs font-semibold text-slate-800">Request position</p><p class="mt-1 text-[10px] text-slate-500">Submitted quantity compared to forecast</p></div>
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">Auto-approve eligible</span>
                                </div>
                                <div class="relative mt-5 h-3 rounded-full bg-gradient-to-r from-amber-100 via-emerald-100 to-amber-100">
                                    <span class="absolute left-[38%] top-1/2 size-5 -translate-y-1/2 rounded-full border-[3px] border-white bg-indigo-600 shadow-md ring-1 ring-indigo-200"></span>
                                    <span class="absolute left-0 top-1/2 size-3 -translate-y-1/2 rounded-full bg-slate-400 ring-4 ring-white"></span>
                                    <span class="absolute right-0 top-1/2 size-3 -translate-y-1/2 rounded-full bg-slate-400 ring-4 ring-white"></span>
                                </div>
                                <div class="mt-2 flex justify-between text-[10px] font-medium text-slate-400"><span>L</span><span>E</span><span>H</span></div>
                            </div>

                            <div class="mt-3 grid grid-cols-[1fr_auto] items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-9 place-items-center rounded-xl bg-white text-emerald-600 shadow-sm ring-1 ring-slate-100"><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 5 6v5c0 4.4 3 8.5 7 10 4-1.5 7-5.6 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <div><p class="text-xs font-semibold text-slate-800">Verified performance score</p><p class="mt-0.5 text-[10px] text-slate-500">Accuracy · fulfillment · reliability</p></div>
                                </div>
                                <div class="text-right"><p class="text-lg font-bold tracking-tight text-slate-900">92<span class="text-xs font-semibold text-slate-400">/100</span></p><p class="text-[10px] font-semibold text-emerald-600">Grade A</p></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-4 py-3 text-[10px] text-slate-500 sm:px-5">
                            <span class="inline-flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-indigo-500"></span>AI-assisted · manager governed</span>
                            <span>Illustrative preview</span>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-5 -left-4 hidden items-center gap-2.5 rounded-2xl border border-slate-200/80 bg-white/90 px-4 py-3 shadow-xl shadow-slate-900/5 backdrop-blur sm:flex">
                    <span class="grid size-8 place-items-center rounded-xl bg-emerald-50 text-emerald-600">↗</span>
                    <div><p class="text-[10px] font-medium text-slate-500">Operating signal</p><p class="text-xs font-bold text-slate-800">A record lenders can verify</p></div>
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200/70 bg-white/55">
            <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:grid-cols-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-center gap-3 py-2 sm:border-r sm:border-slate-200"><span class="grid size-10 place-items-center rounded-xl bg-indigo-50 text-indigo-600">⌁</span><div><p class="text-xs font-semibold text-slate-900">Predict with context</p><p class="text-[11px] text-slate-500">Demand, weather, and market signals</p></div></div>
                <div class="flex items-center justify-center gap-3 py-2 sm:border-r sm:border-slate-200"><span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600">⇄</span><div><p class="text-xs font-semibold text-slate-900">Fulfill with accountability</p><p class="text-[11px] text-slate-500">A traceable request-to-sales workflow</p></div></div>
                <div class="flex items-center justify-center gap-3 py-2"><span class="grid size-10 place-items-center rounded-xl bg-violet-50 text-violet-600">✓</span><div><p class="text-xs font-semibold text-slate-900">Build verifiable trust</p><p class="text-[11px] text-slate-500">Performance records with integrity checks</p></div></div>
            </div>
        </section>

        <section id="features" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-20 sm:px-6 sm:py-24 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-bold tracking-[0.16em] text-indigo-600">ONE CONNECTED PLATFORM</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">From weekly demand to lender-ready proof.</h2>
                <p class="mt-4 text-sm leading-6 text-slate-600 sm:text-base">Bring distributors, operations teams, and financial partners onto a shared record of supply-chain performance.</p>
            </div>

            <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article class="group rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:-translate-y-1 hover:border-indigo-200 hover:shadow-md sm:p-7">
                    <span class="grid size-11 place-items-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white"><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 17.5 8.5 12l4 3.5L21 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 6h6v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <h3 class="mt-5 text-base font-bold tracking-tight text-slate-900">AI demand forecasting</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Quantile-based demand bands (L, E, H) factor in historical sales, weather, and inflation to guide weekly planning.</p>
                    <div class="mt-5 flex items-center gap-2 text-[11px] font-semibold text-indigo-600"><span class="rounded-md bg-indigo-50 px-2 py-1">LOW</span><span class="rounded-md bg-indigo-50 px-2 py-1">EXPECTED</span><span class="rounded-md bg-indigo-50 px-2 py-1">HIGH</span></div>
                </article>

                <article class="group rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:-translate-y-1 hover:border-indigo-200 hover:shadow-md sm:p-7">
                    <span class="grid size-11 place-items-center rounded-xl bg-amber-50 text-amber-600 transition group-hover:bg-indigo-600 group-hover:text-white"><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <h3 class="mt-5 text-base font-bold tracking-tight text-slate-900">Automated review queue</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Route in-band requests for streamlined approval and surface out-of-range quantities for manager review.</p>
                    <p class="mt-5 rounded-lg bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600">Eligible when <span class="font-semibold text-slate-900">L ≤ R ≤ H</span></p>
                </article>

                <article class="group rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:-translate-y-1 hover:border-indigo-200 hover:shadow-md sm:p-7">
                    <span class="grid size-11 place-items-center rounded-xl bg-rose-50 text-rose-600 transition group-hover:bg-indigo-600 group-hover:text-white"><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 4.9 2.7 18a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 4.9a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <h3 class="mt-5 text-base font-bold tracking-tight text-slate-900">Discrepancy tracking</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Create a reviewable ticket when confirmed receipt (C) differs from the logistics dispatch (D), with quantities attached.</p>
                    <p class="mt-5 inline-flex items-center gap-2 text-xs font-semibold text-rose-600"><span>D ≠ C</span><span class="text-slate-300">→</span><span>Discrepancy recorded</span></p>
                </article>

                <article id="credit-scoring" class="group scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:-translate-y-1 hover:border-indigo-200 hover:shadow-md sm:p-7">
                    <span class="grid size-11 place-items-center rounded-xl bg-emerald-50 text-emerald-600 transition group-hover:bg-indigo-600 group-hover:text-white"><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 5 6v5c0 4.4 3 8.5 7 10 4-1.5 7-5.6 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <h3 class="mt-5 text-base font-bold tracking-tight text-slate-900">Verifiable credit scorecards</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Monthly performance scores combine order accuracy, target achievement, timeliness, and payment signals.</p>
                    <div class="mt-5 flex items-center gap-2"><div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full w-[82%] rounded-full bg-emerald-500"></div></div><span class="text-[11px] font-semibold text-emerald-600">Performance-led</span></div>
                </article>

                <article class="group rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:-translate-y-1 hover:border-indigo-200 hover:shadow-md sm:p-7">
                    <span class="grid size-11 place-items-center rounded-xl bg-violet-50 text-violet-600 transition group-hover:bg-indigo-600 group-hover:text-white"><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v18m9-9H3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/></svg></span>
                    <h3 class="mt-5 text-base font-bold tracking-tight text-slate-900">Cryptographic audit trail</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Share public verification records so banks and institutional partners can check scorecard integrity independently.</p>
                    <p class="mt-5 font-mono text-[11px] text-slate-400">SHA-256 · independently verifiable</p>
                </article>

                <article class="group rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:-translate-y-1 hover:border-indigo-200 hover:shadow-md sm:p-7">
                    <span class="grid size-11 place-items-center rounded-xl bg-sky-50 text-sky-600 transition group-hover:bg-indigo-600 group-hover:text-white"><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v2m0 14v2M5.64 5.64l1.42 1.42m9.88 9.88 1.42 1.42M3 12h2m14 0h2M5.64 18.36l1.42-1.42m9.88-9.88 1.42-1.42" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.7"/></svg></span>
                    <h3 class="mt-5 text-base font-bold tracking-tight text-slate-900">Short-supply protection</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Record company short-supply exemptions so distributor performance is assessed in the context of actual fulfillment.</p>
                    <p class="mt-5 inline-flex items-center gap-2 text-xs font-semibold text-emerald-600"><span class="size-1.5 rounded-full bg-emerald-500"></span>Context-aware scoring</p>
                </article>
            </div>
        </section>

        <section id="how-it-works" class="scroll-mt-20 border-y border-slate-200/70 bg-white/65">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-24 lg:px-8">
                <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
                    <div class="max-w-lg">
                        <p class="text-xs font-bold tracking-[0.16em] text-indigo-600">A CLEAR OPERATING LOOP</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">One weekly workflow. A stronger monthly record.</h2>
                        <p class="mt-4 text-sm leading-6 text-slate-600">Every fulfilled request adds context to the next forecast and evidence to the distributor’s performance history.</p>
                        <a href="{{ route('login') }}" class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800">Start in the portal <span aria-hidden="true">→</span></a>
                    </div>

                    <ol class="relative space-y-3">
                        <li class="relative flex gap-4 rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm sm:gap-5 sm:p-5">
                            <span class="relative z-10 grid size-10 shrink-0 place-items-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-600/15">01</span>
                            <div class="flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-bold text-slate-900">Distributor submits weekly demand</h3><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-semibold text-indigo-700">Request · R</span></div><p class="mt-1.5 text-xs leading-5 text-slate-500">Choose a branch and enter the quantity needed for the upcoming cycle.</p></div>
                        </li>
                        <li class="relative flex gap-4 rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm sm:gap-5 sm:p-5">
                            <span class="relative z-10 grid size-10 shrink-0 place-items-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-600/15">02</span>
                            <div class="flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-bold text-slate-900">Forecast bands guide the decision</h3><span class="rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-semibold text-violet-700">AI · L / E / H</span></div><p class="mt-1.5 text-xs leading-5 text-slate-500">Requests in range can move through streamlined approval; exceptions are routed for review.</p></div>
                        </li>
                        <li class="relative flex gap-4 rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm sm:gap-5 sm:p-5">
                            <span class="relative z-10 grid size-10 shrink-0 place-items-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-600/15">03</span>
                            <div class="flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-bold text-slate-900">Logistics dispatches with a secure code</h3><span class="rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-semibold text-sky-700">Dispatch · D</span></div><p class="mt-1.5 text-xs leading-5 text-slate-500">Record delivered quantities and generate a six-digit confirmation code for receipt.</p></div>
                        </li>
                        <li class="relative flex gap-4 rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm sm:gap-5 sm:p-5">
                            <span class="relative z-10 grid size-10 shrink-0 place-items-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-md shadow-indigo-600/15">04</span>
                            <div class="flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-bold text-slate-900">Confirm receipt and record sales</h3><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">Receipt · C / S</span></div><p class="mt-1.5 text-xs leading-5 text-slate-500">Close the fulfillment loop with confirmed quantities and weekly sales.</p></div>
                        </li>
                        <li class="relative flex gap-4 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 shadow-sm sm:gap-5 sm:p-5">
                            <span class="relative z-10 grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-600 text-sm font-bold text-white shadow-md shadow-emerald-600/15">05</span>
                            <div class="flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-bold text-slate-900">Build a monthly credit performance record</h3><span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold text-emerald-700">Scorecard</span></div><p class="mt-1.5 text-xs leading-5 text-slate-500">Aggregate verified operating indicators into a record that financing partners can check.</p></div>
                        </li>
                    </ol>
                </div>
            </div>
        </section>

        <section id="bank-verification" class="scroll-mt-20 mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-24 lg:px-8">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-sm">
                <div class="grid lg:grid-cols-[1fr_0.92fr]">
                    <div class="p-6 sm:p-9 lg:p-12">
                        <p class="text-xs font-bold tracking-[0.16em] text-indigo-600">BANK & INSTITUTIONAL PARTNERS</p>
                        <h2 class="mt-3 max-w-xl text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Verify the record behind the score.</h2>
                        <p class="mt-4 max-w-xl text-sm leading-6 text-slate-600">Check a distributor’s published performance certificate by its UUID verification code. Review the record period, aggregated score, and integrity hash.</p>

                        <form class="mt-7 max-w-xl" data-verification-form data-verify-base="{{ url('/verify') }}">
                            <label for="verification-code" class="mb-2 block text-xs font-semibold text-slate-700">Public verification code</label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <div class="relative min-w-0 flex-1">
                                    <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3m-9 0h8a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3v-5a3 3 0 0 1 3-3Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <input id="verification-code" name="code" type="text" required maxlength="36" inputmode="text" autocomplete="off" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-3 font-mono text-xs text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                                </div>
                                <button type="submit" class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Verify record <span aria-hidden="true">→</span></button>
                            </div>
                            <p class="mt-2 text-[11px] leading-5 text-slate-500">Enter the UUID provided with the distributor’s official score statement.</p>
                            <p class="hidden mt-2 text-xs font-medium text-rose-600" data-verification-error role="alert">Enter a valid verification code.</p>
                        </form>

                        <a href="{{ url('/bank/verification') }}" class="mt-5 inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Bank partner sign-in <span aria-hidden="true">↗</span></a>
                    </div>

                    <div class="relative overflow-hidden bg-slate-950 p-6 text-white sm:p-9 lg:p-10">
                        <div class="absolute -right-20 -top-28 size-80 rounded-full bg-indigo-500/20 blur-3xl" aria-hidden="true"></div>
                        <div class="absolute -bottom-28 -left-24 size-80 rounded-full bg-emerald-500/10 blur-3xl" aria-hidden="true"></div>
                        <div class="relative rounded-2xl border border-white/10 bg-white/[0.06] p-5 backdrop-blur sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-10 place-items-center rounded-xl bg-emerald-400/10 text-emerald-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 5 6v5c0 4.4 3 8.5 7 10 4-1.5 7-5.6 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <div><p class="text-xs font-semibold text-white">Audit certificate</p><p class="mt-1 text-[10px] text-slate-400">Performance record · Sample preview</p></div>
                                </div>
                                <span class="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-2.5 py-1 text-[10px] font-semibold text-emerald-300">VERIFIED SAMPLE</span>
                            </div>
                            <div class="mt-6 flex items-end justify-between border-b border-white/10 pb-5">
                                <div><p class="text-[10px] font-medium text-slate-400">ILLUSTRATIVE DISTRIBUTOR</p><p class="mt-1.5 text-sm font-semibold">Northstar Distribution</p><p class="mt-1 text-[10px] text-slate-400">Reporting period · Sep 2026</p></div>
                                <div class="text-right"><p class="text-3xl font-bold tracking-tight text-white">92<span class="text-sm font-medium text-slate-400">/100</span></p><p class="mt-1 text-[10px] font-semibold text-emerald-300">A · Credit score</p></div>
                            </div>
                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-white/10 bg-white/[0.04] p-3"><p class="text-[10px] text-slate-400">Order accuracy</p><p class="mt-1.5 text-sm font-semibold">94.2%</p></div>
                                <div class="rounded-xl border border-white/10 bg-white/[0.04] p-3"><p class="text-[10px] text-slate-400">Fulfillment</p><p class="mt-1.5 text-sm font-semibold">96.8%</p></div>
                            </div>
                            <div class="mt-4 rounded-xl border border-white/10 bg-black/20 p-3.5">
                                <div class="flex items-center justify-between gap-3"><p class="text-[10px] font-semibold text-slate-300">Cryptographic integrity</p><span class="inline-flex items-center gap-1.5 text-[10px] font-medium text-emerald-300"><span class="size-1.5 rounded-full bg-emerald-400"></span>Hash check</span></div>
                                <p class="mt-2 break-all font-mono text-[9px] leading-4 text-slate-500">a48f9d2c…7e30b51a · SHA-256</p>
                            </div>
                            <p class="mt-4 text-[10px] leading-4 text-slate-500">Illustrative preview only. Live verification is available using a published record code.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-4 pb-20 sm:px-6 sm:pb-24 lg:px-8">
            <div class="mx-auto flex max-w-7xl flex-col justify-between gap-7 overflow-hidden rounded-[2rem] bg-indigo-600 px-6 py-9 text-white shadow-xl shadow-indigo-600/15 sm:px-10 sm:py-11 lg:flex-row lg:items-center lg:px-12">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold tracking-[0.16em] text-indigo-200">BETTER SIGNALS. STRONGER PARTNERSHIPS.</p>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Make every delivery count.</h2>
                    <p class="mt-2 text-sm leading-6 text-indigo-100">Connect weekly operations with a performance story that can travel beyond your supply chain.</p>
                </div>
                <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                    <a href="{{ route('login') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-white px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">Access your portal <span aria-hidden="true">→</span></a>
                    <a href="#bank-verification" class="inline-flex h-11 items-center justify-center rounded-xl border border-white/25 px-4 text-sm font-semibold text-white transition hover:bg-white/10">Verify a record</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200/80 bg-white/65">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-[1.3fr_0.7fr_0.7fr_1fr]">
                <div>
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
                        <span class="grid size-8 place-items-center rounded-lg bg-indigo-600 text-white"><svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m8 12 2.5 2.5L16.5 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="text-sm font-bold tracking-tight text-slate-950">Optima<span class="text-indigo-600">Chain</span></span>
                    </a>
                    <p class="mt-3 max-w-xs text-xs leading-5 text-slate-500">Supply-chain intelligence and verifiable performance records for stronger business relationships.</p>
                </div>
                <div>
                    <h2 class="text-xs font-bold text-slate-900">Explore</h2>
                    <div class="mt-3 flex flex-col gap-2.5 text-xs text-slate-500">
                        <a href="#features" class="transition hover:text-indigo-600">Features</a>
                        <a href="#how-it-works" class="transition hover:text-indigo-600">How it works</a>
                        <a href="#credit-scoring" class="transition hover:text-indigo-600">Credit scoring</a>
                    </div>
                </div>
                <div>
                    <h2 class="text-xs font-bold text-slate-900">Portals</h2>
                    <div class="mt-3 flex flex-col gap-2.5 text-xs text-slate-500">
                        <a href="{{ route('login') }}" class="transition hover:text-indigo-600">Log in</a>
                        <a href="#bank-verification" class="transition hover:text-indigo-600">Bank verification</a>
                        <a href="#bank-verification" class="transition hover:text-indigo-600">Verify a record</a>
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <div id="privacy" class="scroll-mt-24 rounded-xl border border-slate-200/80 bg-white/80 p-3.5">
                        <h2 class="text-xs font-bold text-slate-800">Privacy</h2>
                        <p class="mt-1.5 text-[11px] leading-4 text-slate-500">Only share business and verification data with authorized partners. Contact your platform administrator for data-handling details.</p>
                    </div>
                    <div id="terms" class="scroll-mt-24 rounded-xl border border-slate-200/80 bg-white/80 p-3.5">
                        <h2 class="text-xs font-bold text-slate-800">Terms of service</h2>
                        <p class="mt-1.5 text-[11px] leading-4 text-slate-500">Platform records support operational review and do not constitute a lending decision or guarantee of future performance.</p>
                    </div>
                </div>
            </div>
            <div class="mt-8 flex flex-col justify-between gap-2 border-t border-slate-200/80 pt-5 text-[11px] text-slate-400 sm:flex-row sm:items-center">
                <p>© {{ now()->year }} Optima Chain. All rights reserved.</p>
                <div class="flex items-center gap-4"><a href="#privacy" class="hover:text-slate-600">Privacy policy</a><a href="#terms" class="hover:text-slate-600">Terms of service</a></div>
            </div>
        </div>
    </footer>

    <script>
        document.querySelectorAll('[data-verification-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const code = form.elements.code.value.trim();
                const error = form.querySelector('[data-verification-error]');
                const uuidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

                if (!uuidPattern.test(code)) {
                    error.classList.remove('hidden');
                    form.elements.code.focus();
                    return;
                }

                error.classList.add('hidden');
                window.location.assign(`${form.dataset.verifyBase}/${encodeURIComponent(code)}`);
            });
        });
    </script>
</body>
</html>

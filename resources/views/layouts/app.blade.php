<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Overview') · {{ config('app.name', 'Optima Chain') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    @php
        $currentRole = auth()->user()?->role ?? 'distributor';
        $currentPath = request()->path();
        $navGroups = [
            ['label' => 'WORKSPACE', 'items' => [
                ['label' => 'Overview', 'url' => 'distributor/dashboard', 'roles' => ['distributor'], 'icon' => '◫'],
                ['label' => 'Weekly request', 'url' => 'distributor/weekly-request', 'roles' => ['distributor'], 'icon' => '＋'],
                ['label' => 'Confirm receipt', 'url' => 'distributor/confirm-receipt', 'roles' => ['distributor'], 'icon' => '↙'],
                ['label' => 'Sales entry', 'url' => 'distributor/sales-entry', 'roles' => ['distributor'], 'icon' => '↗'],
                ['label' => 'Review queue', 'url' => 'manager/review-queue', 'roles' => ['manager', 'admin'], 'icon' => '≡'],
                ['label' => 'Demand forecasts', 'url' => 'manager/forecasts', 'roles' => ['manager', 'admin'], 'icon' => '⌁'],
                ['label' => 'Dispatch', 'url' => 'manager/dispatch-management', 'roles' => ['manager', 'admin'], 'icon' => '⇢'],
                ['label' => 'Complaints', 'url' => 'manager/complaints', 'roles' => ['manager', 'admin'], 'icon' => '◇'],
            ]],
            ['label' => 'INSIGHTS & TRUST', 'items' => [
                ['label' => 'Credit scorecard', 'url' => 'analytics/distributor-scorecard', 'roles' => ['distributor', 'manager', 'admin'], 'icon' => '◉'],
                ['label' => 'AI monitoring', 'url' => 'admin/ai-monitoring', 'roles' => ['admin'], 'icon' => '⌁'],
            ]],
        ];
        $displayName = auth()->user()?->name ?? 'Workspace member';
    @endphp

    <div class="min-h-screen lg:grid lg:grid-cols-[248px_minmax(0,1fr)]">
        <aside class="hidden border-r border-slate-200/80 bg-white lg:fixed lg:inset-y-0 lg:flex lg:w-[248px] lg:flex-col">
            <a href="{{ url('distributor/dashboard') }}" class="flex h-[72px] items-center gap-3 border-b border-slate-100 px-6">
                <span class="grid size-9 place-items-center rounded-xl bg-indigo-600 text-lg font-bold text-white">O</span>
                <span class="text-[15px] font-semibold tracking-tight">Optima<span class="text-indigo-600">Chain</span></span>
            </a>
            <nav class="flex-1 space-y-7 overflow-y-auto px-3 py-6" aria-label="Main navigation">
                @foreach ($navGroups as $group)
                    <div>
                        <p class="px-3 text-[10px] font-semibold tracking-[0.12em] text-slate-400">{{ $group['label'] }}</p>
                        <div class="mt-2 space-y-1">
                            @foreach ($group['items'] as $item)
                                @if (in_array($currentRole, $item['roles'], true))
                                    @php($isActive = $currentPath === $item['url'])
                                    <a href="{{ url($item['url']) }}" @if ($isActive) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-colors {{ $isActive ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                        <span class="grid size-5 place-items-center text-base {{ $isActive ? 'text-indigo-600' : 'text-slate-400' }}" aria-hidden="true">{{ $item['icon'] }}</span>
                                        <span>{{ $item['label'] }}</span>
                                        @if ($item['url'] === 'manager/review-queue' && (int) ($pendingReviewCount ?? 0) > 0)
                                            <span class="ml-auto rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800">{{ $pendingReviewCount }}</span>
                                        @endif
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
            <div class="border-t border-slate-100 p-4">
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700">{{ strtoupper(substr($displayName, 0, 1)) }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-semibold text-slate-800">{{ $displayName }}</span>
                        <span class="mt-0.5 block text-[11px] capitalize text-slate-500">{{ str_replace('_', ' ', $currentRole) }} account</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-medium text-slate-500 hover:bg-white hover:text-slate-800" aria-label="Sign out">↗</button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="min-w-0 lg:col-start-2 lg:pl-0">
            <header class="sticky top-0 z-30 flex h-[72px] items-center justify-between border-b border-slate-200/80 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <details class="relative lg:hidden">
                        <summary class="grid size-10 cursor-pointer list-none place-items-center rounded-lg border border-slate-200 text-slate-600" aria-label="Open navigation">☰</summary>
                        <nav class="absolute left-0 top-12 z-50 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-xl" aria-label="Mobile navigation">
                            @foreach ($navGroups as $group)
                                <p class="px-2 pb-1 pt-3 text-[10px] font-semibold tracking-[0.12em] text-slate-400">{{ $group['label'] }}</p>
                                @foreach ($group['items'] as $item)
                                    @if (in_array($currentRole, $item['roles'], true))
                                        <a href="{{ url($item['url']) }}" class="block rounded-lg px-2 py-2 text-sm {{ $currentPath === $item['url'] ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">{{ $item['label'] }}</a>
                                    @endif
                                @endforeach
                            @endforeach
                        </nav>
                    </details>
                    <label class="relative hidden w-full max-w-[360px] sm:block">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400" aria-hidden="true">⌕</span>
                        <input type="search" name="global-search" placeholder="Search orders, distributors..." class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-14 text-sm text-slate-700 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100">
                        <kbd class="absolute right-2 top-1/2 -translate-y-1/2 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] text-slate-400">⌘ K</kbd>
                    </label>
                    <p class="truncate text-sm font-medium text-slate-500 sm:hidden">@yield('title', 'Overview')</p>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 md:inline-flex"><span class="size-1.5 rounded-full bg-emerald-500"></span>All systems operational</span>
                    <button type="button" class="relative grid size-10 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Notifications">
                        <span aria-hidden="true">♧</span>
                        @if ((int) ($unreadNotificationCount ?? 0) > 0)<span class="absolute right-2 top-2 size-2 rounded-full border-2 border-white bg-red-500"></span>@endif
                    </button>
                    <span class="grid size-9 place-items-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 lg:hidden">{{ strtoupper(substr($displayName, 0, 1)) }}</span>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1440px] px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <p class="font-semibold">Please review the highlighted fields.</p>
                        <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
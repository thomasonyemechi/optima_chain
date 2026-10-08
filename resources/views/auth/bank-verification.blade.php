<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify a record · {{ config('app.name', 'Optima Chain') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white"><div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6"><span class="flex items-center gap-2.5"><span class="grid size-8 place-items-center rounded-lg bg-indigo-600 font-bold text-white">O</span><span class="text-sm font-semibold tracking-tight">Optima Chain</span></span><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="text-sm font-medium text-slate-500 hover:text-slate-800">Sign out</button></form></div></header>
    <main class="mx-auto max-w-2xl px-4 py-12 sm:px-6"><p class="text-xs font-semibold tracking-wide text-indigo-600">BANK & PARTNER PORTAL</p><h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Verify a distributor record</h1><p class="mt-2 text-sm leading-6 text-slate-500">Enter the UUID provided with the distributor's official score statement.</p>
        <form method="GET" action="{{ route('bank.verification.lookup') }}" class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><label for="code" class="mb-1.5 block text-sm font-medium text-slate-700">Verification code</label><div class="flex flex-col gap-3 sm:flex-row"><input id="code" name="code" type="text" inputmode="text" autocomplete="off" required value="{{ old('code', request('code')) }}" class="h-11 min-w-0 flex-1 rounded-lg border border-slate-200 px-3 font-mono text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><button type="submit" class="h-11 shrink-0 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Verify record</button></div>@error('code')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</form>
    </main>
</body>
</html>
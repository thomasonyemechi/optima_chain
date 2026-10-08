<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · {{ config('app.name', 'Optima Chain') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen bg-slate-50 font-sans text-slate-900 lg:grid-cols-[minmax(0,1fr)_minmax(420px,0.85fr)]">
    <aside class="hidden min-h-screen flex-col justify-between bg-slate-950 px-12 py-10 text-white lg:flex">
        <a href="{{ url('/') }}" class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-indigo-500 text-lg font-bold">O</span><span class="text-base font-semibold tracking-tight">Optima Chain</span></a>
        <div class="max-w-lg"><p class="text-xs font-semibold tracking-wide text-indigo-300">SUPPLY CHAIN FINANCE</p><h1 class="mt-4 text-4xl font-semibold leading-tight tracking-tight">Clear operations.<br>Stronger credit.</h1><p class="mt-4 max-w-md text-sm leading-6 text-slate-300">Manage replenishment, delivery records, and distributor performance from one connected workspace.</p></div>
        <p class="text-xs text-slate-400">Secure access for distributors and operations teams</p>
    </aside>
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-8">
        <div class="w-full max-w-md">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 lg:hidden"><span class="grid size-9 place-items-center rounded-xl bg-indigo-600 font-bold text-white">O</span><span class="text-sm font-semibold">Optima Chain</span></a>
            <div class="mt-10 lg:mt-0"><p class="text-sm font-medium text-indigo-600">WELCOME BACK</p><h2 class="mt-2 text-2xl font-semibold tracking-tight">Sign in to your workspace</h2><p class="mt-1 text-sm text-slate-500">Use the credentials associated with your account.</p></div>
            @if ($errors->any())<div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
                @csrf
                <div><label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label><input id="email" name="email" type="email" autocomplete="username" required autofocus value="{{ old('email') }}" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">Remember me</label>
                <button type="submit" class="h-11 w-full rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Sign in</button>
            </form>
            <p class="mt-6 text-center text-xs text-slate-400">Access is provisioned by your organization administrator.</p>
        </div>
    </main>
</body>
</html>
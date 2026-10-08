<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-medium text-indigo-600">MANAGER WORKSPACE</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">User access</h1>
            <p class="mt-1 text-sm text-slate-500">Create sign-in accounts for distributors, logistics staff, and bank auditors.</p>
        </div>
        <button type="button" wire:click="openModal" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            Create user
        </button>
    </div>

    @if (session()->has('created_user'))
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm" role="status" aria-live="polite">
            <div class="flex items-start gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true">✓</span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-semibold text-emerald-950">User account created</h2>
                    <p class="mt-1 text-sm text-emerald-800">{{ session('created_user.name') }} · {{ session('created_user.email') }}</p>
                    <p class="mt-3 text-xs text-emerald-800">Share this temporary password securely. It is shown here once.</p>
                    <code class="mt-2 inline-flex select-all rounded-lg border border-emerald-200 bg-white px-3 py-2 font-mono text-sm font-semibold tracking-wide text-slate-900">{{ session('created_user.temporary_password') }}</code>
                </div>
            </div>
        </section>
    @endif

    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="presentation">
            <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-[2px]" wire:click="closeModal"></div>
            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <section class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="create-user-title">
                    <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-7">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-indigo-600">ACCOUNT SETUP</p>
                            <h2 id="create-user-title" class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Create a user login</h2>
                            <p class="mt-1 text-sm text-slate-500">A temporary password will be generated after the account is saved.</p>
                        </div>
                        <button type="button" wire:click="closeModal" class="grid size-9 shrink-0 place-items-center rounded-lg border border-slate-200 text-lg text-slate-500 transition hover:bg-slate-50" aria-label="Close modal">×</button>
                    </header>

                    <form wire:submit="createUser" class="space-y-5 px-5 py-6 sm:px-7">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-slate-700">
                                Full name
                                <input type="text" wire:model="name" autocomplete="name" required maxlength="255" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" placeholder="Alex Morgan">
                                @error('name')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="block text-sm font-medium text-slate-700">
                                Email address
                                <input type="email" wire:model="email" autocomplete="email" required maxlength="255" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" placeholder="alex@example.com">
                                @error('email')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        </div>

                        <label class="block text-sm font-medium text-slate-700">
                            Account type
                            <select wire:model.live="role" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                                <option value="distributor">Distributor</option>
                                <option value="logistics">Logistics staff</option>
                                <option value="bank">Bank auditor</option>
                            </select>
                            @error('role')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                        </label>

                        @if ($role === 'distributor')
                            <div class="space-y-4 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 sm:p-5">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900">Distributor profile</h3>
                                    <p class="mt-1 text-xs text-slate-500">Required to set up the distributor's account.</p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="block text-sm font-medium text-slate-700 sm:col-span-2">
                                        Company name
                                        <input type="text" wire:model="company_name" maxlength="255" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" placeholder="Northstar Distribution">
                                        @error('company_name')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                                    </label>
                                    <label class="block text-sm font-medium text-slate-700">
                                        Account number
                                        <input type="text" wire:model="account_number" maxlength="255" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" placeholder="DIST-0001">
                                        @error('account_number')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                                    </label>
                                    <label class="block text-sm font-medium text-slate-700">
                                        Monthly target
                                        <input type="number" wire:model="monthly_target" min="0" max="9999999999.99" step="0.01" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100" placeholder="1000.00">
                                        @error('monthly_target')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                                    </label>
                                </div>
                            </div>
                        @endif

                        <footer class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                            <button type="button" wire:click="closeModal" class="h-10 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="createUser" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
                                <svg wire:loading wire:target="createUser" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path></svg>
                                <span wire:loading.remove wire:target="createUser">Create account</span>
                                <span wire:loading wire:target="createUser">Creating account…</span>
                            </button>
                        </footer>
                    </form>
                </section>
            </div>
        </div>
    @endif
</div>

<?php

namespace App\Livewire\Manager;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class CreateUserModal extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = 'distributor';

    public string $company_name = '';

    public string $account_number = '';

    public string $monthly_target = '';

    public bool $showModal = true;

    public function boot(): void
    {
        abort_unless(
            auth()->check() && in_array(auth()->user()->role, ['manager', 'admin'], true),
            403,
        );
    }

    public function openModal(): void
    {
        session()->forget('created_user');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function createUser(): void
    {
        $validated = $this->validate();
        $temporaryPassword = Str::random(10);

        $user = DB::transaction(function () use ($validated, $temporaryPassword): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($temporaryPassword),
                'role' => $validated['role'],
            ]);

            if ($validated['role'] === 'distributor') {
                $user->distributor()->create([
                    'company_name' => $validated['company_name'],
                    'account_number' => $validated['account_number'],
                    'monthly_target' => $validated['monthly_target'],
                ]);
            }

            return $user;
        });

        session()->flash('created_user', [
            'name' => $user->name,
            'email' => $user->email,
            'temporary_password' => $temporaryPassword,
        ]);

        $this->reset([
            'name',
            'email',
            'role',
            'company_name',
            'account_number',
            'monthly_target',
        ]);
        $this->resetValidation();
        $this->showModal = false;
    }

    protected function rules(): array
    {
        $isDistributor = $this->role === 'distributor';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['distributor', 'logistics', 'bank'])],
            'company_name' => [
                Rule::requiredIf($isDistributor),
                'nullable',
                'string',
                'max:255',
            ],
            'account_number' => [
                Rule::requiredIf($isDistributor),
                'nullable',
                'string',
                'max:255',
                Rule::unique('distributors', 'account_number'),
            ],
            'monthly_target' => [
                Rule::requiredIf($isDistributor),
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:9999999999.99',
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.manager.create-user-modal')
            ->layout('layouts.app', ['title' => 'Create User']);
    }
}

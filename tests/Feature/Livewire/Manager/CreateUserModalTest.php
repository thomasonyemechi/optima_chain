<?php

use App\Livewire\Manager\CreateUserModal;
use App\Models\Distributor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows managers and admins to open the user creation modal', function (string $role): void {
    $manager = User::factory()->create(['role' => $role]);

    $this->actingAs($manager)
        ->get(route('manager.users.create'))
        ->assertOk()
        ->assertSeeLivewire(CreateUserModal::class)
        ->assertSee('Create a user login');
})->with(['manager', 'admin']);

it('creates a distributor user and profile with a hashed temporary password', function (): void {
    $manager = User::factory()->create(['role' => 'manager']);

    $component = Livewire::actingAs($manager)
        ->test(CreateUserModal::class)
        ->set('name', 'Casey Distributor')
        ->set('email', 'casey@example.com')
        ->set('role', 'distributor')
        ->set('company_name', 'Casey Supply Co.')
        ->set('account_number', 'CASEY-100')
        ->set('monthly_target', '1250.50')
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertSet('showModal', false)
        ->assertSee('User account created')
        ->assertSee('casey@example.com');

    $user = User::query()->where('email', 'casey@example.com')->firstOrFail();
    $distributor = Distributor::query()->where('user_id', $user->id)->firstOrFail();
    $passwordMatch = [];
    preg_match('/<code[^>]*>([^<]+)<\/code>/', $component->html(), $passwordMatch);
    $temporaryPassword = $passwordMatch[1] ?? null;

    expect($user->role)->toBe('distributor')
        ->and($distributor->company_name)->toBe('Casey Supply Co.')
        ->and($distributor->account_number)->toBe('CASEY-100')
        ->and((float) $distributor->monthly_target)->toBe(1250.5)
        ->and($temporaryPassword)->toBeString()->toHaveLength(10)
        ->and(Hash::check($temporaryPassword, $user->password))->toBeTrue();

    $component->assertSee($temporaryPassword);
});

it('creates logistics and bank accounts without distributor profiles', function (): void {
    $manager = User::factory()->create(['role' => 'manager']);

    foreach (['logistics', 'bank'] as $role) {
        Livewire::actingAs($manager)
            ->test(CreateUserModal::class)
            ->set('name', ucfirst($role).' User')
            ->set('email', $role.'@example.com')
            ->set('role', $role)
            ->call('createUser')
            ->assertHasNoErrors()
            ->assertSee('User account created');

        $user = User::query()->where('email', $role.'@example.com')->firstOrFail();

        expect($user->role)->toBe($role)
            ->and($user->distributor()->exists())->toBeFalse();
    }
});

it('requires all distributor profile fields before creating the account', function (): void {
    $manager = User::factory()->create(['role' => 'manager']);

    Livewire::actingAs($manager)
        ->test(CreateUserModal::class)
        ->set('name', 'Incomplete Distributor')
        ->set('email', 'incomplete@example.com')
        ->set('role', 'distributor')
        ->call('createUser')
        ->assertHasErrors([
            'company_name' => 'required',
            'account_number' => 'required',
            'monthly_target' => 'required',
        ]);

    expect(User::query()->where('email', 'incomplete@example.com')->exists())->toBeFalse();
});

it('rejects duplicate emails, duplicate distributor account numbers, and unsupported roles', function (): void {
    $manager = User::factory()->create(['role' => 'manager']);
    $existingUser = User::factory()->create(['email' => 'existing@example.com', 'role' => 'distributor']);
    Distributor::query()->create([
        'user_id' => $existingUser->id,
        'company_name' => 'Existing Supply Co.',
        'account_number' => 'EXISTING-1',
        'monthly_target' => 50,
    ]);

    Livewire::actingAs($manager)
        ->test(CreateUserModal::class)
        ->set('name', 'Duplicate Email')
        ->set('email', 'existing@example.com')
        ->set('role', 'bank')
        ->call('createUser')
        ->assertHasErrors(['email' => 'unique']);

    Livewire::actingAs($manager)
        ->test(CreateUserModal::class)
        ->set('name', 'Duplicate Account')
        ->set('email', 'new@example.com')
        ->set('role', 'distributor')
        ->set('company_name', 'New Supply Co.')
        ->set('account_number', 'EXISTING-1')
        ->set('monthly_target', '50')
        ->call('createUser')
        ->assertHasErrors(['account_number' => 'unique']);

    Livewire::actingAs($manager)
        ->test(CreateUserModal::class)
        ->set('name', 'Unsupported Role')
        ->set('email', 'unsupported@example.com')
        ->set('role', 'admin')
        ->call('createUser')
        ->assertHasErrors(['role' => 'in']);

    expect(User::query()->where('email', 'new@example.com')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'unsupported@example.com')->exists())->toBeFalse();
});

it('denies guests and non-manager roles access to the user creation screen', function (): void {
    $this->get(route('manager.users.create'))
        ->assertRedirect(route('login'));

    $distributor = User::factory()->create(['role' => 'distributor']);

    $this->actingAs($distributor)
        ->get(route('manager.users.create'))
        ->assertForbidden();

    Livewire::actingAs($distributor)
        ->test(CreateUserModal::class)
        ->assertForbidden();
});

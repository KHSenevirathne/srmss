<?php

use App\Livewire\UserManager;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Phase 1 : user management. Only admins (manage-users) may reach it; an admin
 * can create a user and assign a role, email is unique, and you cannot delete
 * your own account. RefreshDatabase is applied globally in tests/Pest.php.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// --- Access control ---------------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get(route('users'))->assertRedirect(route('login'));
});

test('an admin can view the users page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('users'))
        ->assertOk()
        ->assertSee('Users');
});

test('a supervisor cannot reach user management', function () {
    $supervisor = User::factory()->create()->assignRole('supervisor');

    $this->actingAs($supervisor)
        ->get(route('users'))
        ->assertForbidden();
});

test('an operator cannot reach user management', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('users'))
        ->assertForbidden();
});

// --- CRUD + role assignment -------------------------------------------------

test('an admin can create a user and assign a role', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(UserManager::class)
        ->call('create')
        ->set('name', 'New Supervisor')
        ->set('email', 'new.supervisor@srmss.test')
        ->set('password', 'password')
        ->set('role', 'supervisor')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $user = User::where('email', 'new.supervisor@srmss.test')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('supervisor'))->toBeTrue();
});

test('a duplicate email is rejected', function () {
    $admin = User::factory()->create()->assignRole('admin');
    User::factory()->create(['email' => 'taken@srmss.test']);

    Livewire::actingAs($admin)
        ->test(UserManager::class)
        ->call('create')
        ->set('name', 'Someone')
        ->set('email', 'taken@srmss.test')
        ->set('password', 'password')
        ->set('role', 'operator')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('password is required when creating a user', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(UserManager::class)
        ->call('create')
        ->set('name', 'No Password')
        ->set('email', 'nopass@srmss.test')
        ->set('password', '')
        ->set('role', 'operator')
        ->call('save')
        ->assertHasErrors(['password']);
});

test('editing a role reassigns it', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $target = User::factory()->create()->assignRole('operator');

    Livewire::actingAs($admin)
        ->test(UserManager::class)
        ->call('edit', $target->id)
        ->set('role', 'supervisor')
        ->call('save')
        ->assertHasNoErrors();

    expect($target->fresh()->hasRole('supervisor'))->toBeTrue();
    expect($target->fresh()->hasRole('operator'))->toBeFalse();
});

test('an admin cannot delete their own account', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(UserManager::class)
        ->call('delete', $admin->id);

    expect(User::find($admin->id))->not->toBeNull();
});

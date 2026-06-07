<?php

use App\Livewire\DriverManager;
use App\Models\Driver;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Phase 2 — Drivers module. Mirrors VehiclesPageTest for access control and
 * adds CRUD + validation coverage via Livewire::test, per the module recipe
 * (docs/ARCHITECTURE.md §6: access control + create/edit/delete/validation).
 */

beforeEach(function () {
    // RefreshDatabase is applied globally in tests/Pest.php.
    $this->seed(RolesAndPermissionsSeeder::class);
});

// --- Access control ---------------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get(route('drivers'))->assertRedirect(route('login'));
});

test('a user with manage-fleet can view the drivers page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('drivers'))
        ->assertOk()
        ->assertSee('Drivers');
});

test('a user without manage-fleet is forbidden', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('drivers'))
        ->assertForbidden();
});

// --- CRUD + validation ------------------------------------------------------

test('a driver can be created', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', 'Sunil Perera')
        ->set('license_number', 'DL-100')
        ->set('license_expiry', now()->addYear()->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(Driver::where('license_number', 'DL-100')->exists())->toBeTrue();
});

test('a driver can be edited', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $driver = Driver::create([
        'name' => 'Old Name', 'license_number' => 'DL-200',
        'license_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('edit', $driver->id)
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($driver->fresh()->name)->toBe('New Name');
});

test('a driver can be deleted', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $driver = Driver::create([
        'name' => 'Delete Me', 'license_number' => 'DL-300',
        'license_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('delete', $driver->id);

    expect(Driver::find($driver->id))->toBeNull();
});

test('required fields are validated', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', '')
        ->set('license_number', '')
        ->set('license_expiry', '')
        ->call('save')
        ->assertHasErrors(['name', 'license_number', 'license_expiry']);
});

test('a duplicate licence number is rejected', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Driver::create([
        'name' => 'First', 'license_number' => 'DL-DUP',
        'license_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', 'Second')
        ->set('license_number', 'DL-DUP')
        ->set('license_expiry', now()->addYear()->toDateString())
        ->call('save')
        ->assertHasErrors(['license_number']);

    expect(Driver::where('name', 'Second')->exists())->toBeFalse();
});

test('drivers can be filtered by status', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Driver::create(['name' => 'Active Ann', 'license_number' => 'DL-A', 'license_expiry' => now()->addYear(), 'status' => 'active']);
    Driver::create(['name' => 'Inactive Ivan', 'license_number' => 'DL-I', 'license_expiry' => now()->addYear(), 'status' => 'inactive']);

    $drivers = Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->set('statusFilter', 'inactive')
        ->viewData('drivers');

    expect($drivers->total())->toBe(1);
    expect($drivers->first()->name)->toBe('Inactive Ivan');
});

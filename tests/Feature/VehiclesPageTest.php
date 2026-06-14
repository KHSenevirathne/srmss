<?php

use App\Livewire\VehicleManager;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Phase 1 — proves the role-aware guard on the Vehicles screen:
 *   - guests are bounced to login,
 *   - a user WITH manage-fleet (admin) can open the page (also confirms the
 *     full-page Livewire component renders inside the app layout),
 *   - a user WITHOUT it (operator) is blocked with 403.
 */

beforeEach(function () {
    // Build the roles + permissions in the fresh test database.
    // RefreshDatabase is applied globally in tests/Pest.php.
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected to the login page', function () {
    $this->get(route('vehicles'))->assertRedirect(route('login'));
});

test('a user with manage-fleet can view the vehicles page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('vehicles'))
        ->assertOk()
        ->assertSee('Vehicles');
});

test('a user without manage-fleet is forbidden', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('vehicles'))
        ->assertForbidden();
});

test('brand, model and fuel type are saved (brand/model optional)', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(VehicleManager::class)
        ->call('create')
        ->set('registration_number', 'BM-1')
        ->set('brand', 'Tata')
        ->set('model', 'Starbus')
        ->set('fuel_type', 'electric')
        ->set('seating_capacity', 40)
        ->set('mileage', 1000)
        ->call('save')
        ->assertHasNoErrors();

    $vehicle = Vehicle::where('registration_number', 'BM-1')->first();
    expect($vehicle->brand)->toBe('Tata');
    expect($vehicle->model)->toBe('Starbus');
    expect($vehicle->fuel_type)->toBe('electric');

    // Leaving brand/model blank is allowed and stored as null.
    Livewire::actingAs($admin)
        ->test(VehicleManager::class)
        ->call('create')
        ->set('registration_number', 'BM-2')
        ->set('seating_capacity', 30)
        ->set('mileage', 0)
        ->call('save')
        ->assertHasNoErrors();

    $bare = Vehicle::where('registration_number', 'BM-2')->first();
    expect($bare->brand)->toBeNull();
    expect($bare->model)->toBeNull();
    expect($bare->fuel_type)->toBe('diesel'); // dropdown default
});

test('an invalid fuel type is rejected', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(VehicleManager::class)
        ->call('create')
        ->set('registration_number', 'BAD-1')
        ->set('fuel_type', 'plutonium')
        ->set('seating_capacity', 40)
        ->set('mileage', 0)
        ->call('save')
        ->assertHasErrors(['fuel_type']);

    expect(Vehicle::where('registration_number', 'BAD-1')->exists())->toBeFalse();
});

test('vehicles can be filtered by status', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Vehicle::create(['registration_number' => 'AV-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0, 'status' => 'available']);
    Vehicle::create(['registration_number' => 'MT-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0, 'status' => 'maintenance']);

    $vehicles = Livewire::actingAs($admin)
        ->test(VehicleManager::class)
        ->set('statusFilter', 'maintenance')
        ->viewData('vehicles');

    expect($vehicles->total())->toBe(1);
    expect($vehicles->first()->registration_number)->toBe('MT-1');
});

<?php

use App\Livewire\TripManager;
use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Central trips board. Viewing is gated by view-trips (admin, supervisor, operator);
 * updating a trip's status additionally requires manage-schedules.
 * RefreshDatabase is applied globally in tests/Pest.php.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $route = BusRoute::create(['code' => 'R-1', 'name' => 'One', 'start_point' => 'A', 'end_point' => 'B']);
    $vehicle = Vehicle::create(['registration_number' => 'V-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);
    $driver = Driver::create(['name' => 'D1', 'license_number' => 'DL-1', 'license_expiry' => now()->addYear()]);
    $this->schedule = Schedule::create([
        'bus_route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'frequency' => 'daily', 'departure_time' => '08:00', 'arrival_time' => '10:00',
        'valid_from' => now()->toDateString(), 'status' => 'active',
    ]);
    $this->trip = $this->schedule->trips()->create(['trip_date' => now()->toDateString(), 'status' => 'scheduled']);
});

// --- Access control ---------------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get(route('trips'))->assertRedirect(route('login'));
});

test('an operator can view the trips board (read-only)', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('trips'))
        ->assertOk()
        ->assertSee('Trips');
});

test('a supervisor can view the trips board', function () {
    $supervisor = User::factory()->create()->assignRole('supervisor');

    $this->actingAs($supervisor)->get(route('trips'))->assertOk();
});

test('a user without view-trips is forbidden', function () {
    $nobody = User::factory()->create();

    $this->actingAs($nobody)->get(route('trips'))->assertForbidden();
});

// --- Status updates ---------------------------------------------------------

test('a supervisor can update a trip status from the board', function () {
    $supervisor = User::factory()->create()->assignRole('supervisor');

    Livewire::actingAs($supervisor)
        ->test(TripManager::class)
        ->call('updateTripStatus', $this->trip->id, 'delayed');

    expect($this->trip->fresh()->status)->toBe('delayed');
});

test('an operator cannot update a trip status (read-only)', function () {
    $operator = User::factory()->create()->assignRole('operator');

    Livewire::actingAs($operator)
        ->test(TripManager::class)
        ->call('updateTripStatus', $this->trip->id, 'delayed')
        ->assertStatus(403);

    expect($this->trip->fresh()->status)->toBe('scheduled'); // unchanged
});

// --- Filtering --------------------------------------------------------------

test('trips can be filtered by status', function () {
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $this->schedule->trips()->create(['trip_date' => now()->addDay()->toDateString(), 'status' => 'delayed']);

    $trips = Livewire::actingAs($supervisor)
        ->test(TripManager::class)
        ->set('filterStatus', 'delayed')
        ->viewData('trips');

    expect($trips->total())->toBe(1);
    expect($trips->first()->status)->toBe('delayed');
});

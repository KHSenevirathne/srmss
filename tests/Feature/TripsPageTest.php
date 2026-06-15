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
    $this->driver = Driver::create(['name' => 'D1', 'license_number' => 'DL-1', 'license_expiry' => now()->addYear()]);
    $this->schedule = Schedule::create([
        'bus_route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $this->driver->id,
        'frequency' => 'daily', 'departure_time' => '08:00', 'arrival_time' => '10:00',
        'valid_from' => now()->toDateString(), 'status' => 'active',
    ]);
    $this->trip = $this->schedule->trips()->create(['trip_date' => now()->toDateString(), 'status' => 'scheduled']);
});

/** Create a login account for the given driver record and return the user. */
function driverLoginFor(Driver $driver): User
{
    $user = User::factory()->create(['email' => null]);
    $user->assignRole('driver');
    $driver->update(['user_id' => $user->id]);

    return $user;
}

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

// --- Cancelled status (staff-only) ------------------------------------------

test('a supervisor can cancel a trip', function () {
    $supervisor = User::factory()->create()->assignRole('supervisor');

    Livewire::actingAs($supervisor)
        ->test(TripManager::class)
        ->call('updateTripStatus', $this->trip->id, 'cancelled');

    expect($this->trip->fresh()->status)->toBe('cancelled');
});

// --- Driver : scoped view + request workflow --------------------------------

test('a driver can reach the board and sees only their own trips', function () {
    $driverUser = driverLoginFor($this->driver);

    // A second driver's trip that must not be visible to the first.
    $other = Driver::create(['name' => 'D2', 'license_number' => 'DL-2', 'license_expiry' => now()->addYear()]);
    $otherSchedule = Schedule::create([
        'bus_route_id' => $this->schedule->bus_route_id, 'vehicle_id' => $this->schedule->vehicle_id,
        'driver_id' => $other->id, 'frequency' => 'daily', 'departure_time' => '09:00', 'arrival_time' => '11:00',
        'valid_from' => now()->toDateString(), 'status' => 'active',
    ]);
    $otherSchedule->trips()->create(['trip_date' => now()->toDateString(), 'status' => 'scheduled']);

    $this->actingAs($driverUser)->get(route('trips'))->assertOk()->assertSee('My Trips');

    $trips = Livewire::actingAs($driverUser)->test(TripManager::class)->viewData('trips');

    expect($trips->total())->toBe(1);
    expect($trips->first()->id)->toBe($this->trip->id);
});

test('a driver requests a status change which is parked as pending', function () {
    $driverUser = driverLoginFor($this->driver);

    Livewire::actingAs($driverUser)
        ->test(TripManager::class)
        ->call('requestTripStatus', $this->trip->id, 'completed')
        ->assertHasNoErrors();

    $fresh = $this->trip->fresh();
    expect($fresh->status)->toBe('scheduled');            // live status unchanged
    expect($fresh->pending_status)->toBe('completed');    // request is pending
    expect($fresh->status_requested_by)->toBe($driverUser->id);
});

test('a driver cannot request the cancelled status', function () {
    $driverUser = driverLoginFor($this->driver);

    Livewire::actingAs($driverUser)
        ->test(TripManager::class)
        ->call('requestTripStatus', $this->trip->id, 'cancelled')
        ->assertStatus(422);

    expect($this->trip->fresh()->pending_status)->toBeNull();
});

test('a driver cannot request a status on another driver\'s trip', function () {
    $driverUser = driverLoginFor($this->driver);

    $other = Driver::create(['name' => 'D2', 'license_number' => 'DL-2', 'license_expiry' => now()->addYear()]);
    $otherSchedule = Schedule::create([
        'bus_route_id' => $this->schedule->bus_route_id, 'vehicle_id' => $this->schedule->vehicle_id,
        'driver_id' => $other->id, 'frequency' => 'daily', 'departure_time' => '09:00', 'arrival_time' => '11:00',
        'valid_from' => now()->toDateString(), 'status' => 'active',
    ]);
    $otherTrip = $otherSchedule->trips()->create(['trip_date' => now()->toDateString(), 'status' => 'scheduled']);

    Livewire::actingAs($driverUser)
        ->test(TripManager::class)
        ->call('requestTripStatus', $otherTrip->id, 'completed')
        ->assertStatus(403);
});

test('a driver cannot set a status directly', function () {
    $driverUser = driverLoginFor($this->driver);

    Livewire::actingAs($driverUser)
        ->test(TripManager::class)
        ->call('updateTripStatus', $this->trip->id, 'completed')
        ->assertStatus(403);

    expect($this->trip->fresh()->status)->toBe('scheduled');
});

// --- Approval ---------------------------------------------------------------

test('an operator can approve a pending request', function () {
    $driverUser = driverLoginFor($this->driver);
    $operator = User::factory()->create()->assignRole('operator');

    $this->trip->update([
        'pending_status' => 'completed',
        'status_requested_by' => $driverUser->id,
        'status_requested_at' => now(),
    ]);

    Livewire::actingAs($operator)
        ->test(TripManager::class)
        ->call('approveStatus', $this->trip->id);

    $fresh = $this->trip->fresh();
    expect($fresh->status)->toBe('completed');
    expect($fresh->pending_status)->toBeNull();
});

test('an operator can reject a pending request, leaving the status unchanged', function () {
    $driverUser = driverLoginFor($this->driver);
    $operator = User::factory()->create()->assignRole('operator');

    $this->trip->update([
        'pending_status' => 'completed',
        'status_requested_by' => $driverUser->id,
        'status_requested_at' => now(),
    ]);

    Livewire::actingAs($operator)
        ->test(TripManager::class)
        ->call('rejectStatus', $this->trip->id);

    $fresh = $this->trip->fresh();
    expect($fresh->status)->toBe('scheduled');
    expect($fresh->pending_status)->toBeNull();
});

test('a plain user cannot approve a pending request', function () {
    $nobody = User::factory()->create();
    $this->trip->update(['pending_status' => 'completed', 'status_requested_at' => now()]);

    Livewire::actingAs($nobody)
        ->test(TripManager::class)
        ->call('approveStatus', $this->trip->id)
        ->assertStatus(403);
});

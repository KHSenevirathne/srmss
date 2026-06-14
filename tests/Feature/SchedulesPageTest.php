<?php

use App\Livewire\ScheduleManager;
use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Phase 4 — schedule management + conflict detection (DB-backed). The pure
 * overlap maths is unit-tested in tests/Unit/ScheduleConflictServiceTest.php.
 * RefreshDatabase is applied globally in tests/Pest.php.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->route = BusRoute::create(['code' => 'R-1', 'name' => 'One', 'start_point' => 'A', 'end_point' => 'B']);
    $this->vehicle = Vehicle::create(['registration_number' => 'V-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);
    $this->driver = Driver::create(['name' => 'Driver One', 'license_number' => 'DL-1', 'license_expiry' => now()->addYear()]);
});

function makeSchedule(array $overrides = []): Schedule
{
    return Schedule::create(array_merge([
        'bus_route_id'   => test()->route->id,
        'vehicle_id'     => test()->vehicle->id,
        'driver_id'      => test()->driver->id,
        'frequency'      => 'daily',
        'departure_time' => '08:00',
        'arrival_time'   => '10:00',
        'valid_from'     => '2026-01-01',
        'valid_to'       => '2026-12-31',
        'status'         => 'active',
    ], $overrides));
}

/** Form payload for a Livewire create(); merge to vary fields. */
function schedulePayload(array $overrides = []): array
{
    return array_merge([
        'bus_route_id'   => (string) test()->route->id,
        'vehicle_id'     => (string) test()->vehicle->id,
        'driver_id'      => (string) test()->driver->id,
        'frequency'      => 'daily',
        'departure_time' => '08:00',
        'arrival_time'   => '10:00',
        'valid_from'     => '2026-01-01',
        'valid_to'       => '2026-12-31',
        'status'         => 'active',
    ], $overrides);
}

// --- Access control ---------------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get(route('schedules'))->assertRedirect(route('login'));
});

test('a supervisor (manage-schedules) can view the schedules page', function () {
    $supervisor = User::factory()->create()->assignRole('supervisor');

    $this->actingAs($supervisor)
        ->get(route('schedules'))
        ->assertOk()
        ->assertSee('Schedules');
});

test('an operator cannot reach schedules', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('schedules'))
        ->assertForbidden();
});

// --- Create + validation ----------------------------------------------------

test('a valid schedule can be created', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $component = Livewire::actingAs($admin)->test(ScheduleManager::class)->call('create');
    foreach (schedulePayload() as $field => $value) {
        $component->set($field, $value);
    }
    $component->call('save')->assertHasNoErrors()->assertSet('showModal', false);

    expect(Schedule::count())->toBe(1);
});

test('arrival must be after departure', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $component = Livewire::actingAs($admin)->test(ScheduleManager::class)->call('create');
    foreach (schedulePayload(['departure_time' => '10:00', 'arrival_time' => '09:00']) as $field => $value) {
        $component->set($field, $value);
    }
    $component->call('save')->assertHasErrors(['arrival_time']);

    expect(Schedule::count())->toBe(0);
});

test('a vehicle that is not available cannot be assigned', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $this->vehicle->update(['status' => 'maintenance']);

    $component = Livewire::actingAs($admin)->test(ScheduleManager::class)->call('create');
    foreach (schedulePayload() as $field => $value) {
        $component->set($field, $value);
    }
    $component->call('save')->assertHasErrors(['vehicle_id']);

    expect(Schedule::count())->toBe(0);
});

test('an inactive driver cannot be assigned', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $inactive = Driver::create([
        'name' => 'Inactive Ivan', 'license_number' => 'DL-INA',
        'license_expiry' => now()->addYear(), 'status' => 'inactive',
    ]);

    $component = Livewire::actingAs($admin)->test(ScheduleManager::class)->call('create');
    foreach (schedulePayload(['driver_id' => (string) $inactive->id]) as $field => $value) {
        $component->set($field, $value);
    }
    $component->call('save')->assertHasErrors(['driver_id']);

    expect(Schedule::count())->toBe(0);
});

test('editing a schedule keeps its vehicle even if it later went unavailable', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $schedule = makeSchedule();
    $this->vehicle->update(['status' => 'maintenance']);

    // Editing the times must not be trapped by the now-unavailable vehicle.
    Livewire::actingAs($admin)
        ->test(ScheduleManager::class)
        ->call('edit', $schedule->id)
        ->set('arrival_time', '10:30')
        ->call('save')
        ->assertHasNoErrors();

    expect($schedule->fresh()->arrival_time)->toContain('10:30');
});

// --- Conflict detection -----------------------------------------------------

test('the same vehicle on an overlapping window is blocked', function () {
    $admin = User::factory()->create()->assignRole('admin');
    makeSchedule(); // 08:00-10:00 daily, all of 2026

    $other = Driver::create(['name' => 'Driver Two', 'license_number' => 'DL-2', 'license_expiry' => now()->addYear()]);

    $component = Livewire::actingAs($admin)->test(ScheduleManager::class)->call('create');
    foreach (schedulePayload(['driver_id' => (string) $other->id, 'departure_time' => '09:00', 'arrival_time' => '11:00']) as $field => $value) {
        $component->set($field, $value);
    }
    $component->call('save')->assertHasErrors(['conflict']);

    expect(Schedule::count())->toBe(1); // the new one was rejected
});

test('the same driver on an overlapping window is blocked', function () {
    $admin = User::factory()->create()->assignRole('admin');
    makeSchedule();

    $otherVehicle = Vehicle::create(['registration_number' => 'V-2', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);

    $component = Livewire::actingAs($admin)->test(ScheduleManager::class)->call('create');
    foreach (schedulePayload(['vehicle_id' => (string) $otherVehicle->id, 'departure_time' => '09:30', 'arrival_time' => '11:30']) as $field => $value) {
        $component->set($field, $value);
    }
    $component->call('save')->assertHasErrors(['conflict']);

    expect(Schedule::count())->toBe(1);
});

test('a non-overlapping time window for the same vehicle is allowed', function () {
    $admin = User::factory()->create()->assignRole('admin');
    makeSchedule(); // 08:00-10:00

    $component = Livewire::actingAs($admin)->test(ScheduleManager::class)->call('create');
    foreach (schedulePayload(['departure_time' => '10:00', 'arrival_time' => '12:00']) as $field => $value) {
        $component->set($field, $value);
    }
    $component->call('save')->assertHasNoErrors();

    expect(Schedule::count())->toBe(2);
});

test('a cancelled schedule does not cause a conflict', function () {
    $admin = User::factory()->create()->assignRole('admin');
    makeSchedule(['status' => 'cancelled']);

    $component = Livewire::actingAs($admin)->test(ScheduleManager::class)->call('create');
    foreach (schedulePayload() as $field => $value) {
        $component->set($field, $value);
    }
    $component->call('save')->assertHasNoErrors();

    expect(Schedule::where('status', 'active')->count())->toBe(1);
});

test('editing a schedule does not conflict with itself', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $schedule = makeSchedule();

    Livewire::actingAs($admin)
        ->test(ScheduleManager::class)
        ->call('edit', $schedule->id)
        ->set('arrival_time', '10:30') // still overlaps its own old window
        ->call('save')
        ->assertHasNoErrors();

    expect($schedule->fresh()->arrival_time)->toContain('10:30');
});

// --- Cancel + trip generation ----------------------------------------------

test('a schedule can be cancelled', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $schedule = makeSchedule();

    Livewire::actingAs($admin)
        ->test(ScheduleManager::class)
        ->call('cancel', $schedule->id);

    expect($schedule->fresh()->status)->toBe('cancelled');
});

test('generating trips creates rows and is idempotent', function () {
    $admin = User::factory()->create()->assignRole('admin');
    // A weekly schedule over a 3-week window => 3 trips.
    $schedule = makeSchedule([
        'frequency'  => 'weekly',
        'valid_from' => today()->toDateString(),
        'valid_to'   => today()->addWeeks(2)->toDateString(),
    ]);

    Livewire::actingAs($admin)->test(ScheduleManager::class)->call('generateTrips', $schedule->id);
    expect($schedule->trips()->count())->toBe(3);

    // Running again must not duplicate.
    Livewire::actingAs($admin)->test(ScheduleManager::class)->call('generateTrips', $schedule->id);
    expect($schedule->trips()->count())->toBe(3);
});

test('a trip status can be updated from the trips panel', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $schedule = makeSchedule();
    $trip = $schedule->trips()->create(['trip_date' => today()->toDateString(), 'status' => 'scheduled']);

    Livewire::actingAs($admin)
        ->test(ScheduleManager::class)
        ->call('viewTrips', $schedule->id)
        ->call('updateTripStatus', $trip->id, 'delayed');

    expect($trip->fresh()->status)->toBe('delayed');
});

test('a trip of another schedule cannot be updated through the panel', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $mine = makeSchedule();
    $other = makeSchedule(['departure_time' => '12:00', 'arrival_time' => '14:00']);
    $foreignTrip = $other->trips()->create(['trip_date' => today()->toDateString(), 'status' => 'scheduled']);

    // The scoped lookup throws (404) before any update happens.
    Livewire::actingAs($admin)
        ->test(ScheduleManager::class)
        ->call('viewTrips', $mine->id)
        ->call('updateTripStatus', $foreignTrip->id, 'completed');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('deleting a schedule cascades its trips', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $schedule = makeSchedule();
    $schedule->trips()->create(['trip_date' => today()->toDateString(), 'status' => 'scheduled']);

    Livewire::actingAs($admin)->test(ScheduleManager::class)->call('delete', $schedule->id);

    expect(Schedule::find($schedule->id))->toBeNull();
    expect(\App\Models\Trip::where('schedule_id', $schedule->id)->exists())->toBeFalse();
});

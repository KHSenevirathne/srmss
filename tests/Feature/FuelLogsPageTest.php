<?php

use App\Livewire\FuelLogManager;
use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Phase 3 : Fuel logs. Access control (log-fuel : held by every role), record a
 * fuel entry, vehicle + date-range filtering, and validation.
 * RefreshDatabase is applied globally in tests/Pest.php.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->vehicle = Vehicle::create([
        'registration_number' => 'NA-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 1000,
    ]);
});

// --- Access control ---------------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get(route('fuel-logs'))->assertRedirect(route('login'));
});

test('an operator (log-fuel) can view the fuel logs page', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('fuel-logs'))
        ->assertOk()
        ->assertSee('Fuel Logs');
});

test('a user without log-fuel is forbidden', function () {
    $nobody = User::factory()->create(); // no role => no permissions

    $this->actingAs($nobody)
        ->get(route('fuel-logs'))
        ->assertForbidden();
});

// --- CRUD + validation ------------------------------------------------------

test('a fuel log can be recorded', function () {
    $operator = User::factory()->create()->assignRole('operator');

    Livewire::actingAs($operator)
        ->test(FuelLogManager::class)
        ->call('create')
        ->set('vehicle_id', (string) $this->vehicle->id)
        ->set('liters', 120.5)
        ->set('cost', 45000)
        ->set('odometer', 182500)
        ->set('logged_at', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(FuelLog::where('vehicle_id', $this->vehicle->id)->count())->toBe(1);
});

test('required fields are validated', function () {
    $operator = User::factory()->create()->assignRole('operator');

    Livewire::actingAs($operator)
        ->test(FuelLogManager::class)
        ->call('create')
        ->set('vehicle_id', '')
        ->set('logged_at', '')
        ->call('save')
        ->assertHasErrors(['vehicle_id', 'logged_at']);
});

test('logs can be filtered by vehicle', function () {
    $operator = User::factory()->create()->assignRole('operator');
    $other = Vehicle::create([
        'registration_number' => 'NB-2', 'type' => 'coach', 'seating_capacity' => 49, 'mileage' => 0,
    ]);
    $mine = FuelLog::create(['vehicle_id' => $this->vehicle->id, 'liters' => 10, 'cost' => 100, 'logged_at' => now()]);
    FuelLog::create(['vehicle_id' => $other->id, 'liters' => 20, 'cost' => 200, 'logged_at' => now()]);

    // NB-2 still appears in the filter dropdown, so assert on the filtered data
    // (the table rows) rather than the whole page HTML.
    $logs = Livewire::actingAs($operator)
        ->test(FuelLogManager::class)
        ->set('filterVehicleId', (string) $this->vehicle->id)
        ->viewData('logs');

    expect($logs->total())->toBe(1);
    expect($logs->first()->id)->toBe($mine->id);
});

test('logs can be filtered by date range', function () {
    $operator = User::factory()->create()->assignRole('operator');
    FuelLog::create(['vehicle_id' => $this->vehicle->id, 'liters' => 10, 'cost' => 100, 'logged_at' => '2026-01-10']);
    $inRange = FuelLog::create(['vehicle_id' => $this->vehicle->id, 'liters' => 20, 'cost' => 200, 'logged_at' => '2026-02-15']);
    FuelLog::create(['vehicle_id' => $this->vehicle->id, 'liters' => 30, 'cost' => 300, 'logged_at' => '2026-03-10']);

    $component = Livewire::actingAs($operator)
        ->test(FuelLogManager::class)
        ->set('dateFrom', '2026-02-01')
        ->set('dateTo', '2026-02-28');

    // Only the mid-February entry falls inside the window.
    $logs = $component->viewData('logs');
    expect($logs->total())->toBe(1);
    expect($logs->first()->id)->toBe($inRange->id);
});

<?php

use App\Livewire\MaintenanceLogManager;
use App\Models\MaintenanceLog;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Phase 3 : Maintenance logs. Access control (log-fuel), record a service entry,
 * the "service due" flag (next_due_at reached/passed), and validation.
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
    $this->get(route('maintenance-logs'))->assertRedirect(route('login'));
});

test('an operator (log-fuel) can view the maintenance logs page', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('maintenance-logs'))
        ->assertOk()
        ->assertSee('Maintenance Logs');
});

test('a user without log-fuel is forbidden', function () {
    $nobody = User::factory()->create();

    $this->actingAs($nobody)
        ->get(route('maintenance-logs'))
        ->assertForbidden();
});

// --- CRUD + validation ------------------------------------------------------

test('a maintenance log can be recorded', function () {
    $operator = User::factory()->create()->assignRole('operator');

    Livewire::actingAs($operator)
        ->test(MaintenanceLogManager::class)
        ->call('create')
        ->set('vehicle_id', (string) $this->vehicle->id)
        ->set('type', 'routine')
        ->set('description', 'Oil change')
        ->set('cost', 8000)
        ->set('serviced_at', now()->toDateString())
        ->set('next_due_at', now()->addMonths(3)->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(MaintenanceLog::where('vehicle_id', $this->vehicle->id)->count())->toBe(1);
});

test('next due cannot be before the service date', function () {
    $operator = User::factory()->create()->assignRole('operator');

    Livewire::actingAs($operator)
        ->test(MaintenanceLogManager::class)
        ->call('create')
        ->set('vehicle_id', (string) $this->vehicle->id)
        ->set('description', 'Brake check')
        ->set('serviced_at', '2026-06-01')
        ->set('next_due_at', '2026-05-01')
        ->call('save')
        ->assertHasErrors(['next_due_at']);
});

test('required fields are validated', function () {
    $operator = User::factory()->create()->assignRole('operator');

    Livewire::actingAs($operator)
        ->test(MaintenanceLogManager::class)
        ->call('create')
        ->set('vehicle_id', '')
        ->set('description', '')
        ->set('serviced_at', '')
        ->call('save')
        ->assertHasErrors(['vehicle_id', 'description', 'serviced_at']);
});

// --- Service-due flag -------------------------------------------------------

test('serviceDue is true when next_due_at has passed', function () {
    $log = MaintenanceLog::create([
        'vehicle_id' => $this->vehicle->id, 'type' => 'routine', 'description' => 'Past due',
        'cost' => 0, 'serviced_at' => now()->subMonths(4), 'next_due_at' => now()->subDay(),
    ]);

    expect($log->serviceDue())->toBeTrue();
});

test('serviceDue is false when next_due_at is in the future or unset', function () {
    $future = MaintenanceLog::create([
        'vehicle_id' => $this->vehicle->id, 'type' => 'routine', 'description' => 'Future',
        'cost' => 0, 'serviced_at' => now(), 'next_due_at' => now()->addMonth(),
    ]);
    $none = MaintenanceLog::create([
        'vehicle_id' => $this->vehicle->id, 'type' => 'corrective', 'description' => 'No due date',
        'cost' => 0, 'serviced_at' => now(), 'next_due_at' => null,
    ]);

    expect($future->serviceDue())->toBeFalse();
    expect($none->serviceDue())->toBeFalse();
});

test('the service due badge shows for an overdue vehicle', function () {
    $operator = User::factory()->create()->assignRole('operator');
    MaintenanceLog::create([
        'vehicle_id' => $this->vehicle->id, 'type' => 'routine', 'description' => 'Overdue service',
        'cost' => 0, 'serviced_at' => now()->subMonths(4), 'next_due_at' => now()->subDay(),
    ]);

    Livewire::actingAs($operator)
        ->test(MaintenanceLogManager::class)
        ->assertSee('service due');
});

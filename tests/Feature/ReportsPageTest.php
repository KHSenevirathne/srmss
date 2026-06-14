<?php

use App\Livewire\Reports;
use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Phase 5 : Reports screen + PDF export. Access is gated by view-reports (held by
 * every role). RefreshDatabase is applied globally in tests/Pest.php.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// --- Access control ---------------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get(route('reports'))->assertRedirect(route('login'));
});

test('an operator (view-reports) can view the reports page', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('reports'))
        ->assertOk()
        ->assertSee('Reports');
});

test('a user without view-reports is forbidden', function () {
    $nobody = User::factory()->create();

    $this->actingAs($nobody)
        ->get(route('reports'))
        ->assertForbidden();
});

// --- On-screen reports ------------------------------------------------------

test('the reports screen computes data for the selected range', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $route = BusRoute::create(['code' => 'R-1', 'name' => 'One', 'start_point' => 'A', 'end_point' => 'B']);
    $vehicle = Vehicle::create(['registration_number' => 'V-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);
    $driver = Driver::create(['name' => 'D1', 'license_number' => 'DL-1', 'license_expiry' => now()->addYear()]);
    $schedule = Schedule::create([
        'bus_route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'frequency' => 'daily', 'departure_time' => '08:00', 'arrival_time' => '10:00',
        'valid_from' => '2026-01-01', 'status' => 'active',
    ]);
    $schedule->trips()->createMany([
        ['trip_date' => '2026-02-10', 'status' => 'completed'],
        ['trip_date' => '2026-02-11', 'status' => 'delayed'],
    ]);

    $data = Livewire::actingAs($admin)
        ->test(Reports::class)
        ->set('from', '2026-02-01')
        ->set('to', '2026-02-28')
        ->viewData('tripCompletion');

    expect($data['total'])->toBe(2);
    expect($data['completed'])->toBe(1);
});

// --- PDF export -------------------------------------------------------------

test('the report can be downloaded as a PDF', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $response = $this->actingAs($admin)
        ->get(route('reports.pdf', ['from' => '2026-02-01', 'to' => '2026-02-28']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('the PDF export is also gated by view-reports', function () {
    $nobody = User::factory()->create();

    $this->actingAs($nobody)
        ->get(route('reports.pdf', ['from' => '2026-02-01', 'to' => '2026-02-28']))
        ->assertForbidden();
});

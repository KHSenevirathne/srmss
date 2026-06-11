<?php

use App\Livewire\Dashboard;
use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\MaintenanceLog;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('summary cards reflect real data', function () {
    $user = User::factory()->create();

    $route = BusRoute::create(['code' => 'R-1', 'name' => 'One', 'start_point' => 'A', 'end_point' => 'B']);
    $vehicle = Vehicle::create(['registration_number' => 'V-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0, 'status' => 'available']);
    Vehicle::create(['registration_number' => 'V-2', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0, 'status' => 'maintenance']);
    $driver = Driver::create(['name' => 'D1', 'license_number' => 'DL-1', 'license_expiry' => now()->addYear()]);
    $schedule = Schedule::create([
        'bus_route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'frequency' => 'daily', 'departure_time' => '08:00', 'arrival_time' => '10:00',
        'valid_from' => today()->toDateString(), 'status' => 'active',
    ]);
    $schedule->trips()->create(['trip_date' => today()->toDateString(), 'status' => 'on_time']);

    $cards = Livewire::actingAs($user)->test(Dashboard::class)->viewData('cards');

    expect($cards['routes'])->toBe(1);
    expect($cards['activeTripsToday'])->toBe(1);
    expect($cards['availableBuses'])->toBe(1);   // V-2 is in maintenance
    expect($cards['assignedDrivers'])->toBe(1);
});

test('the trip board counts today\'s trips by status', function () {
    $user = User::factory()->create();
    $route = BusRoute::create(['code' => 'R-1', 'name' => 'One', 'start_point' => 'A', 'end_point' => 'B']);
    $vehicle = Vehicle::create(['registration_number' => 'V-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);
    $driver = Driver::create(['name' => 'D1', 'license_number' => 'DL-1', 'license_expiry' => now()->addYear()]);
    $schedule = Schedule::create([
        'bus_route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'frequency' => 'daily', 'departure_time' => '08:00', 'arrival_time' => '10:00',
        'valid_from' => today()->toDateString(), 'status' => 'active',
    ]);
    $schedule->trips()->createMany([
        ['trip_date' => today()->toDateString(), 'status' => 'on_time'],
        ['trip_date' => today()->toDateString(), 'status' => 'delayed'],
        ['trip_date' => today()->toDateString(), 'status' => 'on_time'],
    ]);

    $counts = Livewire::actingAs($user)->test(Dashboard::class)->viewData('tripCounts');

    expect($counts['on_time'])->toBe(2);
    expect($counts['delayed'])->toBe(1);
});

test('alerts list expiring licences and service-due vehicles', function () {
    $user = User::factory()->create();

    Driver::create(['name' => 'Expiring Soon', 'license_number' => 'DL-X', 'license_expiry' => now()->addDays(10)]);
    Driver::create(['name' => 'Fine', 'license_number' => 'DL-Y', 'license_expiry' => now()->addYears(2)]);

    $vehicle = Vehicle::create(['registration_number' => 'V-OD', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);
    MaintenanceLog::create([
        'vehicle_id' => $vehicle->id, 'type' => 'routine', 'description' => 'Overdue',
        'cost' => 0, 'serviced_at' => now()->subMonths(4), 'next_due_at' => now()->subDay(),
    ]);

    $component = Livewire::actingAs($user)->test(Dashboard::class);

    expect($component->viewData('expiringLicences')->pluck('name')->all())->toBe(['Expiring Soon']);
    expect($component->viewData('serviceDueVehicles')->pluck('registration_number')->all())->toBe(['V-OD']);
});

<?php

use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\FuelLog;
use App\Models\MaintenanceLog;
use App\Models\Schedule;
use App\Models\Vehicle;
use App\Services\ReportService;
use Illuminate\Support\Carbon;

/*
 * Phase 5 : ReportService aggregation tests. DB-backed (the figures come from
 * real rows); RefreshDatabase is applied globally in tests/Pest.php.
 */

beforeEach(function () {
    $this->service = new ReportService();
    $this->route = BusRoute::create(['code' => 'R-1', 'name' => 'One', 'start_point' => 'A', 'end_point' => 'B']);
    $this->vehicle = Vehicle::create(['registration_number' => 'V-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);
    $this->driver = Driver::create(['name' => 'D1', 'license_number' => 'DL-1', 'license_expiry' => now()->addYear()]);
    $this->schedule = Schedule::create([
        'bus_route_id' => $this->route->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id,
        'frequency' => 'daily', 'departure_time' => '08:00', 'arrival_time' => '10:00',
        'valid_from' => '2026-01-01', 'status' => 'active',
    ]);
});

test('trip completion summarises status counts and rate', function () {
    $this->schedule->trips()->createMany([
        ['trip_date' => '2026-02-10', 'status' => 'completed'],
        ['trip_date' => '2026-02-11', 'status' => 'completed'],
        ['trip_date' => '2026-02-12', 'status' => 'delayed'],
        ['trip_date' => '2026-02-13', 'status' => 'scheduled'],
    ]);

    $result = $this->service->tripCompletion(Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

    expect($result['total'])->toBe(4);
    expect($result['completed'])->toBe(2);
    expect($result['completion_rate'])->toBe(50.0);
});

test('reports respect the date range filter', function () {
    $this->schedule->trips()->createMany([
        ['trip_date' => '2026-01-15', 'status' => 'completed'], // out of range
        ['trip_date' => '2026-02-15', 'status' => 'completed'], // in range
    ]);

    $result = $this->service->tripCompletion(Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

    expect($result['total'])->toBe(1);
});

test('route performance reports trips and completion per route', function () {
    $this->schedule->trips()->createMany([
        ['trip_date' => '2026-02-10', 'status' => 'completed'],
        ['trip_date' => '2026-02-11', 'status' => 'delayed'],
    ]);

    $rows = $this->service->routePerformance(Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

    expect($rows)->toHaveCount(1);
    expect($rows->first())->toMatchArray(['code' => 'R-1', 'trips' => 2, 'completed' => 1, 'rate' => 50.0]);
});

test('fuel trend groups by month', function () {
    FuelLog::create(['vehicle_id' => $this->vehicle->id, 'liters' => 100, 'cost' => 1000, 'logged_at' => '2026-01-05']);
    FuelLog::create(['vehicle_id' => $this->vehicle->id, 'liters' => 50, 'cost' => 500, 'logged_at' => '2026-01-20']);
    FuelLog::create(['vehicle_id' => $this->vehicle->id, 'liters' => 80, 'cost' => 800, 'logged_at' => '2026-02-03']);

    $trend = $this->service->fuelTrend(Carbon::parse('2026-01-01'), Carbon::parse('2026-02-28'));

    expect($trend->pluck('period')->all())->toBe(['2026-01', '2026-02']);
    expect($trend->firstWhere('period', '2026-01')['liters'])->toBe(150.0);
    expect($trend->firstWhere('period', '2026-02')['cost'])->toBe(800.0);
});

test('maintenance summary aggregates per vehicle within range', function () {
    MaintenanceLog::create([
        'vehicle_id' => $this->vehicle->id, 'type' => 'routine', 'description' => 'Oil change',
        'cost' => 100, 'serviced_at' => '2026-02-05',
    ]);
    MaintenanceLog::create([
        'vehicle_id' => $this->vehicle->id, 'type' => 'corrective', 'description' => 'Brakes',
        'cost' => 50, 'serviced_at' => '2026-02-20',
    ]);
    MaintenanceLog::create([
        'vehicle_id' => $this->vehicle->id, 'type' => 'routine', 'description' => 'Out of range',
        'cost' => 999, 'serviced_at' => '2026-03-10',
    ]);

    $rows = $this->service->maintenanceSummary(Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

    expect($rows)->toHaveCount(1);
    expect($rows->first())->toMatchArray(['vehicle' => 'V-1', 'routine' => 1, 'corrective' => 1, 'cost' => 150.0]);
});

test('vehicle utilisation counts trips per vehicle', function () {
    $this->schedule->trips()->createMany([
        ['trip_date' => '2026-02-10', 'status' => 'completed'],
        ['trip_date' => '2026-02-11', 'status' => 'on_time'],
    ]);

    $rows = $this->service->vehicleUtilization(Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

    expect($rows->first())->toMatchArray(['vehicle' => 'V-1', 'trips' => 2]);
});

test('driver utilisation counts trips per driver', function () {
    $this->schedule->trips()->createMany([
        ['trip_date' => '2026-02-10', 'status' => 'completed'],
        ['trip_date' => '2026-02-11', 'status' => 'on_time'],
        ['trip_date' => '2026-03-10', 'status' => 'completed'], // out of range
    ]);

    $rows = $this->service->driverUtilization(Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

    expect($rows)->toHaveCount(1);
    expect($rows->first())->toMatchArray(['driver' => 'D1', 'trips' => 2]);
});

test('cost summary totals fuel and maintenance spend in range', function () {
    FuelLog::create(['vehicle_id' => $this->vehicle->id, 'liters' => 100, 'cost' => 1200, 'logged_at' => '2026-02-05']);
    FuelLog::create(['vehicle_id' => $this->vehicle->id, 'liters' => 50, 'cost' => 800, 'logged_at' => '2026-03-05']); // out of range
    MaintenanceLog::create(['vehicle_id' => $this->vehicle->id, 'type' => 'routine', 'description' => 'Service', 'cost' => 300, 'serviced_at' => '2026-02-10']);

    $summary = $this->service->costSummary(Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

    expect($summary['fuel'])->toBe(1200.0);
    expect($summary['maintenance'])->toBe(300.0);
    expect($summary['total'])->toBe(1500.0);
});

test('maintenance cost by type splits service vs repair', function () {
    MaintenanceLog::create(['vehicle_id' => $this->vehicle->id, 'type' => 'routine', 'description' => 'Service', 'cost' => 400, 'serviced_at' => '2026-02-05']);
    MaintenanceLog::create(['vehicle_id' => $this->vehicle->id, 'type' => 'corrective', 'description' => 'Repair', 'cost' => 150, 'serviced_at' => '2026-02-12']);

    $split = $this->service->maintenanceCostByType(Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

    expect($split['routine'])->toBe(400.0);
    expect($split['corrective'])->toBe(150.0);
});

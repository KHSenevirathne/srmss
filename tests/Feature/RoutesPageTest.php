<?php

use App\Livewire\RouteManager;
use App\Models\BusRoute;
use App\Models\RouteStop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Phase 3 — Routes module. Access control (manage-routes), route CRUD with a
 * unique code, and ordered-stop management (add / reorder / remove).
 * RefreshDatabase is applied globally in tests/Pest.php.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// --- Access control ---------------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get(route('routes'))->assertRedirect(route('login'));
});

test('a user with manage-routes can view the routes page', function () {
    $supervisor = User::factory()->create()->assignRole('supervisor');

    $this->actingAs($supervisor)
        ->get(route('routes'))
        ->assertOk()
        ->assertSee('Routes');
});

test('a user without manage-routes is forbidden', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('routes'))
        ->assertForbidden();
});

// --- Route CRUD + validation ------------------------------------------------

test('a route can be created', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('create')
        ->set('code', 'R-138')
        ->set('name', 'Colombo – Galle')
        ->set('start_point', 'Colombo Fort')
        ->set('end_point', 'Galle')
        ->set('total_distance_km', 119.0)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect(BusRoute::where('code', 'R-138')->exists())->toBeTrue();
});

test('a duplicate route code is rejected', function () {
    $admin = User::factory()->create()->assignRole('admin');
    BusRoute::create([
        'code' => 'R-1', 'name' => 'First', 'start_point' => 'A', 'end_point' => 'B',
    ]);

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('create')
        ->set('code', 'R-1')
        ->set('name', 'Second')
        ->set('start_point', 'C')
        ->set('end_point', 'D')
        ->call('save')
        ->assertHasErrors(['code']);
});

test('required fields are validated', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('create')
        ->set('code', '')
        ->set('name', '')
        ->set('start_point', '')
        ->set('end_point', '')
        ->call('save')
        ->assertHasErrors(['code', 'name', 'start_point', 'end_point']);
});

// --- Stops: add / reorder / remove ------------------------------------------

test('stops can be added in sequence', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $route = BusRoute::create([
        'code' => 'R-2', 'name' => 'R2', 'start_point' => 'A', 'end_point' => 'B',
    ]);

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('manageStops', $route->id)
        ->set('newStopName', 'Stop A')->call('addStop')
        ->set('newStopName', 'Stop B')->call('addStop');

    $stops = $route->stops()->get();
    expect($stops->pluck('name')->all())->toBe(['Stop A', 'Stop B']);
    expect($stops->pluck('sequence')->all())->toBe([1, 2]);
});

test('a stop can be moved up', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $route = BusRoute::create([
        'code' => 'R-3', 'name' => 'R3', 'start_point' => 'A', 'end_point' => 'B',
    ]);
    $first = RouteStop::create(['bus_route_id' => $route->id, 'name' => 'First', 'sequence' => 1]);
    $second = RouteStop::create(['bus_route_id' => $route->id, 'name' => 'Second', 'sequence' => 2]);

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('manageStops', $route->id)
        ->call('moveStopUp', $second->id);

    expect($route->stops()->pluck('name')->all())->toBe(['Second', 'First']);
});

test('removing a stop resequences the rest', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $route = BusRoute::create([
        'code' => 'R-4', 'name' => 'R4', 'start_point' => 'A', 'end_point' => 'B',
    ]);
    RouteStop::create(['bus_route_id' => $route->id, 'name' => 'One', 'sequence' => 1]);
    $two = RouteStop::create(['bus_route_id' => $route->id, 'name' => 'Two', 'sequence' => 2]);
    RouteStop::create(['bus_route_id' => $route->id, 'name' => 'Three', 'sequence' => 3]);

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('manageStops', $route->id)
        ->call('removeStop', $two->id);

    $stops = $route->stops()->get();
    expect($stops->pluck('name')->all())->toBe(['One', 'Three']);
    expect($stops->pluck('sequence')->all())->toBe([1, 2]);
});

test('deleting a route cascades its stops', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $route = BusRoute::create([
        'code' => 'R-5', 'name' => 'R5', 'start_point' => 'A', 'end_point' => 'B',
    ]);
    RouteStop::create(['bus_route_id' => $route->id, 'name' => 'Only', 'sequence' => 1]);

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('delete', $route->id);

    expect(BusRoute::find($route->id))->toBeNull();
    expect(RouteStop::where('bus_route_id', $route->id)->exists())->toBeFalse();
});

// --- Stops with coordinates + map ------------------------------------------

test('a stop can be added with coordinates', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $route = BusRoute::create([
        'code' => 'R-6', 'name' => 'R6', 'start_point' => 'A', 'end_point' => 'B',
    ]);

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('manageStops', $route->id)
        ->set('newStopName', 'Galle')
        ->set('newStopLat', '6.0535')
        ->set('newStopLng', '80.2210')
        ->call('addStop')
        ->assertHasNoErrors();

    $stop = $route->stops()->first();
    expect((float) $stop->latitude)->toBe(6.0535);
    expect((float) $stop->longitude)->toBe(80.2210);
});

test('the map shows a fallback when no API key is configured', function () {
    config(['services.google_maps.key' => null]);
    $admin = User::factory()->create()->assignRole('admin');
    $route = BusRoute::create([
        'code' => 'R-7', 'name' => 'R7', 'start_point' => 'A', 'end_point' => 'B',
    ]);
    RouteStop::create(['bus_route_id' => $route->id, 'name' => 'S1', 'sequence' => 1, 'latitude' => 6.0, 'longitude' => 80.0]);

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('viewMap', $route->id)
        ->assertSee('GOOGLE_MAPS_API_KEY');
});

test('the map renders when a key and coordinates are present', function () {
    config(['services.google_maps.key' => 'test-key']);
    $admin = User::factory()->create()->assignRole('admin');
    $route = BusRoute::create([
        'code' => 'R-8', 'name' => 'R8', 'start_point' => 'A', 'end_point' => 'B',
    ]);
    RouteStop::create(['bus_route_id' => $route->id, 'name' => 'S1', 'sequence' => 1, 'latitude' => 6.0, 'longitude' => 80.0]);

    Livewire::actingAs($admin)
        ->test(RouteManager::class)
        ->call('viewMap', $route->id)
        ->assertSee('routeMap')
        ->assertDontSee('GOOGLE_MAPS_API_KEY');
});

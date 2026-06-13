<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;

/*
 * HR-02 — audit trail. The LogsActivity trait records create/update/delete on the
 * audited models, and the viewer is restricted to administrators.
 * RefreshDatabase is applied globally in tests/Pest.php.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// --- Automatic activity capture --------------------------------------------

test('creating an audited record writes an activity log', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin);

    $vehicle = Vehicle::create(['registration_number' => 'AL-1', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);

    $log = ActivityLog::where('subject_type', 'Vehicle')->where('subject_id', $vehicle->id)->where('event', 'created')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
    expect($log->description)->toContain('AL-1');
});

test('updating and deleting an audited record are logged', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($admin);

    $vehicle = Vehicle::create(['registration_number' => 'AL-2', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);
    $vehicle->update(['mileage' => 500]);
    $vehicle->delete();

    expect(ActivityLog::where('subject_id', $vehicle->id)->where('subject_type', 'Vehicle')->where('event', 'updated')->exists())->toBeTrue();
    expect(ActivityLog::where('subject_id', $vehicle->id)->where('subject_type', 'Vehicle')->where('event', 'deleted')->exists())->toBeTrue();
});

test('the actor is null when no user is authenticated', function () {
    $vehicle = Vehicle::create(['registration_number' => 'AL-3', 'type' => 'bus', 'seating_capacity' => 50, 'mileage' => 0]);

    $log = ActivityLog::where('subject_id', $vehicle->id)->where('subject_type', 'Vehicle')->first();
    expect($log->user_id)->toBeNull();
});

// --- Viewer access control --------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get(route('activity-log'))->assertRedirect(route('login'));
});

test('an admin can view the activity log', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('activity-log'))
        ->assertOk()
        ->assertSee('Activity Log');
});

test('a supervisor cannot view the activity log', function () {
    $supervisor = User::factory()->create()->assignRole('supervisor');

    $this->actingAs($supervisor)
        ->get(route('activity-log'))
        ->assertForbidden();
});

test('an operator cannot view the activity log', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('activity-log'))
        ->assertForbidden();
});

<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

/*
 * Phase 1 — proves the role-aware guard on the Vehicles screen:
 *   - guests are bounced to login,
 *   - a user WITH manage-fleet (admin) can open the page (also confirms the
 *     full-page Livewire component renders inside the app layout),
 *   - a user WITHOUT it (operator) is blocked with 403.
 */

beforeEach(function () {
    // Build the roles + permissions in the fresh test database.
    // RefreshDatabase is applied globally in tests/Pest.php.
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected to the login page', function () {
    $this->get(route('vehicles'))->assertRedirect(route('login'));
});

test('a user with manage-fleet can view the vehicles page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('vehicles'))
        ->assertOk()
        ->assertSee('Vehicles');
});

test('a user without manage-fleet is forbidden', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('vehicles'))
        ->assertForbidden();
});

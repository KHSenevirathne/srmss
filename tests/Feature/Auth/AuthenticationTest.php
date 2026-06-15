<?php

use App\Models\Driver;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('staff authenticate with their email', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'login' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('a driver authenticates with their employee number', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $login = User::factory()->create(['email' => null]);
    $login->assignRole('driver');
    $driver = Driver::create([
        'user_id' => $login->id,
        'name' => 'Sunil', 'nic' => '900000000V', 'license_number' => 'DL-LOGIN',
        'license_expiry' => now()->addYear(), 'employee_number' => 'E-001',
    ]);

    $response = $this->post(route('login.store'), [
        'login' => 'E-001',
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($login);
    // Drivers land on their trips, not the dashboard.
    $response->assertRedirect(route('trips'));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'login' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

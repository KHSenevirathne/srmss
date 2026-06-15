<?php

use App\Models\Driver;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/** Seed roles and return a login-enabled driver user. */
function makeDriverLogin(): User
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $login = User::factory()->create(['email' => null]);
    $login->assignRole('driver');
    Driver::create([
        'user_id' => $login->id, 'name' => 'D', 'nic' => '900000001V',
        'license_number' => 'DL-D1', 'license_expiry' => now()->addYear(), 'employee_number' => 'E-009',
    ]);

    return $login;
}

test('security settings page shows only password management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Update password')
        ->assertDontSee('Two-factor authentication')
        ->assertDontSee('Passkeys');
});

test('security settings page requires password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertRedirect(route('password.confirm'));
});

test('a driver cannot reach the security settings page', function () {
    $this->actingAs(makeDriverLogin())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertForbidden();
});

test('a driver cannot reach the profile settings page', function () {
    $this->actingAs(makeDriverLogin())
        ->get(route('profile.edit'))
        ->assertForbidden();
});

test('a driver can change appearance (theme)', function () {
    $this->actingAs(makeDriverLogin())
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('Appearance');
});

test('password can be updated', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test('pages::settings.security')
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test('pages::settings.security')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});

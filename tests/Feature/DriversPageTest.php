<?php

use App\Livewire\DriverManager;
use App\Models\Driver;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

/*
 * Phase 2 : Drivers module. Mirrors VehiclesPageTest for access control and
 * adds CRUD + validation coverage via Livewire::test, per the module recipe
 * (docs/ARCHITECTURE.md §6: access control + create/edit/delete/validation).
 */

beforeEach(function () {
    // RefreshDatabase is applied globally in tests/Pest.php.
    $this->seed(RolesAndPermissionsSeeder::class);
});

// --- Access control ---------------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get(route('drivers'))->assertRedirect(route('login'));
});

test('a user with manage-fleet can view the drivers page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('drivers'))
        ->assertOk()
        ->assertSee('Drivers');
});

test('a user without manage-fleet is forbidden', function () {
    $operator = User::factory()->create()->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('drivers'))
        ->assertForbidden();
});

// --- CRUD + validation ------------------------------------------------------

test('a driver can be created', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', 'Sunil Perera')
        ->set('nic', '901234567V')
        ->set('license_number', 'DL-100')
        ->set('license_expiry', now()->addYear()->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $driver = Driver::where('license_number', 'DL-100')->first();
    expect($driver)->not->toBeNull();
    expect($driver->nic)->toBe('901234567V');
    expect($driver->employee_number)->toMatch('/^E-\d{3,}$/'); // auto-generated, short
});

test('the employee number is auto-generated, short, and sequential', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $make = function (string $nic, string $licence) use ($admin) {
        Livewire::actingAs($admin)
            ->test(DriverManager::class)
            ->call('create')
            ->set('name', 'Driver '.$licence)
            ->set('nic', $nic)
            ->set('license_number', $licence)
            ->set('license_expiry', now()->addYear()->toDateString())
            ->call('save')
            ->assertHasNoErrors();
    };

    $make('900000001V', 'DL-A1');
    $make('900000002V', 'DL-A2');

    $first = Driver::where('license_number', 'DL-A1')->first()->employee_number;
    $second = Driver::where('license_number', 'DL-A2')->first()->employee_number;

    expect($first)->toBe('E-001');
    expect($second)->toBe('E-002'); // increments
});

test('a driver can be edited', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $driver = Driver::create([
        'name' => 'Old Name', 'nic' => '911111111V', 'license_number' => 'DL-200',
        'license_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('edit', $driver->id)
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($driver->fresh()->name)->toBe('New Name');
});

test('a driver can be deleted', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $driver = Driver::create([
        'name' => 'Delete Me', 'license_number' => 'DL-300',
        'license_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('delete', $driver->id);

    expect(Driver::find($driver->id))->toBeNull();
});

test('required fields are validated', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', '')
        ->set('nic', '')
        ->set('license_number', '')
        ->set('license_expiry', '')
        ->call('save')
        ->assertHasErrors(['name', 'nic', 'license_number', 'license_expiry']);
});

test('a duplicate NIC is rejected', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Driver::create([
        'name' => 'First', 'nic' => '950000000V', 'license_number' => 'DL-N1',
        'license_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', 'Second')
        ->set('nic', '950000000V')
        ->set('license_number', 'DL-N2')
        ->set('license_expiry', now()->addYear()->toDateString())
        ->call('save')
        ->assertHasErrors(['nic']);

    expect(Driver::where('name', 'Second')->exists())->toBeFalse();
});

test('a duplicate licence number is rejected', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Driver::create([
        'name' => 'First', 'nic' => '960000000V', 'license_number' => 'DL-DUP',
        'license_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', 'Second')
        ->set('nic', '961111111V')
        ->set('license_number', 'DL-DUP')
        ->set('license_expiry', now()->addYear()->toDateString())
        ->call('save')
        ->assertHasErrors(['license_number']);

    expect(Driver::where('name', 'Second')->exists())->toBeFalse();
});

test('drivers can be filtered by status', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Driver::create(['name' => 'Active Ann', 'license_number' => 'DL-A', 'license_expiry' => now()->addYear(), 'status' => 'active']);
    Driver::create(['name' => 'Inactive Ivan', 'license_number' => 'DL-I', 'license_expiry' => now()->addYear(), 'status' => 'inactive']);

    $drivers = Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->set('statusFilter', 'inactive')
        ->viewData('drivers');

    expect($drivers->total())->toBe(1);
    expect($drivers->first()->name)->toBe('Inactive Ivan');
});

// --- Optional login provisioning --------------------------------------------

test('enabling login provisions a linked account with the driver role', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', 'Loginful')
        ->set('nic', '990000001V')
        ->set('license_number', 'DL-L1')
        ->set('license_expiry', now()->addYear()->toDateString())
        ->set('needsLogin', true)
        ->set('password', 'password')
        ->call('save')
        ->assertHasNoErrors();

    $driver = Driver::where('license_number', 'DL-L1')->first();
    expect($driver->user_id)->not->toBeNull();
    expect($driver->user->hasRole('driver'))->toBeTrue();
});

test('a password is required when enabling login', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', 'No Password')
        ->set('nic', '990000002V')
        ->set('license_number', 'DL-L2')
        ->set('license_expiry', now()->addYear()->toDateString())
        ->set('needsLogin', true)
        ->set('password', '')
        ->call('save')
        ->assertHasErrors(['password']);
});

test('disabling login removes the linked account', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('create')
        ->set('name', 'Toggle Off')
        ->set('nic', '990000003V')
        ->set('license_number', 'DL-L3')
        ->set('license_expiry', now()->addYear()->toDateString())
        ->set('needsLogin', true)
        ->set('password', 'password')
        ->call('save')
        ->assertHasNoErrors();

    $driver = Driver::where('license_number', 'DL-L3')->first();
    $userId = $driver->user_id;
    expect($userId)->not->toBeNull();

    Livewire::actingAs($admin)
        ->test(DriverManager::class)
        ->call('edit', $driver->id)
        ->set('needsLogin', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($driver->fresh()->user_id)->toBeNull();
    expect(User::find($userId))->toBeNull();
});

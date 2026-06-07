<?php

use App\Livewire\DriverManager;
use App\Livewire\RouteManager;
use App\Livewire\VehicleManager;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

/*
 * Authenticated SRMSS screens. Each module route is guarded by the permission
 * that gates it (see RolesAndPermissionsSeeder) via the `can:` middleware, so
 * a user who lacks the permission gets a 403 instead of reaching the page.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Fleet — Vehicles + Drivers share the manage-fleet permission.
    Route::get('/vehicles', VehicleManager::class)
        ->middleware('can:manage-fleet')
        ->name('vehicles');

    Route::get('/drivers', DriverManager::class)
        ->middleware('can:manage-fleet')
        ->name('drivers');

    // Route planning (Phase 3) — routes + ordered stops.
    Route::get('/routes', RouteManager::class)
        ->middleware('can:manage-routes')
        ->name('routes');
});

require __DIR__.'/settings.php';

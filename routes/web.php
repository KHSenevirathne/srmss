<?php

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

    // Fleet — Vehicles (Phase 2 adds Drivers here under the same guard).
    Route::get('/vehicles', VehicleManager::class)
        ->middleware('can:manage-fleet')
        ->name('vehicles');
});

require __DIR__.'/settings.php';

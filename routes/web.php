<?php

use App\Http\Controllers\ReportPdfController;
use App\Livewire\ActivityLogViewer;
use App\Livewire\Dashboard;
use App\Livewire\DriverManager;
use App\Livewire\FuelLogManager;
use App\Livewire\MaintenanceLogManager;
use App\Livewire\Reports;
use App\Livewire\RouteManager;
use App\Livewire\ScheduleManager;
use App\Livewire\TripManager;
use App\Livewire\UserManager;
use App\Livewire\VehicleManager;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

/*
 * Authenticated SRMSS screens. Each module route is guarded by the permission
 * that gates it (see RolesAndPermissionsSeeder) via the `can:` middleware, so
 * a user who lacks the permission gets a 403 instead of reaching the page.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Fleet : Vehicles + Drivers share the manage-fleet permission.
    Route::get('/vehicles', VehicleManager::class)
        ->middleware('can:manage-fleet')
        ->name('vehicles');

    Route::get('/drivers', DriverManager::class)
        ->middleware('can:manage-fleet')
        ->name('drivers');

    // Route planning (Phase 3) : routes + ordered stops.
    Route::get('/routes', RouteManager::class)
        ->middleware('can:manage-routes')
        ->name('routes');

    // Scheduling (Phase 4) : timetables + conflict-checked assignment + trips.
    Route::get('/schedules', ScheduleManager::class)
        ->middleware('can:manage-schedules')
        ->name('schedules');

    // Central trips board : staff (view-trips) see every trip; a driver
    // (view-own-trips) sees only their own. What each can do to a trip's status
    // is enforced inside the component (set / request / approve).
    Route::get('/trips', TripManager::class)
        ->middleware('can:access-trips')
        ->name('trips');

    // Fuel & maintenance logging (Phase 3) : both gated by log-fuel.
    Route::get('/fuel-logs', FuelLogManager::class)
        ->middleware('can:log-fuel')
        ->name('fuel-logs');

    Route::get('/maintenance-logs', MaintenanceLogManager::class)
        ->middleware('can:log-fuel')
        ->name('maintenance-logs');

    // Reporting (Phase 5) : on-screen reports + PDF export, gated by view-reports.
    Route::get('/reports', Reports::class)
        ->middleware('can:view-reports')
        ->name('reports');

    Route::get('/reports/pdf', [ReportPdfController::class, 'download'])
        ->middleware('can:view-reports')
        ->name('reports.pdf');

    // Administration (Phase 1) : create users + assign roles. Admin only.
    Route::get('/users', UserManager::class)
        ->middleware('can:manage-users')
        ->name('users');

    // Audit trail (HR-02) : read-only, admin only.
    Route::get('/activity-log', ActivityLogViewer::class)
        ->middleware('can:manage-users')
        ->name('activity-log');
});

require __DIR__.'/settings.php';

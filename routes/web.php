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

// Each route is guarded by the permission it needs (see RolesAndPermissionsSeeder).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Vehicles + Drivers share the manage-fleet permission.
    Route::get('/vehicles', VehicleManager::class)
        ->middleware('can:manage-fleet')
        ->name('vehicles');

    Route::get('/drivers', DriverManager::class)
        ->middleware('can:manage-fleet')
        ->name('drivers');

    Route::get('/routes', RouteManager::class)
        ->middleware('can:manage-routes')
        ->name('routes');

    Route::get('/schedules', ScheduleManager::class)
        ->middleware('can:manage-schedules')
        ->name('schedules');

    // Staff see every trip; a driver sees only their own (enforced in the component).
    Route::get('/trips', TripManager::class)
        ->middleware('can:access-trips')
        ->name('trips');

    Route::get('/fuel-logs', FuelLogManager::class)
        ->middleware('can:log-fuel')
        ->name('fuel-logs');

    Route::get('/maintenance-logs', MaintenanceLogManager::class)
        ->middleware('can:log-fuel')
        ->name('maintenance-logs');

    Route::get('/reports', Reports::class)
        ->middleware('can:view-reports')
        ->name('reports');

    Route::get('/reports/pdf', [ReportPdfController::class, 'download'])
        ->middleware('can:view-reports')
        ->name('reports.pdf');

    Route::get('/users', UserManager::class)
        ->middleware('can:manage-users')
        ->name('users');

    // Read-only audit trail, admin only.
    Route::get('/activity-log', ActivityLogViewer::class)
        ->middleware('can:manage-users')
        ->name('activity-log');
});

require __DIR__.'/settings.php';

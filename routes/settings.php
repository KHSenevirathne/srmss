<?php

use App\Http\Middleware\EnsureUserIsNotDriver;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Drivers have no editable profile, so /settings sends them straight to the
    // one settings page they may use : appearance (theme).
    Route::get('settings', fn () => redirect()->route(
        auth()->user()->isDriver() ? 'appearance.edit' : 'profile.edit'
    ));
});

// Appearance (theme) is available to everyone, drivers included.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
});

// Self-service account editing : not available to drivers, whose accounts are
// managed for them on the Drivers screen.
Route::middleware(['auth', EnsureUserIsNotDriver::class])->group(function () {
    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified', EnsureUserIsNotDriver::class])->group(function () {
    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

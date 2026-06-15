<?php

use App\Http\Middleware\EnsureUserIsNotDriver;
use Illuminate\Support\Facades\Route;

// Self-service account settings : not available to drivers, whose accounts are
// managed for them on the Drivers screen.
Route::middleware(['auth', EnsureUserIsNotDriver::class])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified', EnsureUserIsNotDriver::class])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

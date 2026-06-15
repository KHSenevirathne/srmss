<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Drivers have no dashboard; they land on their own trips. Staff go to the
 * intended page or the configured home (the dashboard).
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        if ($request->user()?->isDriver()) {
            return redirect()->route('trips');
        }

        return redirect()->intended(config('fortify.home'));
    }
}

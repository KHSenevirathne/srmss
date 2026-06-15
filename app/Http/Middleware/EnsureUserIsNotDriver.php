<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drivers cannot edit their own account : their profile, email and password are
 * managed for them on the Drivers screen by an admin or supervisor. This guards
 * the self-service settings pages against the driver role.
 */
class EnsureUserIsNotDriver
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isDriver(), 403, 'Drivers cannot manage their own account.');

        return $next($request);
    }
}

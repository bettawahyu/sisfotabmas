<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WajibGantiPassword
{
    /**
     * Send a user whose password was set by someone else to the change-password page until they replace it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->wajib_ganti_password && ! $request->routeIs('password.wajib', 'password.update', 'logout')) {
            return redirect()->route('password.wajib');
        }

        return $next($request);
    }
}

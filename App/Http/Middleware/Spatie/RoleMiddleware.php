<?php

namespace App\Http\Middleware\Spatie;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role, $guard = null)
    {
        if (! $request->user($guard)?->hasRole($role)) {
            abort(403);
        }

        return $next($request);
    }
}

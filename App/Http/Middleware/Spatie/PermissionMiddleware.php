<?php

namespace App\Http\Middleware\Spatie;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission, $guard = null)
    {
        if (! $request->user($guard)?->can($permission)) {
            abort(403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware\Spatie;

use Closure;
use Illuminate\Http\Request;

class RoleOrPermissionMiddleware
{
    public function handle(Request $request, Closure $next, $roleOrPermission, $guard = null)
    {
        if (! $request->user($guard)?->hasAnyRole([$roleOrPermission]) &&
            ! $request->user($guard)?->can($roleOrPermission)) {
            abort(403);
        }

        return $next($request);
    }
}

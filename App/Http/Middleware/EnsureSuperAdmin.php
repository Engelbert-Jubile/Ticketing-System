<?php

namespace App\Http\Middleware;

use App\Support\RoleHelpers;
use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! RoleHelpers::userIsSuperAdmin($request->user())) {
            abort(403);
        }

        return $next($request);
    }
}

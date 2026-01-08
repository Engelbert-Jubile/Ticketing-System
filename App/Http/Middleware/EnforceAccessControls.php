<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SettingsService;
use App\Support\RoleHelpers;
use App\Support\SecurityPolicy;
use Closure;
use Illuminate\Http\Request;

class EnforceAccessControls
{
    public function handle(Request $request, Closure $next)
    {
        $settings = app(SettingsService::class);

        if (! SecurityPolicy::ipRestrictionsEnabled($settings)) {
            return $next($request);
        }

        $ip = $request->ip();
        if (SecurityPolicy::ipAllowed($ip, $settings)) {
            return $next($request);
        }

        $user = $request->user();
        $allowBypass = (bool) $settings->get('security', 'allow_superadmin_ip_bypass', true);
        if ($allowBypass && RoleHelpers::userIsSuperAdmin($user)) {
            return $next($request);
        }

        $impersonatorId = $request->session()->get('impersonator_id');
        if ($allowBypass && $impersonatorId) {
            $impersonator = User::query()->find($impersonatorId);
            if (RoleHelpers::userIsSuperAdmin($impersonator)) {
                return $next($request);
            }
        }

        abort(403, 'Access blocked by IP restriction policy.');
    }
}

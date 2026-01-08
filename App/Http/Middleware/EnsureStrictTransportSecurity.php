<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureStrictTransportSecurity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! config('security.hsts.enabled')) {
            return $response;
        }

        if (! $request->secure()) {
            return $response;
        }

        $maxAge = (int) config('security.hsts.max_age', 31536000);
        $header = "max-age={$maxAge}";

        if (config('security.hsts.include_subdomains')) {
            $header .= '; includeSubDomains';
        }

        if (config('security.hsts.preload')) {
            $header .= '; preload';
        }

        $response->headers->set('Strict-Transport-Security', $header);

        return $response;
    }
}

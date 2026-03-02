<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        view()->share('cspNonce', $nonce);
        $request->attributes->set('csp_nonce', $nonce);

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://www.google.com https://www.gstatic.com",
            "style-src 'self' 'nonce-{$nonce}' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-src https://www.google.com/recaptcha/",
        ]).';';

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}

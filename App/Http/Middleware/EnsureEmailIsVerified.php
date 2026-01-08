<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1️⃣  Jika user belum login ⇒ lanjutkan (biarkan middleware "auth" yang menanganinya)
        if (! $request->user()) {
            return $next($request);
        }

        // 2️⃣  Jika email SUDAH ter-verifikasi ⇒ lanjutkan ke route berikutnya
        if ($request->user() && $request->user()->hasVerifiedEmail()) {
            return $next($request);
        }

        // 3️⃣  Kalau AJAX/JSON, kembalikan 403 – cocok untuk SPA/API
        if ($request->expectsJson()) {
            abort(403, 'Your email address is not verified.');
        }

        // 4️⃣  Kalau web biasa, lempar ke halaman verifikasi email (route('verification.notice'))
        return Redirect::route('verification.notice');
    }
}

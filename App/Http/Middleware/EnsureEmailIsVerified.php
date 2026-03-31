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
        $user = $request->user();

        // Let auth middleware handle guests.
        if (! $user) {
            return $next($request);
        }

        // Superadmin can bypass email verification.
        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return $next($request);
        }

        if (! (bool) config('features.email_verification', true)) {
            return $next($request);
        }

        $email = strtolower(trim((string) $user->email));
        if (! str_ends_with($email, '@kftd.co.id')) {
            return $next($request);
        }

        if ($user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Your email address is not verified.');
        }

        return Redirect::route('verification.notice', [
            'locale' => $request->route('locale') ?? app()->getLocale() ?? config('app.locale', 'en'),
        ]);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Authenticate
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $locale = $this->resolveLocale($request);

        if (Auth::guard()->guest()) {
            if ($request->header('X-Inertia')) {
                $request->session()->put('url.intended', $request->fullUrl());

                return Inertia::location(route('login', ['locale' => $locale]));
            }

            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->guest(route('login', ['locale' => $locale]));
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        $candidates = [
            app()->getLocale(),
            $request->route('locale'),
            $request->query('lang'),
            $request->query('locale'),
            $request->session()->get('app.locale'),
            config('app.locale', 'en'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && in_array($value, $supported, true)) {
                return $value;
            }
        }

        return $supported[0] ?? 'en';
    }
}

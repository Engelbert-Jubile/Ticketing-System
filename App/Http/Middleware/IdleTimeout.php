<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class IdleTimeout
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $locale = $this->resolveLocale($request);

        $settings = app(SettingsService::class);
        $idleMinutes = (int) $settings->get(
            'security',
            'session_timeout_minutes',
            (int) config('session.idle_timeout', (int) env('SESSION_IDLE_TIMEOUT', 30))
        );
        if ($idleMinutes > 0) {
            $now = time();
            $lastActivity = (int) $request->session()->get('last_activity', $now);

            if (($now - $lastActivity) > ($idleMinutes * 60)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $statusMessage = 'Sesi berakhir karena tidak ada aktivitas. Silakan login ulang.';

                // If this was an Inertia visit, a normal 302 redirect will be treated as an invalid
                // Inertia response and shown as an iframe/modal ("double page"). Force a hard visit.
                if ($request->header('X-Inertia')) {
                    $request->session()->flash('status', $statusMessage);
                    $request->session()->put('url.intended', $request->fullUrl());

                    return Inertia::location(route('login', ['locale' => $locale]));
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Session expired due to inactivity.',
                    ], 401);
                }

                return redirect()
                    ->route('login', ['locale' => $locale])
                    ->with('status', $statusMessage);
            }
        }

        $request->session()->put('last_activity', time());

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

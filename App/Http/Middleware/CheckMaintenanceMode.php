<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckMaintenanceMode
{
    /**
     * URIs that stay reachable even when maintenance is enabled.
     *
     * - Static assets (vite/build, storage, livewire, etc.)
     * - Health check endpoint
     * - Public welcome/root so we can show a friendly overlay.
     */
    private array $allowedPatterns = [
        'storage/*',
        'livewire/*',
        '_debugbar/*',
        'vendor/*',
        'horizon/*',
        'telescope/*',
        'build/*',
        'dist/*',
        'css/*',
        'js/*',
        'fonts/*',
        'assets/*',
        'favicon.ico',
        'robots.txt',
        'manifest.webmanifest',
        'mix-manifest.json',
        'service-worker.js',
    ];

    /**
     * Limit which areas admins can bypass to (dashboard/settings by default).
     */
    private array $adminBypassPrefixes = [
        'dashboard',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! config('features.maintenance_controls', true)) {
            return $next($request);
        }

        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);
        $enabled = (bool) $settings->getRaw('general', 'maintenance_enabled', false);
        $message = $settings->getRaw('general', 'maintenance_message', 'Maintenance in progress.');

        if (! $enabled) {
            return $next($request);
        }

        if ($this->isWhitelisted($request)) {
            return $next($request);
        }

        $user = Auth::guard('web')->user() ?: $request->user();
        $isSuperAdmin = $this->userIsSuperAdmin($user);

        $isLoginRoute = $this->isLoginRoute($request);

        // Allow superadmin through everything (including login/dashboard) during maintenance.
        if ($isSuperAdmin) {
            return $next($request);
        }

        // Logged-in non-superadmin gets blocked everywhere (except logout and whitelisted assets) while maintenance is active.
        if ($user && ! $this->isLogoutRoute($request) && ! $this->isAuthEntryPoint($request) && ! $this->isWhitelisted($request)) {
            return $this->maintenanceResponse($request, $message, 503, true);
        }

        // Allow login form/submit to load so superadmin can sign in and turn off maintenance.
        if (! $user && $isLoginRoute) {
            return $next($request);
        }

        if (! $isLoginRoute && $this->isAuthEntryPoint($request)) {
            return $this->maintenanceResponse($request, $message, 503, true);
        }

        // All other cases: allow the request to proceed (including login for superadmin).
        return $next($request);
    }

    private function isWhitelisted(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        if (in_array($routeName, ['inertia.health', 'root.locale.redirect', 'home', 'welcome'], true)) {
            return true;
        }

        if ($request->routeIs('storage.*')) {
            return true;
        }

        // Allow bare root (/), even before route is resolved.
        $rawPath = $request->path();
        if ($rawPath === null || $rawPath === '' || $rawPath === '/') {
            return true;
        }

        foreach ($this->allowedPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        // Allow the welcome page even when the locale prefix is present.
        $path = ltrim($request->path(), '/');
        $segments = explode('/', $path);
        if (count($segments) >= 1) {
            $first = $segments[0];
            $second = $segments[1] ?? null;
            $locales = config('app.supported_locales', ['en', 'id']);
            if (in_array($first, $locales, true) && ($second === null || $second === '')) {
                return true;
            }
            if (in_array($first, $locales, true) && $second === 'welcome') {
                return true;
            }
        }

        return false;
    }

    private function isAuthEntryPoint(Request $request): bool
    {
        if ($request->routeIs('login', 'login.store', 'register', 'register.store', 'password.*')) {
            return true;
        }

        $path = ltrim($request->path(), '/');
        $segments = explode('/', $path);
        $locales = config('app.supported_locales', ['en', 'id']);
        $first = $segments[0] ?? '';
        $second = $segments[1] ?? '';
        $target = in_array($first, $locales, true) ? $second : $first;

        return in_array($target, ['login', 'register', 'password'], true);
    }

    private function isLoginRoute(Request $request): bool
    {
        if ($request->routeIs('login', 'login.store')) {
            return true;
        }

        $path = ltrim($request->path(), '/');
        $segments = explode('/', $path);
        $locales = config('app.supported_locales', ['en', 'id']);
        $first = $segments[0] ?? '';
        $second = $segments[1] ?? '';
        $target = in_array($first, $locales, true) ? $second : $first;

        return $target === 'login';
    }

    private function isLogoutRoute(Request $request): bool
    {
        return $request->routeIs('logout', 'logout.get');
    }

    private function userIsSuperAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        $roles = collect(
            method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : ($user->roles?->pluck('name')->toArray() ?? [])
        )->map(fn ($name) => Str::of($name ?? '')->lower()->trim()->toString())->filter()->values();

        return $roles->contains(fn ($name) => in_array($name, ['superadmin', 'super admin', 'super_admin'], true));
    }

    private function maintenanceResponse(Request $request, ?string $message, int $status = 503, bool $forceView = false)
    {
        if ($forceView || ! $request->expectsJson()) {
            return response()->view('errors.maintenance', ['message' => $message], $status);
        }

        return response()->json(['message' => $message ?: 'Maintenance in progress.'], $status);
    }
}

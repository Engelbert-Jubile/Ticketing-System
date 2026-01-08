<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class SetLocaleFromUrl
{
    public function __construct(private SettingsService $settings) {}

    public function handle(Request $request, Closure $next)
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        // Single source of truth: DB (general.locale), fallback to config only when DB empty
        $settingsLocale = $this->normalize(
            $this->settings->get('general', 'locale', config('app.locale', 'en')),
            $supported
        );
        $fallbackLocale = $this->normalize(config('app.locale', 'en'), $supported) ?: ($supported[0] ?? 'en');
        $resolvedLocale = $settingsLocale ?: $fallbackLocale;

        // Resolve route prefix if present, but do NOT override DB-sourced locale
        $routeLocale = $this->normalize($request->route('locale') ?? $request->segment(1), $supported);
        $isSettingsUpdate = $request->routeIs('settings.general.update');

        // If the URL prefix differs from resolved locale, keep serving with resolved locale (no redirect loops)
        if (! $routeLocale || $routeLocale !== $resolvedLocale) {
            $request->route()?->setParameter('locale', $resolvedLocale);
        }

        $locale = $resolvedLocale;

        if (config('app.debug')) {
            Log::info('locale.middleware.resolve', [
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'segment_locale' => $request->segment(1),
                'route_locale' => $request->route('locale'),
                'settings_locale' => $settingsLocale,
                'fallback_locale' => $fallbackLocale,
                'resolved_locale' => $resolvedLocale,
                'app_locale_before' => app()->getLocale(),
            ]);
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        Date::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        $request->session()->put('app.locale', $locale);
        cookie()->queue(cookie('app_locale', $locale, 60 * 24 * 30));

        $this->persistUserLocale($locale);

        if (config('app.debug')) {
            Log::info('locale.middleware.applied', [
                'path' => $request->path(),
                'app_locale_after' => app()->getLocale(),
            ]);
        }

        return $next($request);
    }

    private function normalize(?string $locale, array $supported): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        $clean = strtolower(trim($locale));

        return in_array($clean, $supported, true) ? $clean : null;
    }

    private function redirectToLocale(Request $request, string $locale)
    {
        $segments = $request->segments();
        if (isset($segments[0]) && $this->normalize($segments[0], config('app.supported_locales', ['en', 'id']))) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        $path = implode('/', $segments);

        $queryString = $request->getQueryString();

        return redirect()->to('/'.$path.($queryString ? '?'.$queryString : ''));
    }

    private function persistUserLocale(string $locale): void
    {
        $user = Auth::user();
        if (! $user || ! Schema::hasColumn('users', 'locale')) {
            return;
        }

        if ($user->locale !== $locale) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }
    }
}

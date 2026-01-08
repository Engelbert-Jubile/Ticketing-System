<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;

class ApplyLocaleFromSettings
{
    public function __construct(private SettingsService $settings) {}

    public function handle($request, Closure $next)
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        $routeLocale = $this->extractRouteLocale($request, $supported);
        $queryLocale = $this->normalizeLocale($request->query('lang') ?? $request->query('locale'));
        $sessionLocale = $this->normalizeLocale($request->session()->get('app.locale'));
        $cookieLocale = $this->normalizeLocale($request->cookie('app_locale'));
        $settingsLocale = $this->normalizeLocale($this->settings->get('general', 'locale', config('app.locale', 'en')));

        // Locale harus selalu berasal dari URL prefix; jika belum ada, arahkan ke URL berprefix.
        if (! $routeLocale) {
            $target = $queryLocale ?? $sessionLocale ?? $cookieLocale ?? $settingsLocale ?? ($supported[0] ?? 'en');

            return $this->redirectToLocale($request, $target, $supported);
        }

        $targetLocale = $routeLocale;
        $this->persistLocale($request, $targetLocale);

        if ($queryLocale && $queryLocale !== $targetLocale) {
            // Hapus query locale agar tidak menimpa URL sebagai sumber kebenaran
            return $this->redirectToLocale($request, $targetLocale, $supported);
        }

        app()->setLocale($targetLocale);
        Carbon::setLocale($targetLocale);
        Date::setLocale($targetLocale);
        URL::defaults(['locale' => $targetLocale]);

        return $next($request);
    }

    private function normalizeLocale($locale): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        $clean = strtolower(trim($locale));
        $allowed = config('app.supported_locales', ['en', 'id']);

        return in_array($clean, $allowed, true) ? $clean : null;
    }

    private function extractRouteLocale(Request $request, array $supported): ?string
    {
        $routeLocale = $this->normalizeLocale($request->route('locale'));
        if ($routeLocale) {
            return $routeLocale;
        }

        $firstSegment = $request->segment(1);

        return $this->normalizeLocale($firstSegment);
    }

    private function persistLocale(Request $request, string $locale): void
    {
        $request->session()->put('app.locale', $locale);
        cookie()->queue(cookie('app_locale', $locale, 60 * 24 * 30)); // 30 days
    }

    private function redirectToLocale(Request $request, string $locale, array $supported)
    {
        if (! in_array($locale, $supported, true)) {
            $locale = $supported[0] ?? 'en';
        }

        $segments = $request->segments();
        if (isset($segments[0]) && $this->normalizeLocale($segments[0])) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        $path = implode('/', $segments);

        $query = $request->query();
        unset($query['lang'], $query['locale']);
        $url = '/'.$path.($query ? '?'.http_build_query($query) : '');

        return redirect()->to($url);
    }
}

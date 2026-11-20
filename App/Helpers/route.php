<?php

if (! function_exists('routeLocale')) {
    function routeLocale(string $name, array $params = [], bool $absolute = true) {
        if (! isset($params['locale'])) {
            $params['locale'] = request()->route('locale');
        }

        return route($name, $params, $absolute);
    }
}

if (! function_exists('portalEntryUrl')) {
    function portalEntryUrl(string $locale, array $params = [], bool $absolute = true): string
    {
        $params['locale'] = $locale;

        if (\Illuminate\Support\Facades\Route::has('portal')) {
            return route('portal', $params, $absolute);
        }

        // Serve the portal through the existing dashboard route during a deployment.
        return route('dashboard', ['view' => 'portal', ...$params], $absolute);
    }
}


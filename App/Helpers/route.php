<?php

if (! function_exists('routeLocale')) {
    function routeLocale(string $name, array $params = [], bool $absolute = true) {
        if (! isset($params['locale'])) {
            $params['locale'] = request()->route('locale');
        }

        return route($name, $params, $absolute);
    }
}

if (! function_exists('portalEntryRoute')) {
    function portalEntryRoute(): string
    {
        // The previous route cache can still be active during a deployment.
        return \Illuminate\Support\Facades\Route::has('portal') ? 'portal' : 'dashboard';
    }
}


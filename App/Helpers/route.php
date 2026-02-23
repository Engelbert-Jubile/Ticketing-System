<?php

if (! function_exists('routeLocale')) {
    function routeLocale(string $name, array $params = [], bool $absolute = true) {
        if (! isset($params['locale'])) {
            $params['locale'] = request()->route('locale');
        }

        return route($name, $params, $absolute);
    }
}


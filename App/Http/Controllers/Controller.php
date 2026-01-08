<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Database\Eloquent\Model;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Generate a route URL while automatically injecting the active locale.
     */
    protected function routeWithLocale(string $name, array $parameters = [], bool $absolute = true): string
    {
        if (! array_key_exists('locale', $parameters)) {
            $parameters = ['locale' => app()->getLocale()] + $parameters;
        }

        return route($name, $parameters, $absolute);
    }

    /**
     * Convenience wrapper for attachment routes (view/download/etc).
     */
    protected function attachmentRoute(string $name, $attachment, bool $absolute = true): string
    {
        $id = $attachment instanceof Model ? $attachment->getRouteKey() : $attachment;

        return $this->routeWithLocale($name, ['attachment' => $id], $absolute);
    }
}

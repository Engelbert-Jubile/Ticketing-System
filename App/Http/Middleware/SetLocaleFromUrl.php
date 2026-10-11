<?php

namespace App\Http\Middleware;

use App\Support\UserLocale;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;

class SetLocaleFromUrl
{
    public function handle(Request $request, Closure $next)
    {
        $locale = UserLocale::resolve($request->user());
        app()->setLocale($locale);
        Carbon::setLocale($locale);
        Date::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        // Resolve each request from the account; never copy a shared cookie/session
        // or the global default into an explicit personal preference.
        return $next($request);
    }
}

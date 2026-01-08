<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;

class EnforceSettingsMaintenance
{
    public function handle(Request $request, Closure $next)
    {
        // Hanya berlaku jika fitur maintenance diaktifkan
        if (! config('features.maintenance_controls', true)) {
            return $next($request);
        }

        // Jika Laravel sudah dalam mode maintenance standar, biarkan middleware bawaan yang tangani.
        if (app()->isDownForMaintenance()) {
            return $next($request);
        }

        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);
        $enabled = (bool) $settings->getRaw('general', 'maintenance_enabled', false);

        if (! $enabled) {
            return $next($request);
        }

        // Izinkan superadmin untuk masuk supaya bisa mematikan maintenance.
        $user = $request->user();
        $isSuperAdmin = false;
        if ($user) {
            $roleNames = collect(
                method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : ($user->roles?->pluck('name')->toArray() ?? [])
            )->map(fn ($name) => strtolower(trim($name ?? '')))->filter();

            $isSuperAdmin = $roleNames->contains(fn ($name) => in_array($name, ['superadmin', 'super admin', 'super_admin'], true));
        }
        if ($isSuperAdmin) {
            return $next($request);
        }

        $message = $settings->getRaw('general', 'maintenance_message', 'Maintenance');

        return response()->view('errors.maintenance', ['message' => $message], 503);
    }
}

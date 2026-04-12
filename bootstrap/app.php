<?php

use App\Http\Middleware\Spatie\PermissionMiddleware;
use App\Http\Middleware\Spatie\RoleMiddleware;
use App\Http\Middleware\Spatie\RoleOrPermissionMiddleware;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnforceAccessControls;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ Global middleware (untuk semua request)
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
	    \App\Http\Middleware\ContentSecurityPolicy::class,
        ]);

        // ✅ Middleware group: web
        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\InjectLoginLoadingOverlay::class,
        ]);

        // ✅ Middleware group: api (tanpa Sanctum)
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // ✅ Alias middleware
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'idle.timeout' => \App\Http\Middleware\IdleTimeout::class,
            'superadmin' => EnsureSuperAdmin::class,
            'access.restrict' => EnforceAccessControls::class,

            // 🔐 Spatie Permissions (Custom)
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Optional: custom exception handler
    })
    ->create();

// Permanent fix for Linux case-sensitive filesystems:
// this project stores application classes in "App/" (capital A), not "app/".
$app->useAppPath(__DIR__.'/../App');

return $app;

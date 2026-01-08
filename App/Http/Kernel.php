<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureStrictTransportSecurity;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocaleFromUrl;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\IdleTimeout;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
// Spatie permission middlewares
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Spatie\Permission\Middlewares\RoleMiddleware;
use Spatie\Permission\Middlewares\RoleOrPermissionMiddleware;

class Kernel extends HttpKernel
{
    /**
     * Global HTTP middleware stack.
     */
    protected $middleware = [
        // urutan aman & umum
        TrustProxies::class,
        PreventRequestsDuringMaintenance::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
        EnsureStrictTransportSecurity::class,
        CheckMaintenanceMode::class,
    ];

    /**
     * Middleware groups.
     */
    protected $middlewareGroups = [
        'web' => [
            SetLocaleFromUrl::class,
            \App\Http\Middleware\InjectLoginLoadingOverlay::class,
            HandleInertiaRequests::class,
            // untuk route web; session/csrf sudah termasuk di $middleware
        ],

        'api' => [
            // throttle default untuk API
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ],
    ];

    /**
     * Individually assignable middleware aliases.
     *
     * Gunakan nama alias ini pada route/middleware.
     */
    protected $middlewareAliases = [
        // Laravel built-ins
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'idle.timeout' => IdleTimeout::class,
        'superadmin' => EnsureSuperAdmin::class,
        'set-locale-from-url' => SetLocaleFromUrl::class,

        // Spatie Permission
        'role' => RoleMiddleware::class,
        'permission' => PermissionMiddleware::class,
        'role_or_permission' => RoleOrPermissionMiddleware::class,
    ];

    /**
     * Prioritized middleware execution order.
     * Ensure locale is set before Inertia shares props.
     */
    protected $middlewarePriority = [
        \App\Http\Middleware\SetLocaleFromUrl::class,
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        HandleInertiaRequests::class,
        SubstituteBindings::class,
        Authenticate::class,
        ThrottleRequests::class,
        \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
        Authorize::class,
    ];
}

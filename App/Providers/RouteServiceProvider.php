<?php

namespace App\Providers;

use App\Domains\Project\Models\Project;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Path to the "home" route for your application.
     *
     * Used by Laravel authentication to redirect users after login.
     */
    public const HOME = '/dashboard';

    public function boot(): void
    {
        // **Jangan lupa panggil parent's boot()**
        parent::boot();

        // **Definisikan route-model binding kustom**
        Route::model('project', Project::class);
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}

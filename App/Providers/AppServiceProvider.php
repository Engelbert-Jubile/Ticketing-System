<?php

namespace App\Providers;

use App\Domains\Project\Interfaces\ProjectRepositoryInterface;
use App\Domains\Project\Models\Status;
use App\Domains\Project\Repositories\ProjectRepository;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\RegisterUser;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;         // Komponen Login kita
use Illuminate\Routing\Events\RouteMatched;
use Livewire\Livewire;  // Komponen RegisterUser kita

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Binding interface ke implementasi (repository pattern)
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
    }

    public function boot(): void
    {
        // Daftarkan alias Livewire untuk RegisterUser (sesuaikan jika namespace berbeda)
        Livewire::component('auth.register-user', RegisterUser::class);

        // Daftarkan alias Livewire untuk Login (ƒ?opages.auth.loginƒ?? di Blade / route)
        Livewire::component('pages.auth.login', Login::class);

        Status::ensureDefaults();

        // Pastikan parameter locale selalu tersedia saat membangkitkan URL.
        $this->app['router']->matched(function (RouteMatched $event) {
            $locale = $event->route->parameter('locale') ?? app()->getLocale() ?? config('app.locale', 'en');
            URL::defaults(['locale' => $locale]);
        });
    }

}

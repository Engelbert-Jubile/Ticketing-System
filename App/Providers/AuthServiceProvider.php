<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Workflow;
use App\Policies\UserPolicy;
use App\Policies\WorkflowPolicy;
use App\Support\RoleHelpers;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Workflow::class => WorkflowPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            return RoleHelpers::userIsSuperAdmin($user) ? true : null;
        });
    }
}

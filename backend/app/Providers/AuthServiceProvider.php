<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register before package boot callbacks, so direct permissions cannot bypass
        // the employee boundary through Spatie's early permission grant.
        Gate::before(function ($user,$ability) {
            if (\App\Services\EmployeeAccess::limited($user) && !in_array($ability,\App\Services\EmployeeAccess::PERMISSIONS,true)) return false;
            return null;
        });
    }
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Implicitly grant "Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            if (\App\Services\EmployeeAccess::limited($user) && !in_array($ability, \App\Services\EmployeeAccess::PERMISSIONS, true)) return false;
            return $user->hasRole('admin') ? true : null;
        });
    }
}

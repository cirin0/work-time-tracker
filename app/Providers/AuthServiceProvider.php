<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [

    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('view-all-users', function (User $currentUser) {
            return $currentUser->isAdmin();
        });

        Gate::define('update-role', function (User $currentUser, User $targetUser) {
            return $currentUser->isAdmin();
        });
        Gate::define('manage-profile', function (User $currentUser, User $targetUser) {
            return $currentUser->id === $targetUser->id || $currentUser->isAdmin();
        });
    }
}

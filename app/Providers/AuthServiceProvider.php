<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;


class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [

    ];


    public function boot()
    {
        $this->registerPolicies();

        Gate::define('manage-tasks', function ($user) {
            Log::info('User type check:', [
                'user_id' => $user->id,
                'user_type' => $user->type,
                'has_access' => in_array($user->type, ['admin', 'manager', 'super-admin']),
            ]);

            return in_array($user->type, ['admin', 'manager', 'super-admin']);
        });
    }
}

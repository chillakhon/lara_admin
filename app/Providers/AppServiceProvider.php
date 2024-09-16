<?php

namespace App\Providers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if($this->app->environment('production') || $this->app->environment('development') || $this->app->environment('local')) {
            URL::forceScheme('https');
        }

    }

    public static function setUrlsToHttps(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setPath(preg_replace('/^http:/', 'https:', $paginator->path()));

        return $paginator;
    }
}

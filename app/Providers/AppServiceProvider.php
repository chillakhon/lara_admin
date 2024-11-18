<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
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

        Relation::morphMap([
            'Material' => \App\Models\Material::class,
            'material' => \App\Models\Material::class,
            'Product' => \App\Models\Product::class,
            'product' => \App\Models\Product::class,
            'Variant' => \App\Models\ProductVariant::class,
            'variant' => \App\Models\ProductVariant::class,
        ]);

    }

    public static function setUrlsToHttps(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setPath(preg_replace('/^http:/', 'https:', $paginator->path()));

        return $paginator;
    }
}

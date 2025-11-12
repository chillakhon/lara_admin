<?php

namespace App\Providers;

use App\Facades\Notification;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('notification-service', function ($app) {
            return new NotificationService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Регистрируем Facade (опционально, если нужен)
    }
}

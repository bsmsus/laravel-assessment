<?php

namespace Rahul\Discounts;

use Illuminate\Support\ServiceProvider;

class DiscountServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/discounts.php', 'discounts');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/discounts.php' => config_path('discounts.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        if (! $this->app->environment('testing')) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
}

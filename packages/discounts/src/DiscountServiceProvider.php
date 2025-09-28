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
        // Allow publishing of config
        $this->publishes([
            __DIR__ . '/../config/discounts.php' => config_path('discounts.php'),
        ], 'config');

        // Allow publishing of migrations for apps that want them copied
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // IMPORTANT: do NOT auto-load package migrations while running tests.
        // This prevents unpredictable ordering/duplicates with Testbench.
        if (! $this->app->environment('testing')) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
}

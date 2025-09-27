<?php

namespace Rahul\Discounts;

use Illuminate\Support\ServiceProvider;

class DiscountServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../config/discounts.php', 'discounts');
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/discounts.php' => config_path('discounts.php'),
        ], 'config');

        // Publish migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Events and other stuff can be registered here
    }
}

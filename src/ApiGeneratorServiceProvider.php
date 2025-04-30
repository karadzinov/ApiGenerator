<?php

namespace MartinK\ApiGenerator;

use Illuminate\Support\ServiceProvider;

class ApiGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\GenerateApiCommand::class,
                // Other commands here
            ]);
        }
    }

    public function boot()
    {
        // Load routes, migrations, etc.
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/api-generator.php' => config_path('api-generator.php'),
        ], 'config');
    }
}


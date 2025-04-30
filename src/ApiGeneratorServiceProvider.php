<?php

namespace MartinK\ApiGenerator;

use Illuminate\Support\ServiceProvider;

class ApiGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register the commands only if the application is running in the console
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\GenerateApiCommand::class,
                Commands\GenerateCrudCommand::class,
            ]);
        }
    }

    public function boot()
    {
        // Loading routes, migrations, and configuration publishing
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../config/api-generator.php' => config_path('api-generator.php'),
        ], 'config');
    }
}

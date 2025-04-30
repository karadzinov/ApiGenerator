<?php

namespace MartinK\ApiGenerator\Commands;

use Illuminate\Console\Command;

class GenerateApiCommand extends Command
{
    protected $signature = 'generate:api {model}';
    protected $description = 'Generate API resources, controllers, routes, etc. for a model';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $model = ucfirst($this->argument('model'));

        // Logic to generate Model, Controller, Migration, Resource, Routes, etc.
        $this->info("Generating API for model: $model");

        // Example: Generate controller, migration, etc.
        $this->call('make:model', ['name' => $model]);
        $this->call('make:controller', ['name' => "{$model}Controller"]);

        // Add more logic to create migrations, routes, etc. as needed.
    }
}


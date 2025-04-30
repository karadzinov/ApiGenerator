<?php

namespace MartinK\ApiGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str; // <-- Import Str class
use Illuminate\Support\Facades\File;

class GenerateApiCommand extends Command
{
    protected $signature = 'generate:api {model} {--fields=} {--relationships=}';
    protected $description = 'Generate a full CRUD API for the given model';

    protected $modelName;
    protected $modelNameLower;
    protected $fields;
    protected $relationships;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->modelName = ucfirst($this->argument('model'));
        $this->modelNameLower = strtolower($this->argument('model'));

        $this->fields = $this->option('fields');
        $this->relationships = $this->option('relationships');

        $this->generateController();
        $this->generateRequests();
        $this->generateService();
        $this->generateRoutes();
    }

    protected function generateController()
    {
        $controllerContent = <<<EOT
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\\{$this->modelName}StoreRequest;
use App\Http\Requests\\{$this->modelName}UpdateRequest;
use App\Models\\{$this->modelName};
use App\Services\\{$this->modelName}Service;

class {$this->modelName}Controller extends Controller
{
    public function index()
    {
        return {$this->modelName}::all();
    }

    public function store({$this->modelName}StoreRequest \$request)
    {
        return {$this->modelName}::create(\$request->validated());
    }

    public function show({$this->modelName} \$model)
    {
        return \$model;
    }

    public function update({$this->modelName}UpdateRequest \$request, {$this->modelName} \$model)
    {
        \$model->update(\$request->validated());
        return \$model;
    }

    public function destroy({$this->modelName} \$model)
    {
        \$model->delete();
        return response()->noContent();
    }
}
EOT;

        $controllerPath = app_path("Http/Controllers/Api/{$this->modelName}Controller.php");
        File::put($controllerPath, $controllerContent);
        $this->info("{$this->modelName}Controller generated successfully!");
    }

    protected function generateRequests()
    {
        // Store Request
        $storeRequestTemplate = "<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$this->modelName}StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // Add more fields here dynamically from the --fields option
        ];
    }
}
";
        file_put_contents(app_path("Http/Requests/{$this->modelName}StoreRequest.php"), $storeRequestTemplate);

        // Update Request
        $updateRequestTemplate = "<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$this->modelName}UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            // Add more fields here dynamically from the --fields option
        ];
    }
}
";
        file_put_contents(app_path("Http/Requests/{$this->modelName}UpdateRequest.php"), $updateRequestTemplate);

        $this->info("{$this->modelName} requests generated successfully!");
    }

    protected function generateService()
    {
        $serviceContent = "<?php

namespace App\Services;

use App\Models\\{$this->modelName};

class {$this->modelName}Service
{
    public function index()
    {
        return {$this->modelName}::all();
    }

    public function store(array \$data)
    {
        return {$this->modelName}::create(\$data);
    }

    public function update({$this->modelName} \$model, array \$data)
    {
        \$model->update(\$data);
        return \$model;
    }

    public function destroy({$this->modelName} \$model)
    {
        \$model->delete();
    }
}
";
        file_put_contents(app_path("Services/{$this->modelName}Service.php"), $serviceContent);
        $this->info("{$this->modelName} service generated successfully!");
    }

    protected function generateRoutes()
    {
        $routesPath = base_path('routes/api.php');
        $modelNamePlural = Str::plural(strtolower($this->modelName)); // <-- Corrected usage of Str::plural
        $routeLine = "    Route::apiResource('$modelNamePlural', \\App\\Http\\Controllers\\Api\\{$this->modelName}Controller::class)->names('api.{$modelNamePlural}');";

        $fileContent = file($routesPath);
        $insideGroup = false;
        $newContent = [];

        foreach ($fileContent as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, 'Route::prefix(')) {
                $insideGroup = true;
            }

            if ($insideGroup && str_contains($line, 'Route::prefix(') && !str_contains($line, $routeLine)) {
                $newContent[] = $routeLine . "\n";
                $this->info("API route for {$this->modelName} added successfully!");
            }

            $newContent[] = $line;
        }

        file_put_contents($routesPath, implode('', $newContent));
    }
}

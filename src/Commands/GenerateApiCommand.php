<?php

namespace MartinK\ApiGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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

        $this->generateModel();
        $this->generateController();
        $this->generateRequests();
        $this->generateService();
        $this->generateRoutes();
    }

    protected function parseFields(): array
    {
        $fields = [];

        if ($this->fields) {
            $pairs = explode(',', $this->fields);
            foreach ($pairs as $pair) {
                [$name, $type] = explode(':', $pair);
                $fields[$name] = $type ?? 'string';
            }
        }

        return $fields;
    }

    protected function generateModel()
    {
        $modelPath = app_path("Models/{$this->modelName}.php");

        if (File::exists($modelPath)) {
            $this->warn("Model {$this->modelName} already exists. Skipping.");
            return;
        }

        $fieldsArray = array_keys($this->parseFields());
        $fillableCode = "protected \$fillable = ['" . implode("', '", $fieldsArray) . "'];";

        $modelContent = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$this->modelName} extends Model
{
    use HasFactory;

    {$fillableCode}
}
PHP;

        File::ensureDirectoryExists(app_path('Models'));
        File::put($modelPath, $modelContent);
        $this->info("Model created: {$this->modelName}");

        // Create migration
        $table = Str::snake(Str::plural($this->modelName));
        $this->callSilent('make:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
        $this->info("Migration created for table: {$table}");
    }

    protected function generateController()
    {
        File::ensureDirectoryExists(app_path('Http/Controllers/Api'));

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
        $this->info("Controller created: {$this->modelName}Controller");
    }

    protected function generateRequests()
    {
        File::ensureDirectoryExists(app_path('Http/Requests'));

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

        $this->info("Requests created: {$this->modelName}StoreRequest, {$this->modelName}UpdateRequest");
    }

    protected function generateService()
    {
        File::ensureDirectoryExists(app_path('Services'));

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
        $this->info("Service created: {$this->modelName}Service");
    }

    protected function generateRoutes()
    {
        $routesPath = base_path('routes/api.php');

        // Check if the api.php file exists
        if (!File::exists($routesPath)) {
            // If the file doesn't exist, create it with the basic structure
            File::put($routesPath, "<?php\n\nuse Illuminate\Http\Request;\nuse Illuminate\Support\Facades\Route;\n\n");
            $this->info("API routes file created at: {$routesPath}");
        }

        $modelNamePlural = Str::plural(strtolower($this->modelName)); // <-- Corrected usage of Str::plural
        $routeLine = "    Route::apiResource('$modelNamePlural', \\App\\Http\\Controllers\\Api\\{$this->modelName}Controller::class)->names('api.{$modelNamePlural}');";

        // Now append the route to the existing or newly created file
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

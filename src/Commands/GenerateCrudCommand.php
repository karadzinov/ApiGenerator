<?php

namespace MartinK\ApiGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
    protected $signature = 'generate:crud {model} {--fields=} {--relationships=}';
    protected $description = 'Generate a full CRUD stack (Model, Migration, Controller, Requests, Resource, Service, Routes)';

    protected $modelName;
    protected $modelNameLower;
    protected $fields = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->modelName = ucfirst($this->argument('model'));
        $this->modelNameLower = strtolower($this->argument('model'));
        $this->fields = explode(',', $this->option('fields'));

        $this->generateModel();
        $this->generateMigration();
        $this->generateController();
        $this->generateRequests();
        $this->generateResource();
        $this->generateService();
        $this->generateRoutes();
    }

    protected function generateModel()
    {
        $modelContent = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$this->modelName} extends Model
{
    use HasFactory;

    protected \$fillable = [";

        foreach ($this->fields as $field) {
            $fieldData = explode(':', $field);
            $modelContent .= "\n        '{$fieldData[0]}',";
        }

        $modelContent .= "\n    ];\n";

        // Handling relationships
        if ($relationships = $this->option('relationships')) {
            $relationships = explode(',', $relationships);
            foreach ($relationships as $relationship) {
                $relationshipData = explode(':', $relationship);
                if ($relationshipData[1] === 'belongsTo') {
                    $modelContent .= "\n    public function {$relationshipData[0]}() {\n        return \$this->belongsTo(App\Models\\{$relationshipData[0]}::class);\n    }\n";
                } elseif ($relationshipData[1] === 'hasMany') {
                    $modelContent .= "\n    public function {$relationshipData[0]}() {\n        return \$this->hasMany(App\Models\\{$relationshipData[0]}::class);\n    }\n";
                }
            }
        }

        $modelContent .= "}\n";

        $modelPath = app_path("Models/{$this->modelName}.php");
        File::put($modelPath, $modelContent);
        $this->info("Model {$this->modelName} generated successfully!");
    }

    protected function generateMigration()
    {
        $migrationName = 'create_' . Str::snake($this->modelNameLower) . '_table';
        $migrationPath = database_path('migrations/' . date('Y_m_d_His') . "_{$migrationName}.php");

        $migrationContent = "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create{$this->modelName}Table extends Migration
{
    public function up()
    {
        Schema::create('{$this->modelNameLower}s', function (Blueprint \$table) {";

        foreach ($this->fields as $field) {
            $fieldData = explode(':', $field);
            $migrationContent .= "\n            \$table->{$fieldData[1]}('{$fieldData[0]}');";
        }

        $migrationContent .= "\n            \$table->timestamps();\n        });\n    }\n\n    public function down()\n    {\n        Schema::dropIfExists('{$this->modelNameLower}s');\n    }\n}";

        File::put($migrationPath, $migrationContent);
        $this->info("Migration for {$this->modelName} generated successfully!");
    }

    protected function generateController()
    {
        $namespace = 'App\Http\Controllers\Api';
        $controllerPath = app_path("Http/Controllers/Api/{$this->modelName}Controller.php");

        $controllerTemplate = <<<EOT
<?php

namespace {$namespace};

use App\Http\Controllers\Controller;
use App\Http\Requests\\{$this->modelName}StoreRequest;
use App\Http\Requests\\{$this->modelName}UpdateRequest;
use App\Http\Resources\\{$this->modelName}Resource;
use App\Models\\{$this->modelName};
use App\Services\\{$this->modelName}Service;
use Illuminate\Http\Request;

class {$this->modelName}Controller extends Controller
{
    protected \$service;

    public function __construct({$this->modelName}Service \$service)
    {
        \$this->service = \$service;
    }

    public function index()
    {
        return {$this->modelName}Resource::collection(\$this->service->index());
    }

    public function store({$this->modelName}StoreRequest \$request)
    {
        \$model = \$this->service->store(\$request->validated());
        return new {$this->modelName}Resource(\$model);
    }

    public function show({$this->modelName} \${$this->modelNameLower})
    {
        return new {$this->modelName}Resource(\${$this->modelNameLower});
    }

    public function update({$this->modelName}UpdateRequest \$request, {$this->modelName} \${$this->modelNameLower})
    {
        \$updatedModel = \$this->service->update(\${$this->modelNameLower}, \$request->validated());
        return new {$this->modelName}Resource(\$updatedModel);
    }

    public function destroy({$this->modelName} \${$this->modelNameLower})
    {
        \$this->service->destroy(\${$this->modelNameLower});
        return response()->noContent();
    }
}
EOT;

        file_put_contents($controllerPath, $controllerTemplate);
        $this->info("Controller {$this->modelName}Controller generated successfully!");
    }

    protected function generateRequests()
    {
        $requestsPath = app_path('Http/Requests');

        // Store Request
        $storeRequestTemplate = "<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$this->modelName}StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
" . collect($this->fields)->map(function ($field) {
                [$name, $type] = explode(':', $field);
                $rule = $type === 'email' ? 'email' : 'string';
                return "            '$name' => 'required|$rule',";
            })->implode("\n") . "
        ];
    }
}
";
        file_put_contents("{$requestsPath}/{$this->modelName}StoreRequest.php", $storeRequestTemplate);
        $this->info("Store Request for {$this->modelName} generated successfully!");

        // Update Request
        $updateRequestTemplate = "<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$this->modelName}UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
" . collect($this->fields)->map(function ($field) {
                [$name, $type] = explode(':', $field);
                $rule = $type === 'email' ? 'email' : 'string';
                return "            '$name' => 'sometimes|$rule',";
            })->implode("\n") . "
        ];
    }
}
";
        file_put_contents("{$requestsPath}/{$this->modelName}UpdateRequest.php", $updateRequestTemplate);
        $this->info("Update Request for {$this->modelName} generated successfully!");
    }

    protected function generateResource()
    {
        $resourceContent = "<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class {$this->modelName}Resource extends JsonResource
{
    public function toArray(\$request)
    {
        return parent::toArray(\$request);
    }
}
";

        $resourcePath = app_path("Http/Resources/{$this->modelName}Resource.php");
        File::put($resourcePath, $resourceContent);
        $this->info("Resource {$this->modelName}Resource generated successfully!");
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

        $servicePath = app_path("Services/{$this->modelName}Service.php");
        File::put($servicePath, $serviceContent);
        $this->info("Service {$this->modelName}Service generated successfully!");
    }

    protected function generateRoutes()
    {
        $routesPath = base_path('routes/api.php');
        $modelNamePlural = \Str::plural(strtolower($this->modelName));
        $routeLine = "    Route::apiResource('$modelNamePlural', \\App\\Http\\Controllers\\Api\\{$this->modelName}Controller::class)->names('api.{$modelNamePlural}');";

        $routeGroupStart = "Route::prefix('v1')->middleware('auth:api')->group(function () {";
        $routeGroupEnd = "});";

        $fileContent = file($routesPath);

        $insideGroup = false;
        $newContent = [];

        foreach ($fileContent as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, $routeGroupStart)) {
                $insideGroup = true;
            }

            if ($insideGroup && trim($line) === $routeGroupEnd) {
                // Before ending the group, insert route if not already present
                if (!Str::contains(implode('', $fileContent), $routeLine)) {
                    $newContent[] = $routeLine . "\n";
                    $this->info("API route for {$this->modelName} added successfully!");
                }
            }

            $newContent[] = $line;
        }

        // Write back to file
        file_put_contents($routesPath, implode('', $newContent));
    }

}

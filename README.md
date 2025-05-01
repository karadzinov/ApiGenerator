# ApiGenerator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/martink/apigenerator.svg?style=flat-square)](https://packagist.org/packages/martink/apigenerator)
[![Total Downloads](https://img.shields.io/packagist/dt/martink/apigenerator.svg?style=flat-square)](https://packagist.org/packages/martink/apigenerator)
[![License](https://img.shields.io/github/license/karadzinov/ApiGenerator.svg?style=flat-square)](https://github.com/karadzinov/ApiGenerator/blob/main/LICENSE)

ApiGenerator is a Laravel package that helps you generate a full CRUD stack (Model, Migration, Controller, Requests, Resource, Service, and API routes) and API scaffolding with a single Artisan command.

## 🚀 Installation

You can install the package via Composer:

```bash
composer require martink/apigenerator --dev
```

If Laravel doesn't auto-discover the service provider, add it manually in config/app.php:
```bash
'providers' => [
    MartinK\ApiGenerator\ApiGeneratorServiceProvider::class,
],
```

📦 Publish (if needed)
```bash
php artisan vendor:publish --provider="MartinK\ApiGenerator\ApiGeneratorServiceProvider"
```

⚙️ Usage
Generate a Full CRUD Stack

```bash
php artisan generate:crud ModelName --fields="title:string,description:text" --relationships="user:belongsTo"
```

This will generate:

app/Models/ModelName.php

database/migrations/xxxx_xx_xx_create_modelname_table.php

app/Http/Controllers/Api/ModelNameController.php

app/Http/Requests/ModelNameStoreRequest.php

app/Http/Requests/ModelNameUpdateRequest.php

app/Http/Resources/ModelNameResource.php

app/Services/ModelNameService.php

Route entry in routes/api.php

Generate API from Existing Models
If your model already exists and includes $fillable, you can use:

```bash
php artisan generate:api
```
This command will loop through all models in app/Models and auto-generate:

Controllers

Resources

Services

Routes

🧪 Example
```bash
php artisan generate:crud Album --fields="name:string,coverImg:string,restaurant_id:foreignId" --relationships="restaurant:belongsTo,pictures:hasMany"
```
🗂 Generated Controller Example
```bash
public function update(AlbumUpdateRequest $request, Album $album)
{
    $updated = $this->service->update($album, $request->validated());
    return new AlbumResource($updated);
}
```

✅ Requirements
PHP ^7.4|^8.0

Laravel 8 or 9+

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

Developed by Martin Karadzinov


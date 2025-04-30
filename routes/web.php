<?php

use Illuminate\Support\Facades\Route;

Route::get('generate-api/{model}', [ApiGeneratorController::class, 'generateApi']);


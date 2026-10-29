<?php

use Illuminate\Support\Facades\Route;
use Modules\ExampleModule\Http\Controllers\ExampleModuleController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('examplemodules', ExampleModuleController::class)->names('examplemodule');
});

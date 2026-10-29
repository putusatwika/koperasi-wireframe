<?php

use Illuminate\Support\Facades\Route;
use Modules\ExampleModule\Http\Controllers\ExampleModuleController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('examplemodules', ExampleModuleController::class)->names('examplemodule');
});

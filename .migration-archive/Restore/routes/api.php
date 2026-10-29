<?php

use Illuminate\Support\Facades\Route;
use Modules\Restore\Http\Controllers\RestoreController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('restores', RestoreController::class)->names('restore');
});

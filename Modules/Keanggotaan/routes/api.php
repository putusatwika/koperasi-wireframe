<?php

use Illuminate\Support\Facades\Route;
use Modules\Keanggotaan\Http\Controllers\KeanggotaanController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('keanggotaans', KeanggotaanController::class)->names('keanggotaan');
});

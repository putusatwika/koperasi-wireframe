<?php

use Illuminate\Support\Facades\Route;
use Modules\Keanggotaan\Http\Controllers\KeanggotaanController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('keanggotaans', KeanggotaanController::class)->names('keanggotaan');
});

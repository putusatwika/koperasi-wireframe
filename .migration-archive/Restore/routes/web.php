<?php

use Illuminate\Support\Facades\Route;
use Modules\Restore\Http\Controllers\RestoreController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('restores', RestoreController::class)->names('restore');
});

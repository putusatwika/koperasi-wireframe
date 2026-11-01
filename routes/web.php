<?php

use App\Http\Controllers\Reports\LabaRugiExcelController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('reports/laba-rugi/excel', LabaRugiExcelController::class)
        ->name('reports.laba-rugi.excel');
});

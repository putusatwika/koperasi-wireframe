<?php

use Illuminate\Support\Facades\Route;
use Modules\Peminjaman\Http\Controllers\KartuPiutangCetakController;
use Modules\Peminjaman\Http\Controllers\PeminjamanController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('module/peminjaman')->name('module.peminjaman.')->group(function () {
        Route::resource('peminjamen', PeminjamanController::class);
        Route::get('kartu-piutang/{peminjaman}/cetak', KartuPiutangCetakController::class)
            ->name('kartu-piutang.cetak');
    });
});

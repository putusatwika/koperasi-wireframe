<?php

namespace Modules\Peminjaman\Filament\Resources\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class PemeriksaanPeminjamanTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(PeminjamanTableColumns::columns())
            ->filters(PeminjamanTableColumns::filters())
            ->recordActions([
                ViewAction::make()->authorize('peminjaman.pemeriksaan.view'),
            ]);
    }
}

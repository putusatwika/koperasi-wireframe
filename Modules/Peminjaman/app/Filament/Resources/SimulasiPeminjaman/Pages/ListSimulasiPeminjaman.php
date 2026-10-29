<?php

namespace Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjaman;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjamanAnggota;
use Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\SimulasiPeminjamanResource;

class ListSimulasiPeminjaman extends ListRecords
{
    protected static string $resource = SimulasiPeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ajukanPeminjaman')
                ->label('Ajukan Pinjaman')
                ->icon('heroicon-o-plus')
                ->authorize('peminjaman.pengajuan.create')
                ->url(AjukanPeminjaman::getUrl()),
            Action::make('ajukanPeminjamanAnggota')
                ->label('Ajukan Pinjaman Anggota')
                ->icon('heroicon-o-user-plus')
                ->authorize('peminjaman.pengajuan.create_any')
                ->url(AjukanPeminjamanAnggota::getUrl()),
        ];
    }
}

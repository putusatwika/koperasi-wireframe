<?php

namespace Modules\Peminjaman\Filament\Resources\DaftarPeminjamans\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjaman;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjamanAnggota;
use Modules\Peminjaman\Filament\Resources\DaftarPeminjamans\DaftarPeminjamanResource;

class ListDaftarPeminjamans extends ListRecords
{
    protected static string $resource = DaftarPeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buatPengajuan')
                ->label('Buat Pengajuan')
                ->icon('heroicon-o-plus')
                ->authorize('peminjaman.pengajuan.create')
                ->url(AjukanPeminjaman::getUrl()),
            Action::make('buatPengajuanAnggota')
                ->label('Buat Pengajuan Anggota')
                ->icon('heroicon-o-user-plus')
                ->authorize('peminjaman.pengajuan.create_any')
                ->url(AjukanPeminjamanAnggota::getUrl()),
        ];
    }
}

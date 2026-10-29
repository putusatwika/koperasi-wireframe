<?php

namespace Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjaman;
use Modules\Peminjaman\Filament\Pages\AjukanPeminjamanAnggota;
use Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\PengajuanPeminjamanResource;

class ListPengajuanPeminjamans extends ListRecords
{
    protected static string $resource = PengajuanPeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buatPengajuan')
                ->label('Buat Pengajuan')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->authorize('peminjaman.pengajuan.create')
                ->url(AjukanPeminjaman::getUrl()),
            Action::make('buatPengajuanAnggota')
                ->label('Buat Pengajuan Anggota')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->authorize('peminjaman.pengajuan.create_any')
                ->url(AjukanPeminjamanAnggota::getUrl()),
        ];
    }
}

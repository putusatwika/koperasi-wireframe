<?php

namespace Modules\Simpanan\Filament\Resources\SimpananAnggotas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Modules\Simpanan\Enums\KategoriSimpanan;

class SimpananAnggotaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('nomor_rekening')->label('No. Rekening'),
            TextEntry::make('anggota.member_number')->label('No. Anggota'),
            TextEntry::make('anggota.user.name')->label('Nama Anggota'),
            TextEntry::make('jenisSimpanan.nama')->label('Produk'),
            TextEntry::make('jenisSimpanan.kategori')
                ->label('Kategori')
                ->badge()
                ->formatStateUsing(fn (KategoriSimpanan|string $state): string => $state instanceof KategoriSimpanan ? $state->name : $state),
            TextEntry::make('saldo')->label('Saldo')->money('IDR'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('dibuka_pada')->label('Dibuka')->date(),
            TextEntry::make('jatuh_tempo')->label('Jatuh Tempo')->date()->placeholder('-'),
        ])->columns(2);
    }
}

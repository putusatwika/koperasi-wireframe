<?php

namespace Modules\Keuangan\Filament\Resources\TransaksiKeuangans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Modules\Keuangan\Enums\TipeTransaksiKeuangan;

class TransaksiKeuanganInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('nomor')->label('Nomor'),
            TextEntry::make('tanggal')->label('Tanggal')->date('d/m/Y'),
            TextEntry::make('kategori.nama')->label('Kategori'),
            TextEntry::make('tipe')
                ->label('Tipe')
                ->badge()
                ->formatStateUsing(fn (TipeTransaksiKeuangan|string $state): string => $state instanceof TipeTransaksiKeuangan ? $state->name : $state),
            TextEntry::make('nominal')->label('Nominal')->money('IDR'),
            TextEntry::make('keterangan')->label('Keterangan')->placeholder('-'),
            TextEntry::make('referensi')->label('Nomor Referensi')->placeholder('-'),
            TextEntry::make('lampiran')->label('Lampiran')->placeholder('-'),
            TextEntry::make('createdBy.name')->label('Dicatat Oleh')->placeholder('-'),
        ])->columns(2);
    }
}

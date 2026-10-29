<?php

namespace Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;

class SimulasiPeminjamanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('jenisPinjaman.kode')->label('Kode Jenis')->default('Custom'),
                TextEntry::make('jenisPinjaman.nama')->label('Jenis Pinjaman')->default('Custom'),
                TextEntry::make('anggota.user.name')->label('Anggota')->default('-'),
                TextEntry::make('jumlah_pinjaman')->label('Jumlah Pinjaman')->money('IDR'),
                TextEntry::make('metode_bunga')
                    ->label('Metode Bunga')
                    ->formatStateUsing(fn (JenisBungaPeminjaman|string|null $state): string => match ($state instanceof JenisBungaPeminjaman ? $state->value : $state) {
                        'flat' => 'Flat',
                        'effective' => 'Efektif',
                        'annuity' => 'Anuitas',
                        default => '-',
                    }),
                TextEntry::make('persentase_bunga_per_bulan')->label('Bunga per Bulan')->suffix('%'),
                TextEntry::make('tenor_bulan')->label('Tenor')->suffix(' bulan'),
                TextEntry::make('peruntukan')->label('Peruntukan'),
                TextEntry::make('rencana_mulai_cicilan')->label('Rencana Mulai Cicilan')->date('F Y'),
                TextEntry::make('created_at')->label('Dibuat')->dateTime(),
            ]);
    }
}

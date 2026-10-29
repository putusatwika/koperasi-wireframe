<?php

namespace Modules\Peminjaman\Filament\Resources\JenisPinjaman\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;

class JenisPinjamanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('kode')->label('Kode'),
                TextEntry::make('nama')->label('Nama'),
                TextEntry::make('metode_bunga')
                    ->label('Metode Perhitungan Bunga')
                    ->formatStateUsing(fn (JenisBungaPeminjaman|string|null $state): string => match ($state instanceof JenisBungaPeminjaman ? $state->value : $state) {
                        'flat' => 'Flat',
                        'effective' => 'Efektif',
                        'annuity' => 'Anuitas',
                        default => '-',
                    }),
                TextEntry::make('persentase_bunga_per_bulan')
                    ->label('Persentase Bunga per Bulan')
                    ->suffix('%'),
                TextEntry::make('created_at')->label('Dibuat')->dateTime(),
                TextEntry::make('updated_at')->label('Diperbarui')->dateTime(),
            ]);
    }
}

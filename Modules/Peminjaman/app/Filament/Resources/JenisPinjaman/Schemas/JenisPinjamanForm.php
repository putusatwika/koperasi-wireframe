<?php

namespace Modules\Peminjaman\Filament\Resources\JenisPinjaman\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;

class JenisPinjamanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode')
                    ->label('Kode')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('nama')
                    ->label('Nama')
                    ->required()
                    ->maxLength(150),
                Select::make('metode_bunga')
                    ->label('Metode Perhitungan Bunga')
                    ->options([
                        JenisBungaPeminjaman::Flat->value => 'Flat',
                        JenisBungaPeminjaman::Effective->value => 'Efektif',
                        JenisBungaPeminjaman::Annuity->value => 'Anuitas',
                    ])
                    ->required(),
                TextInput::make('persentase_bunga_per_bulan')
                    ->label('Persentase Bunga per Bulan (%)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('biaya_administrasi')
                    ->label('Biaya Administrasi')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('biaya_provisi_persen')
                    ->label('Biaya Provisi')
                    ->suffix('% dari plafon')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('denda_keterlambatan_persen')
                    ->label('Denda Keterlambatan')
                    ->suffix('% per hari')
                    ->numeric()
                    ->minValue(0)
                    ->default(0.1),
                TextInput::make('penalti_pelunasan_persen')
                    ->label('Penalti Pelunasan Dipercepat')
                    ->suffix('% dari sisa pokok')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }
}

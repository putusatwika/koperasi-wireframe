<?php

namespace Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;

class SimulasiPeminjamanTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jenisPinjaman.kode')
                    ->label('Kode Jenis')
                    ->default('Custom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('anggota.user.name')
                    ->label('Anggota')
                    ->default('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jumlah_pinjaman')
                    ->label('Jumlah Pinjaman')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('metode_bunga')
                    ->label('Metode Bunga')
                    ->formatStateUsing(fn (JenisBungaPeminjaman|string|null $state): string => match ($state instanceof JenisBungaPeminjaman ? $state->value : $state) {
                        'flat' => 'Flat',
                        'effective' => 'Efektif',
                        'annuity' => 'Anuitas',
                        default => '-',
                    }),
                TextColumn::make('persentase_bunga_per_bulan')
                    ->label('Bunga per Bulan')
                    ->suffix('%'),
                TextColumn::make('tenor_bulan')
                    ->label('Tenor')
                    ->suffix(' bulan')
                    ->sortable(),
                TextColumn::make('peruntukan')
                    ->label('Peruntukan')
                    ->searchable(),
                TextColumn::make('rencana_mulai_cicilan')
                    ->label('Mulai Cicilan')
                    ->date('F Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->authorize('peminjaman.simulasi_pinjaman.view'),
            ]);
    }
}

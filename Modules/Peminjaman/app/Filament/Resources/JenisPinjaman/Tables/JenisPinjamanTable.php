<?php

namespace Modules\Peminjaman\Filament\Resources\JenisPinjaman\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;

class JenisPinjamanTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('metode_bunga')
                    ->label('Metode Bunga')
                    ->badge()
                    ->formatStateUsing(fn (JenisBungaPeminjaman|string $state): string => match ($state instanceof JenisBungaPeminjaman ? $state->value : $state) {
                        'flat' => 'Flat',
                        'effective' => 'Efektif',
                        'annuity' => 'Anuitas',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('persentase_bunga_per_bulan')
                    ->label('Bunga per Bulan')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('biaya_administrasi')
                    ->label('Admin')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('biaya_provisi_persen')
                    ->label('Provisi')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('denda_keterlambatan_persen')
                    ->label('Denda/Hari')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('penalti_pelunasan_persen')
                    ->label('Penalti')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make()->authorize('peminjaman.jenis_pinjaman.view'),
                EditAction::make()->authorize('peminjaman.jenis_pinjaman.update'),
                DeleteAction::make()->authorize('peminjaman.jenis_pinjaman.delete'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorize('peminjaman.jenis_pinjaman.delete'),
                ]),
            ]);
    }
}

<?php

namespace Modules\Keuangan\Filament\Resources\TransaksiKeuangans\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Keuangan\Enums\TipeTransaksiKeuangan;

class TransaksiKeuanganTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor')->label('Nomor')->searchable(),
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y')->sortable(),
                TextColumn::make('kategori.nama')->label('Kategori')->sortable(),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (TipeTransaksiKeuangan|string $state): string => $state instanceof TipeTransaksiKeuangan ? $state->name : $state),
                TextColumn::make('nominal')->label('Nominal')->money('IDR')->sortable(),
                TextColumn::make('keterangan')->label('Keterangan')->limit(40)->toggleable(),
                TextColumn::make('referensi')->label('Referensi')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options(collect(TipeTransaksiKeuangan::cases())->mapWithKeys(
                        fn (TipeTransaksiKeuangan $tipe): array => [$tipe->value => $tipe->name],
                    )->all()),
            ])
            ->defaultSort('tanggal', 'desc')
            ->recordActions([
                ViewAction::make()->authorize('keuangan.transaksi.view'),
            ]);
    }
}

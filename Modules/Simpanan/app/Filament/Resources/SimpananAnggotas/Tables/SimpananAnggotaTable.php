<?php

namespace Modules\Simpanan\Filament\Resources\SimpananAnggotas\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Simpanan\Enums\KategoriSimpanan;
use Modules\Simpanan\Enums\PeriodikSimpanan;

class SimpananAnggotaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_rekening')->label('No. Rekening')->searchable(),
                TextColumn::make('anggota.user.name')
                    ->label('Anggota')
                    ->searchable(),
                TextColumn::make('anggota.member_number')->label('No. Anggota')->searchable(),
                TextColumn::make('jenisSimpanan.nama')->label('Produk')->sortable(),
                TextColumn::make('jenisSimpanan.kategori')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (KategoriSimpanan|string $state): string => $state instanceof KategoriSimpanan ? $state->name : $state),
                TextColumn::make('saldo')->label('Saldo')->money('IDR')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('dibuka_pada')->label('Dibuka')->date(),
                TextColumn::make('jatuh_tempo')->label('Jatuh Tempo')->date()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(collect(KategoriSimpanan::cases())->mapWithKeys(
                        fn (KategoriSimpanan $kategori): array => [$kategori->value => $kategori->name],
                    )->all())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('jenisSimpanan', fn ($q) => $q->where('kategori', $data['value']))
                        : $query),
                SelectFilter::make('periodik')
                    ->label('Periodik')
                    ->options(collect(PeriodikSimpanan::cases())->mapWithKeys(
                        fn (PeriodikSimpanan $periodik): array => [$periodik->value => $periodik->name],
                    )->all())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('jenisSimpanan', fn ($q) => $q->where('periodik', $data['value']))
                        : $query),
            ])
            ->recordActions([
                ViewAction::make()->authorize('simpanan.simpanan.view'),
            ]);
    }
}

<?php

namespace Modules\Keuangan\Filament\Resources\KategoriKeuangans\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Keuangan\Enums\TipeKategoriKeuangan;

class KategoriKeuanganTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (TipeKategoriKeuangan|string $state): string => $state instanceof TipeKategoriKeuangan ? $state->name : $state),
                TextColumn::make('coaAccount.code')->label('COA')->placeholder('-'),
                TextColumn::make('is_active')
                    ->label('Aktif')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif'),
            ])
            ->recordActions([
                EditAction::make()->authorize('keuangan.kategori.update'),
                DeleteAction::make()->authorize('keuangan.kategori.delete'),
            ]);
    }
}

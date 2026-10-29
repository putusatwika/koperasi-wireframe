<?php

namespace Modules\Simpanan\Filament\Resources\JenisSimpanan\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Simpanan\Enums\KategoriSimpanan;
use Modules\Simpanan\Enums\PeriodikSimpanan;
use Modules\Simpanan\Models\JenisSimpanan;

class JenisSimpananTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (KategoriSimpanan|string $state): string => $state instanceof KategoriSimpanan ? $state->name : $state),
                TextColumn::make('nominal_default')->label('Nominal')->money('IDR')->sortable(),
                TextColumn::make('periodik')
                    ->label('Periodik')
                    ->badge()
                    ->formatStateUsing(fn (PeriodikSimpanan|string $state): string => $state instanceof PeriodikSimpanan ? $state->name : $state),
                TextColumn::make('berbunga')
                    ->label('Berbunga')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak'),
                TextColumn::make('bunga_per_bulan')->label('Bunga/Bulan')->suffix('%')->sortable(),
                TextColumn::make('tanggal_jatuh_tempo')->label('Tgl Jatuh Tempo'),
                TextColumn::make('is_active')
                    ->label('Aktif')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif'),
            ])
            ->recordActions([
                EditAction::make()->authorize('simpanan.jenis_simpanan.update'),
                Action::make('aturTanggalJatuhTempo')
                    ->label('Atur Tanggal')
                    ->icon('heroicon-o-calendar-days')
                    ->authorize('simpanan.jenis_simpanan.update_tanggal')
                    ->visible(fn (JenisSimpanan $record): bool => $record->periodik === PeriodikSimpanan::Monthly)
                    ->fillForm(fn (JenisSimpanan $record): array => [
                        'tanggal_jatuh_tempo' => $record->tanggal_jatuh_tempo,
                    ])
                    ->form([
                        TextInput::make('tanggal_jatuh_tempo')
                            ->label('Tanggal Jatuh Tempo (1-28)')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(28)
                            ->required(),
                    ])
                    ->action(fn (JenisSimpanan $record, array $data): bool => $record->update([
                        'tanggal_jatuh_tempo' => (int) $data['tanggal_jatuh_tempo'],
                    ])),
                DeleteAction::make()->authorize('simpanan.jenis_simpanan.delete'),
            ]);
    }
}

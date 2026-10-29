<?php

namespace Modules\Pembayaran\Filament\Resources\Histori\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Pembayaran\Enums\JenisPembayaran;

class PembayaranTable
{
    public static function configure(Table $table, bool $showMember = true, string $viewPermission = 'pembayaran.history.view'): Table
    {
        $columns = [
            TextColumn::make('payment_number')->label('Nomor Pembayaran')->searchable()->sortable(),
        ];

        if ($showMember) {
            $columns[] = TextColumn::make('anggota.user.name')->label('Anggota')->searchable()->sortable();
        }

        return $table
            ->columns([
                ...$columns,
                TextColumn::make('peminjaman.loan_number')->label('Nomor Pinjaman')->searchable()->sortable(),
                TextColumn::make('payment_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (JenisPembayaran $state): string => $state->name),
                TextColumn::make('payment_date')->label('Tanggal')->date('d/m/Y')->sortable(),
                TextColumn::make('amount')->label('Jumlah')->money('IDR')->sortable(),
                TextColumn::make('principal_amount')->label('Pokok')->money('IDR')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('interest_amount')->label('Bunga')->money('IDR')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('penalty_amount')->label('Denda')->money('IDR')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference_number')->label('Referensi')->toggleable(),
            ])
            ->defaultSort('payment_date', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->authorize($viewPermission)
                    ->modalWidth('3xl'),
            ]);
    }
}

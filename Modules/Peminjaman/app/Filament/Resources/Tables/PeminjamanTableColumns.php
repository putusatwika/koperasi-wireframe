<?php

namespace Modules\Peminjaman\Filament\Resources\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Modules\Peminjaman\Enums\StatusPeminjaman;

class PeminjamanTableColumns
{
    public static function columns(): array
    {
        return [
            TextColumn::make('loan_number')->label('Nomor Pinjaman')->searchable()->sortable(),
            TextColumn::make('anggota.user.name')->label('Anggota')->searchable()->sortable(),
            TextColumn::make('account_number')->label('Rekening')->searchable(),
            TextColumn::make('loan_amount')->label('Jumlah')->money('IDR')->sortable(),
            TextColumn::make('interest_type')->label('Bunga')->badge(),
            TextColumn::make('term_months')->label('Tenor')->suffix(' bln')->sortable(),
            TextColumn::make('status')->label('Status')->badge()->sortable(),
            TextColumn::make('rejection_stage')->label('Tahap Penolakan')->badge(),
            TextColumn::make('rejection_reason')->label('Pesan Penolakan')->limit(40)->toggleable(),
            TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function filters(): array
    {
        return [
            SelectFilter::make('status')->options(collect(StatusPeminjaman::cases())->mapWithKeys(fn (StatusPeminjaman $status): array => [$status->value => $status->name])->all()),
            TrashedFilter::make(),
        ];
    }
}

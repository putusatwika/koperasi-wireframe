<?php

namespace Modules\Pembayaran\Filament\Resources\HistoriAnggotas\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class HistoriAnggotaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member_number')->label('Nomor Anggota')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Nama Anggota')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Email')->searchable(),
                TextColumn::make('account_number')->label('Nomor Rekening')->searchable(),
                TextColumn::make('pembayarans_count')->label('Jumlah Pembayaran')->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()->authorize('pembayaran.history.viewAny'),
            ]);
    }
}

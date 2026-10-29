<?php

namespace Modules\Keanggotaan\Filament\Resources\Anggotas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AnggotasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member_number')->label('Nomor Anggota')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('account_number')->label('Nomor Rekening')->searchable(),
                TextColumn::make('phone')->label('Telepon'),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('joined_at')->label('Bergabung')->date()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorize('keanggotaan.anggota.delete'),
                    ForceDeleteBulkAction::make()->authorize('keanggotaan.anggota.delete'),
                    RestoreBulkAction::make()->authorize('keanggotaan.anggota.delete'),
                ]),
            ]);
    }
}

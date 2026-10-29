<?php

namespace Modules\Peminjaman\Filament\Resources\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class PengajuanPeminjamanTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(PeminjamanTableColumns::columns())
            ->filters(PeminjamanTableColumns::filters())
            ->recordActions([
                ViewAction::make()->authorize('peminjaman.pengajuan.view'),
                EditAction::make()->authorize('peminjaman.pengajuan.update'),
                DeleteAction::make()->authorize('peminjaman.pengajuan.delete'),
                ForceDeleteAction::make()->authorize('peminjaman.pengajuan.delete'),
                RestoreAction::make()->authorize('peminjaman.pengajuan.delete'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorize('peminjaman.pengajuan.delete'),
                    ForceDeleteBulkAction::make()->authorize('peminjaman.pengajuan.delete'),
                    RestoreBulkAction::make()->authorize('peminjaman.pengajuan.delete'),
                ]),
            ]);
    }
}

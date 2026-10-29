<?php

namespace Modules\Peminjaman\Filament\Resources\PersetujuanPencairans;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Filament\Resources\Peminjamen\Schemas\PeminjamanInfolist;
use Modules\Peminjaman\Filament\Resources\PersetujuanPencairans\Pages\ListPersetujuanPencairans;
use Modules\Peminjaman\Filament\Resources\PersetujuanPencairans\Pages\ViewPersetujuanPencairan;
use Modules\Peminjaman\Filament\Resources\Tables\PersetujuanPencairanTable;
use Modules\Peminjaman\Models\Peminjaman;
use UnitEnum;

class PersetujuanPencairanResource extends Resource
{
    protected static ?string $model = Peminjaman::class;

    protected static ?string $slug = 'persetujuan-pencairan';

    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static ?string $navigationLabel = 'Persetujuan dan Pencairan';

    protected static ?string $modelLabel = 'Persetujuan dan Pencairan';

    protected static ?string $pluralModelLabel = 'Persetujuan dan Pencairan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    public static function infolist(Schema $schema): Schema
    {
        return PeminjamanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersetujuanPencairanTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status', [StatusPeminjaman::ReadyForApproval, StatusPeminjaman::Approved])
            ->with(['anggota.user']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('peminjaman.persetujuan.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('peminjaman.persetujuan.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersetujuanPencairans::route('/'),
            'view' => ViewPersetujuanPencairan::route('/{record}'),
        ];
    }
}

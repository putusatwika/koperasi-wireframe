<?php

namespace Modules\Peminjaman\Filament\Resources\DaftarPeminjamans;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Peminjaman\Filament\Resources\DaftarPeminjamans\Pages\ListDaftarPeminjamans;
use Modules\Peminjaman\Filament\Resources\DaftarPeminjamans\Pages\ViewDaftarPeminjaman;
use Modules\Peminjaman\Filament\Resources\DaftarPeminjamans\RelationManagers\JadwalAngsuransRelationManager;
use Modules\Peminjaman\Filament\Resources\Peminjamen\Schemas\PeminjamanInfolist;
use Modules\Peminjaman\Filament\Resources\Tables\DaftarPeminjamanTable;
use Modules\Peminjaman\Models\Peminjaman;
use UnitEnum;

class DaftarPeminjamanResource extends Resource
{
    protected static ?string $model = Peminjaman::class;

    protected static ?string $slug = 'daftar-peminjaman';

    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static ?string $navigationLabel = 'Daftar Peminjaman';

    protected static ?string $modelLabel = 'Peminjaman';

    protected static ?string $pluralModelLabel = 'Daftar Peminjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    public static function infolist(Schema $schema): Schema
    {
        return PeminjamanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DaftarPeminjamanTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            JadwalAngsuransRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['anggota.user']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('peminjaman.daftar_peminjaman.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('peminjaman.daftar_peminjaman.view') ?? false;
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
            'index' => ListDaftarPeminjamans::route('/'),
            'view' => ViewDaftarPeminjaman::route('/{record}'),
        ];
    }
}

<?php

namespace Modules\Peminjaman\Filament\Resources\PemeriksaanPeminjamans;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Filament\Resources\PemeriksaanPeminjamans\Pages\ListPemeriksaanPeminjamans;
use Modules\Peminjaman\Filament\Resources\PemeriksaanPeminjamans\Pages\ViewPemeriksaanPeminjaman;
use Modules\Peminjaman\Filament\Resources\Peminjamen\Schemas\PeminjamanInfolist;
use Modules\Peminjaman\Filament\Resources\Tables\PemeriksaanPeminjamanTable;
use Modules\Peminjaman\Models\Peminjaman;
use UnitEnum;

class PemeriksaanPeminjamanResource extends Resource
{
    protected static ?string $model = Peminjaman::class;

    protected static ?string $slug = 'pemeriksaan-peminjaman';

    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static ?string $navigationLabel = 'Pemeriksaan Peminjaman';

    protected static ?string $modelLabel = 'Pemeriksaan Peminjaman';

    protected static ?string $pluralModelLabel = 'Pemeriksaan Peminjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    public static function infolist(Schema $schema): Schema
    {
        return PeminjamanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PemeriksaanPeminjamanTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status', [StatusPeminjaman::Submitted, StatusPeminjaman::InReview])
            ->with(['anggota.user']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('peminjaman.pemeriksaan.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('peminjaman.pemeriksaan.view') ?? false;
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
            'index' => ListPemeriksaanPeminjamans::route('/'),
            'view' => ViewPemeriksaanPeminjaman::route('/{record}'),
        ];
    }
}

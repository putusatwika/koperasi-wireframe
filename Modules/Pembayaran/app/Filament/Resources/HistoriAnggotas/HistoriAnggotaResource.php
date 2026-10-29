<?php

namespace Modules\Pembayaran\Filament\Resources\HistoriAnggotas;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Pembayaran\Filament\Resources\HistoriAnggotas\Pages\ListHistoriAnggotas;
use Modules\Pembayaran\Filament\Resources\HistoriAnggotas\Pages\ViewHistoriAnggota;
use Modules\Pembayaran\Filament\Resources\HistoriAnggotas\RelationManagers\PembayaransRelationManager;
use Modules\Pembayaran\Filament\Resources\HistoriAnggotas\Schemas\HistoriAnggotaInfolist;
use Modules\Pembayaran\Filament\Resources\HistoriAnggotas\Tables\HistoriAnggotaTable;
use UnitEnum;

class HistoriAnggotaResource extends Resource
{
    protected static ?string $model = Anggota::class;

    protected static ?string $slug = 'histori-anggota';

    protected static string|UnitEnum|null $navigationGroup = 'Pembayaran';

    protected static ?string $navigationLabel = 'Histori Anggota';

    protected static ?string $modelLabel = 'Anggota';

    protected static ?string $pluralModelLabel = 'Histori Anggota';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function infolist(Schema $schema): Schema
    {
        return HistoriAnggotaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HistoriAnggotaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PembayaransRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'pembayarans.peminjaman'])
            ->withCount('pembayarans');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('pembayaran.history.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('pembayaran.history.viewAny') ?? false;
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
            'index' => ListHistoriAnggotas::route('/'),
            'view' => ViewHistoriAnggota::route('/{record}'),
        ];
    }
}

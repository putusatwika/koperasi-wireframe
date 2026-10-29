<?php

namespace Modules\Simpanan\Filament\Resources\SimpananAnggotas;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Simpanan\Filament\Resources\SimpananAnggotas\Pages\ListSimpananAnggota;
use Modules\Simpanan\Filament\Resources\SimpananAnggotas\Pages\ViewSimpananAnggota;
use Modules\Simpanan\Filament\Resources\SimpananAnggotas\Schemas\SimpananAnggotaInfolist;
use Modules\Simpanan\Filament\Resources\SimpananAnggotas\Tables\SimpananAnggotaTable;
use Modules\Simpanan\Models\SimpananAkun;
use UnitEnum;

class SimpananAnggotaResource extends Resource
{
    protected static ?string $model = SimpananAkun::class;

    protected static string|UnitEnum|null $navigationGroup = 'Simpanan';

    protected static ?string $navigationLabel = 'Simpanan Anggota';

    protected static ?string $modelLabel = 'Simpanan Anggota';

    protected static ?string $pluralModelLabel = 'Simpanan Anggota';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function infolist(Schema $schema): Schema
    {
        return SimpananAnggotaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SimpananAnggotaTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['anggota.user', 'jenisSimpanan']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('simpanan.simpanan.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('simpanan.simpanan.view') ?? false;
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

    public static function getPages(): array
    {
        return [
            'index' => ListSimpananAnggota::route('/'),
            'view' => ViewSimpananAnggota::route('/{record}'),
        ];
    }
}

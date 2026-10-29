<?php

namespace Modules\Keanggotaan\Filament\Resources\Anggotas;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Keanggotaan\Filament\Resources\Anggotas\Pages\CreateAnggota;
use Modules\Keanggotaan\Filament\Resources\Anggotas\Pages\EditAnggota;
use Modules\Keanggotaan\Filament\Resources\Anggotas\Pages\ListAnggotas;
use Modules\Keanggotaan\Filament\Resources\Anggotas\Pages\ViewAnggota;
use Modules\Keanggotaan\Filament\Resources\Anggotas\Schemas\AnggotaForm;
use Modules\Keanggotaan\Filament\Resources\Anggotas\Schemas\AnggotaInfolist;
use Modules\Keanggotaan\Filament\Resources\Anggotas\Tables\AnggotasTable;
use Modules\Keanggotaan\Models\Anggota;
use UnitEnum;

class AnggotaResource extends Resource
{
    protected static ?string $model = Anggota::class;

    protected static string|UnitEnum|null $navigationGroup = 'Keanggotaan';

    protected static ?string $navigationLabel = 'Daftar Anggota';

    protected static ?string $modelLabel = 'Anggota';

    protected static ?string $pluralModelLabel = 'Anggota';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AnggotaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AnggotaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnggotasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->whereDoesntHave(
                'user.roles',
                fn (Builder $query): Builder => $query->where('name', 'super_admin'),
            );
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('keanggotaan.anggota.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('keanggotaan.anggota.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('keanggotaan.anggota.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('keanggotaan.anggota.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('keanggotaan.anggota.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('keanggotaan.anggota.delete') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()?->can('keanggotaan.anggota.delete') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('keanggotaan.anggota.delete') ?? false;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnggotas::route('/'),
            'create' => CreateAnggota::route('/create'),
            'view' => ViewAnggota::route('/{record}'),
            'edit' => EditAnggota::route('/{record}/edit'),
        ];
    }
}

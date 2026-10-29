<?php

namespace Modules\Simpanan\Filament\Resources\Kewajibans;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Simpanan\Filament\Resources\Kewajibans\Pages\ListKewajiban;
use Modules\Simpanan\Filament\Resources\Kewajibans\Tables\KewajibanTable;
use Modules\Simpanan\Models\KewajibanSimpanan;
use UnitEnum;

class KewajibanResource extends Resource
{
    protected static ?string $model = KewajibanSimpanan::class;

    protected static string|UnitEnum|null $navigationGroup = 'Simpanan';

    protected static ?string $navigationLabel = 'Kewajiban Simpanan';

    protected static ?string $modelLabel = 'Kewajiban Simpanan';

    protected static ?string $pluralModelLabel = 'Kewajiban Simpanan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function table(Table $table): Table
    {
        return KewajibanTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['anggota.user', 'jenisSimpanan']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('simpanan.kewajiban.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('simpanan.kewajiban.view') ?? false;
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
            'index' => ListKewajiban::route('/'),
        ];
    }
}

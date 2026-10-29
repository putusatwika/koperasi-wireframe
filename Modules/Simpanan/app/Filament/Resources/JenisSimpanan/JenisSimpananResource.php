<?php

namespace Modules\Simpanan\Filament\Resources\JenisSimpanan;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Simpanan\Filament\Resources\JenisSimpanan\Pages\CreateJenisSimpanan;
use Modules\Simpanan\Filament\Resources\JenisSimpanan\Pages\EditJenisSimpanan;
use Modules\Simpanan\Filament\Resources\JenisSimpanan\Pages\ListJenisSimpanan;
use Modules\Simpanan\Filament\Resources\JenisSimpanan\Schemas\JenisSimpananForm;
use Modules\Simpanan\Filament\Resources\JenisSimpanan\Tables\JenisSimpananTable;
use Modules\Simpanan\Models\JenisSimpanan;
use UnitEnum;

class JenisSimpananResource extends Resource
{
    protected static ?string $model = JenisSimpanan::class;

    protected static string|UnitEnum|null $navigationGroup = 'Simpanan';

    protected static ?string $navigationLabel = 'Produk Simpanan';

    protected static ?string $modelLabel = 'Produk Simpanan';

    protected static ?string $pluralModelLabel = 'Produk Simpanan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return JenisSimpananForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JenisSimpananTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('simpanan.jenis_simpanan.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('simpanan.jenis_simpanan.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('simpanan.jenis_simpanan.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('simpanan.jenis_simpanan.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('simpanan.jenis_simpanan.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('simpanan.jenis_simpanan.delete') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJenisSimpanan::route('/'),
            'create' => CreateJenisSimpanan::route('/create'),
            'edit' => EditJenisSimpanan::route('/{record}/edit'),
        ];
    }
}

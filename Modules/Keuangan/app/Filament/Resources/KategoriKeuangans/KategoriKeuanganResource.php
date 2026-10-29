<?php

namespace Modules\Keuangan\Filament\Resources\KategoriKeuangans;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Keuangan\Filament\Resources\KategoriKeuangans\Pages\CreateKategoriKeuangan;
use Modules\Keuangan\Filament\Resources\KategoriKeuangans\Pages\EditKategoriKeuangan;
use Modules\Keuangan\Filament\Resources\KategoriKeuangans\Pages\ListKategoriKeuangan;
use Modules\Keuangan\Filament\Resources\KategoriKeuangans\Schemas\KategoriKeuanganForm;
use Modules\Keuangan\Filament\Resources\KategoriKeuangans\Tables\KategoriKeuanganTable;
use Modules\Keuangan\Models\KategoriKeuangan;
use UnitEnum;

class KategoriKeuanganResource extends Resource
{
    protected static ?string $model = KategoriKeuangan::class;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Kategori Keuangan';

    protected static ?string $modelLabel = 'Kategori Keuangan';

    protected static ?string $pluralModelLabel = 'Kategori Keuangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return KategoriKeuanganForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KategoriKeuanganTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('keuangan.kategori.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('keuangan.kategori.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('keuangan.kategori.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('keuangan.kategori.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('keuangan.kategori.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('keuangan.kategori.delete') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKategoriKeuangan::route('/'),
            'create' => CreateKategoriKeuangan::route('/create'),
            'edit' => EditKategoriKeuangan::route('/{record}/edit'),
        ];
    }
}

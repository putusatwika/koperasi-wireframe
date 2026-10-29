<?php

namespace Modules\Peminjaman\Filament\Resources\JenisPinjaman;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\Pages\CreateJenisPinjaman;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\Pages\EditJenisPinjaman;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\Pages\ListJenisPinjaman;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\Pages\ViewJenisPinjaman;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\Schemas\JenisPinjamanForm;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\Schemas\JenisPinjamanInfolist;
use Modules\Peminjaman\Filament\Resources\JenisPinjaman\Tables\JenisPinjamanTable;
use Modules\Peminjaman\Models\JenisPinjaman;
use UnitEnum;

class JenisPinjamanResource extends Resource
{
    protected static ?string $model = JenisPinjaman::class;

    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static ?string $navigationLabel = 'Jenis Pinjaman';

    protected static ?string $modelLabel = 'Jenis Pinjaman';

    protected static ?string $pluralModelLabel = 'Jenis Pinjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return JenisPinjamanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JenisPinjamanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JenisPinjamanTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('peminjaman.jenis_pinjaman.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('peminjaman.jenis_pinjaman.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('peminjaman.jenis_pinjaman.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('peminjaman.jenis_pinjaman.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('peminjaman.jenis_pinjaman.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('peminjaman.jenis_pinjaman.delete') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJenisPinjaman::route('/'),
            'create' => CreateJenisPinjaman::route('/create'),
            'view' => ViewJenisPinjaman::route('/{record}'),
            'edit' => EditJenisPinjaman::route('/{record}/edit'),
        ];
    }
}

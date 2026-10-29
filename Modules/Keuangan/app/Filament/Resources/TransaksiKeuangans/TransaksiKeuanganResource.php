<?php

namespace Modules\Keuangan\Filament\Resources\TransaksiKeuangans;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Keuangan\Filament\Resources\TransaksiKeuangans\Pages\ListTransaksiKeuangan;
use Modules\Keuangan\Filament\Resources\TransaksiKeuangans\Pages\ViewTransaksiKeuangan;
use Modules\Keuangan\Filament\Resources\TransaksiKeuangans\Schemas\TransaksiKeuanganInfolist;
use Modules\Keuangan\Filament\Resources\TransaksiKeuangans\Tables\TransaksiKeuanganTable;
use Modules\Keuangan\Models\TransaksiKeuangan;
use UnitEnum;

class TransaksiKeuanganResource extends Resource
{
    protected static ?string $model = TransaksiKeuangan::class;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Riwayat Keuangan';

    protected static ?string $modelLabel = 'Transaksi Keuangan';

    protected static ?string $pluralModelLabel = 'Riwayat Keuangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static ?int $navigationSort = 20;

    public static function infolist(Schema $schema): Schema
    {
        return TransaksiKeuanganInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransaksiKeuanganTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['kategori.coaAccount', 'createdBy']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('keuangan.transaksi.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('keuangan.transaksi.view') ?? false;
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
            'index' => ListTransaksiKeuangan::route('/'),
            'view' => ViewTransaksiKeuangan::route('/{record}'),
        ];
    }
}

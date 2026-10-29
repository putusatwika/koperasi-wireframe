<?php

namespace Modules\Pembayaran\Filament\Resources\Histori;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Pembayaran\Filament\Resources\Histori\Pages\ListPembayarans;
use Modules\Pembayaran\Filament\Resources\Histori\Schemas\PembayaranInfolist;
use Modules\Pembayaran\Filament\Resources\Histori\Tables\PembayaranTable;
use Modules\Pembayaran\Models\Pembayaran;
use UnitEnum;

class HistoriResource extends Resource
{
    protected static ?string $model = Pembayaran::class;

    protected static ?string $slug = 'histori';

    protected static string|UnitEnum|null $navigationGroup = 'Pembayaran';

    protected static ?string $navigationLabel = 'Histori';

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Histori';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function table(Table $table): Table
    {
        return PembayaranTable::configure($table, false);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PembayaranInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['peminjaman', 'anggota.user'])
            ->whereHas('anggota', fn ($query) => $query->where('user_id', auth()->id()))
            ->latest('payment_date')
            ->latest('id');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('pembayaran.history.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('pembayaran.history.view') ?? false;
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
            'index' => ListPembayarans::route('/'),
        ];
    }
}

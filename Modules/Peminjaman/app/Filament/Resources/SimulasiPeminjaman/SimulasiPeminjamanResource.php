<?php

namespace Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\Pages\ListSimulasiPeminjaman;
use Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\Pages\ViewSimulasiPeminjaman;
use Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\Schemas\SimulasiPeminjamanInfolist;
use Modules\Peminjaman\Filament\Resources\SimulasiPeminjaman\Tables\SimulasiPeminjamanTable;
use Modules\Peminjaman\Models\SimulasiPeminjaman;
use UnitEnum;

class SimulasiPeminjamanResource extends Resource
{
    protected static ?string $model = SimulasiPeminjaman::class;

    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $navigationLabel = 'Simulasi Pinjaman';

    protected static ?string $modelLabel = 'Simulasi Pinjaman';

    protected static ?string $pluralModelLabel = 'Simulasi Pinjaman';

    public static function infolist(Schema $schema): Schema
    {
        return SimulasiPeminjamanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SimulasiPeminjamanTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['anggota.user', 'jenisPinjaman']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('peminjaman.simulasi_pinjaman.viewAny') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('peminjaman.simulasi_pinjaman.view') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSimulasiPeminjaman::route('/'),
            'view' => ViewSimulasiPeminjaman::route('/{record}'),
        ];
    }
}

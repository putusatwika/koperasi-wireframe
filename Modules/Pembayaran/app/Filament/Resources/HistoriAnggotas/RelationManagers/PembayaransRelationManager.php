<?php

namespace Modules\Pembayaran\Filament\Resources\HistoriAnggotas\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Pembayaran\Filament\Resources\Histori\Schemas\PembayaranInfolist;
use Modules\Pembayaran\Filament\Resources\Histori\Tables\PembayaranTable;

class PembayaransRelationManager extends RelationManager
{
    protected static string $relationship = 'pembayarans';

    protected static ?string $title = 'Histori Pembayaran';

    public function infolist(Schema $schema): Schema
    {
        return PembayaranInfolist::configure($schema, false);
    }

    public function table(Table $table): Table
    {
        return PembayaranTable::configure($table, false, 'pembayaran.history.viewAny');
    }
}

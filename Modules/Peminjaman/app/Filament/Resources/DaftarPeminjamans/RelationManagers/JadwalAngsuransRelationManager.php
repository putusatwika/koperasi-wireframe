<?php

namespace Modules\Peminjaman\Filament\Resources\DaftarPeminjamans\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JadwalAngsuransRelationManager extends RelationManager
{
    protected static string $relationship = 'jadwalAngsurans';

    protected static ?string $title = 'Jadwal Angsuran';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('angsuran_ke')->label('Ke'),
                TextColumn::make('jatuh_tempo')->label('Jatuh Tempo')->date(),
                TextColumn::make('pokok')->label('Pokok')->money('IDR'),
                TextColumn::make('bunga')->label('Bunga')->money('IDR'),
                TextColumn::make('total')->label('Total')->money('IDR'),
                TextColumn::make('dibayar_pokok')->label('Bayar Pokok')->money('IDR'),
                TextColumn::make('dibayar_bunga')->label('Bayar Bunga')->money('IDR'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('dibayar_pada')->label('Dibayar')->date()->placeholder('-'),
            ])
            ->defaultSort('angsuran_ke')
            ->paginated(false);
    }
}

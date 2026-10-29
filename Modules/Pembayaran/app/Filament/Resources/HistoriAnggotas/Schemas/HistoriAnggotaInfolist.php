<?php

namespace Modules\Pembayaran\Filament\Resources\HistoriAnggotas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class HistoriAnggotaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')->label('Nama Anggota'),
                TextEntry::make('user.email')->label('Email'),
                TextEntry::make('member_number')->label('Nomor Anggota'),
                TextEntry::make('account_number')->label('Nomor Rekening'),
                TextEntry::make('status')->label('Status')->badge(),
            ]);
    }
}

<?php

namespace Modules\Keanggotaan\Filament\Resources\Anggotas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AnggotaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')->label('User'),
                TextEntry::make('user.email')->label('Email'),
                TextEntry::make('member_number')->label('Nomor Anggota'),
                TextEntry::make('account_number')->label('Nomor Rekening'),
                TextEntry::make('identity_number')->label('Nomor Identitas'),
                TextEntry::make('phone')->label('Nomor Telepon'),
                TextEntry::make('address')->label('Alamat'),
                TextEntry::make('joined_at')->label('Tanggal Bergabung')->date(),
                TextEntry::make('status')->label('Status')->badge(),
            ]);
    }
}

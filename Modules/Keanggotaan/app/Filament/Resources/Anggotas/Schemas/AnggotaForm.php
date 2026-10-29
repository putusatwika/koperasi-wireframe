<?php

namespace Modules\Keanggotaan\Filament\Resources\Anggotas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AnggotaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user.name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                TextInput::make('user.email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('user.password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                TextInput::make('member_number')
                    ->label('Nomor Anggota')
                    ->required()
                    ->maxLength(50),
                TextInput::make('account_number')
                    ->label('Nomor Rekening')
                    ->required()
                    ->maxLength(50),
                TextInput::make('identity_number')
                    ->label('Nomor Identitas')
                    ->maxLength(50),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->maxLength(30),
                Textarea::make('address')
                    ->label('Alamat')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                DatePicker::make('joined_at')
                    ->label('Tanggal Bergabung')
                    ->default(now())
                    ->required(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending (belum aktif)',
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }
}

<?php

namespace Modules\Keanggotaan\Filament\Resources\Anggotas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Keanggotaan\Services\AnggotaService;
use Modules\Simpanan\Enums\KategoriSimpanan;
use Modules\Simpanan\Models\JenisSimpanan;

class AnggotaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('member_number')
                    ->label('Nomor Anggota')
                    ->default(fn (): string => app(AnggotaService::class)->generateMemberNumber())
                    ->helperText('Dibuat otomatis oleh sistem.')
                    ->readOnly()
                    ->required()
                    ->maxLength(50)
                    ->columnSpanFull(),
                TextInput::make('user.name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                TextInput::make('user.email')
                    ->label('Email')
                    ->email()
                    ->unique(
                        table: 'users',
                        column: 'email',
                        ignorable: fn (?Anggota $record) => $record?->user,
                        ignoreRecord: true,
                    )
                    ->validationMessages([
                        'unique' => 'Email ini sudah digunakan. Gunakan alamat email yang lain.',
                    ])
                    ->required()
                    ->maxLength(255),
                TextInput::make('user.password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->rules(['regex:/^[0-9]+$/'])
                    ->validationMessages(['regex' => 'Nomor telepon hanya boleh berisi angka.'])
                    ->maxLength(30),
                TextInput::make('identity_number')
                    ->label('Nomor KTP')
                    ->rules(['regex:/^[0-9]+$/'])
                    ->validationMessages([
                        'regex' => 'Nomor KTP hanya boleh berisi angka.',
                        'unique' => 'Nomor KTP ini sudah terdaftar pada anggota lain. Periksa kembali nomor KTP.',
                    ])
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(50),
                DatePicker::make('joined_at')
                    ->label('Tanggal Bergabung')
                    ->default(now())
                    ->required(),
                Select::make('bank_name')
                    ->label('Nama Bank')
                    ->options([
                        'BCA' => 'BCA',
                        'BRI' => 'BRI',
                        'BNI' => 'BNI',
                        'Mandiri' => 'Mandiri',
                        'BSI' => 'BSI',
                        'BTN' => 'BTN',
                        'CIMB Niaga' => 'CIMB Niaga',
                        'Danamon' => 'Danamon',
                        'Permata' => 'Permata',
                        'BJB' => 'BJB',
                        'Bank Mega' => 'Bank Mega',
                        'Panin' => 'Panin',
                    ])
                    ->searchable()
                    ->required(),
                TextInput::make('account_number')
                    ->label('Nomor Rekening')
                    ->rules(['regex:/^[0-9]+$/'])
                    ->validationMessages([
                        'regex' => 'Nomor rekening hanya boleh berisi angka.',
                        'unique' => 'Nomor rekening ini sudah dipakai oleh anggota lain. Periksa kembali nomor rekening atau gunakan nomor yang berbeda.',
                    ])
                    ->unique(ignoreRecord: true)
                    ->helperText('Tidak boleh sama dengan nomor rekening anggota lain.')
                    ->required()
                    ->maxLength(50),
                TextInput::make('occupation')
                    ->label('Pekerjaan')
                    ->maxLength(100),
                TextInput::make('income')
                    ->label('Penghasilan')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp'),
                Textarea::make('address')
                    ->label('Alamat')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending (belum aktif)',
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ])
                    ->default('active')
                    ->helperText('Pilih Pending jika anggota belum melunasi simpanan wajib.')
                    ->required(),
                Section::make('Pembayaran Simpanan Awal')
                    ->description('Opsional. Pilih simpanan yang dibayar saat pendaftaran. Status anggota otomatis menjadi Aktif jika simpanan pokok dan wajib sudah lunas.')
                    ->schema([
                        Toggle::make('initial_savings.pokok_enabled')
                            ->label('Bayar Simpanan Pokok')
                            ->helperText('Aktifkan jika simpanan pokok dibayar sekarang.')
                            ->default(false)
                            ->live(),
                        TextInput::make('initial_savings.pokok')
                            ->label('Nominal Simpanan Pokok')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->default(fn (string $operation): float => $operation === 'create'
                                ? self::defaultNominal(KategoriSimpanan::Pokok)
                                : 0)
                            ->helperText(fn (string $operation): string => $operation === 'create'
                                ? 'Standar: '.self::formattedNominal(KategoriSimpanan::Pokok)
                                : '')
                            ->visible(fn (Get $get): bool => self::isInitialSavingEnabled($get, 'pokok_enabled'))
                            ->required(fn (Get $get): bool => self::isInitialSavingEnabled($get, 'pokok_enabled')),
                        Toggle::make('initial_savings.wajib_enabled')
                            ->label('Bayar Simpanan Wajib')
                            ->helperText('Aktifkan jika simpanan wajib dibayar sekarang.')
                            ->default(false)
                            ->live(),
                        TextInput::make('initial_savings.wajib')
                            ->label('Nominal Simpanan Wajib')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->default(fn (string $operation): float => $operation === 'create'
                                ? self::defaultNominal(KategoriSimpanan::Wajib)
                                : 0)
                            ->helperText(fn (string $operation): string => $operation === 'create'
                                ? 'Standar: '.self::formattedNominal(KategoriSimpanan::Wajib)
                                : '')
                            ->visible(fn (Get $get): bool => self::isInitialSavingEnabled($get, 'wajib_enabled'))
                            ->required(fn (Get $get): bool => self::isInitialSavingEnabled($get, 'wajib_enabled')),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->columnSpanFull()
                    ->visible(fn (string $operation): bool => $operation === 'create'
                        && (auth()->user()?->can('simpanan.kewajiban.update') ?? false)),
            ]);
    }

    private static function isInitialSavingEnabled(Get $get, string $key): bool
    {
        return (bool) $get("initial_savings.{$key}");
    }

    private static function defaultNominal(KategoriSimpanan $kategori): float
    {
        return (float) (JenisSimpanan::query()
            ->where('is_active', true)
            ->where('kategori', $kategori->value)
            ->value('nominal_default') ?? 0);
    }

    private static function formattedNominal(KategoriSimpanan $kategori): string
    {
        return 'Rp '.number_format(self::defaultNominal($kategori), 0, ',', '.');
    }
}

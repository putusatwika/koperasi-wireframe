<?php

namespace Modules\Simpanan\Filament\Resources\JenisSimpanan\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\Simpanan\Enums\KategoriSimpanan;
use Modules\Simpanan\Enums\PeriodikSimpanan;

class JenisSimpananForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Produk Simpanan')
                ->schema([
                    TextInput::make('kode')
                        ->label('Kode')
                        ->required()
                        ->maxLength(30)
                        ->unique(ignoreRecord: true),
                    TextInput::make('nama')
                        ->label('Nama')
                        ->required()
                        ->maxLength(100),
                    Select::make('kategori')
                        ->label('Kategori')
                        ->options(collect(KategoriSimpanan::cases())->mapWithKeys(
                            fn (KategoriSimpanan $kategori): array => [$kategori->value => $kategori->name],
                        )->all())
                        ->required()
                        ->live(),
                    TextInput::make('nominal_default')
                        ->label('Nominal Default')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    Toggle::make('wajib')->label('Wajib'),
                    Select::make('periodik')
                        ->label('Periodik')
                        ->options(collect(PeriodikSimpanan::cases())->mapWithKeys(
                            fn (PeriodikSimpanan $periodik): array => [$periodik->value => $periodik->name],
                        )->all())
                        ->required()
                        ->live(),
                    TextInput::make('tanggal_jatuh_tempo')
                        ->label('Tanggal Jatuh Tempo (1-28)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(28)
                        ->visible(fn (Get $get): bool => $get('periodik') === PeriodikSimpanan::Monthly->value),
                    Toggle::make('berbunga')->label('Berbunga')->live(),
                    TextInput::make('bunga_per_bulan')
                        ->label('Bunga per Bulan (%)')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->visible(fn (Get $get): bool => (bool) $get('berbunga')),
                    TextInput::make('periode_bunga_bulan')
                        ->label('Periode Pemberian Bunga (bulan)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->default(1)
                        ->visible(fn (Get $get): bool => (bool) $get('berbunga')),
                    TextInput::make('tenor_bulan')
                        ->label('Tenor (bulan)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->visible(fn (Get $get): bool => $get('kategori') === KategoriSimpanan::Berjangka->value),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                    Textarea::make('deskripsi')->label('Deskripsi')->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}

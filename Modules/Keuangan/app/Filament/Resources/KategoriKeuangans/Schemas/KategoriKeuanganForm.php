<?php

namespace Modules\Keuangan\Filament\Resources\KategoriKeuangans\Schemas;

use App\Models\CoaAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Keuangan\Enums\TipeKategoriKeuangan;

class KategoriKeuanganForm
{
    public static function configure(Schema $schema): Schema
    {
        $coaAccountOptions = CoaAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (CoaAccount $account): array => [
                $account->getKey() => "{$account->code} - {$account->name}",
            ])
            ->all();

        return $schema->components([
            Section::make('Kategori Keuangan')
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
                    Select::make('tipe')
                        ->label('Tipe')
                        ->options(collect(TipeKategoriKeuangan::cases())->mapWithKeys(
                            fn (TipeKategoriKeuangan $tipe): array => [$tipe->value => $tipe->name],
                        )->all())
                        ->required(),
                    Select::make('coa_account_id')
                        ->label('Akun COA')
                        ->options($coaAccountOptions)
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                    Textarea::make('deskripsi')->label('Deskripsi')->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}

<?php

namespace Modules\Simpanan\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Simpanan\Models\JenisSimpanan;
use Modules\Simpanan\Models\SimpananAkun;
use Modules\Simpanan\Services\SimpananService;
use Throwable;
use UnitEnum;

class SetorSimpanan extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Simpanan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Setor Simpanan';

    protected static ?int $navigationSort = 20;

    protected string $view = 'simpanan::filament.pages.setor-simpanan';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('simpanan.transaksi.create') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $anggotaOptions = Anggota::query()
            ->with('user')
            ->where('status', 'active')
            ->orderBy('member_number')
            ->get()
            ->mapWithKeys(fn (Anggota $anggota): array => [
                $anggota->getKey() => "{$anggota->member_number} - {$anggota->user?->name}",
            ])
            ->all();

        $jenisOptions = JenisSimpanan::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Data Anggota')
                    ->description('Pilih anggota yang akan menyetor simpanan.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('anggota_id')
                                ->label('Anggota')
                                ->options($anggotaOptions)
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('jenis_simpanan_id', null);
                                    $set('nominal', null);
                                })
                                ->required(),
                        ]),
                    ]),
                Section::make('Produk & Nominal Setoran')
                    ->description('Saldo saat ini ditampilkan untuk membantu bendahara memverifikasi setoran.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('jenis_simpanan_id')
                                ->label('Produk Simpanan')
                                ->options($jenisOptions)
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set, mixed $state): void {
                                    $jenis = JenisSimpanan::query()->find($state);
                                    $nominalDefault = (float) ($jenis?->nominal_default ?? 0);

                                    $set('nominal', $nominalDefault > 0 ? $nominalDefault : null);
                                })
                                ->required(),
                            Placeholder::make('saldo_saat_ini')
                                ->label('Saldo Saat Ini')
                                ->visible(fn (Get $get): bool => filled($get('anggota_id')) && filled($get('jenis_simpanan_id')))
                                ->content(function (Get $get): string {
                                    $akun = SimpananAkun::query()
                                        ->where('anggota_id', $get('anggota_id'))
                                        ->where('jenis_simpanan_id', $get('jenis_simpanan_id'))
                                        ->first();

                                    if ($akun === null) {
                                        return 'Belum ada rekening — rekening baru akan dibuka otomatis.';
                                    }

                                    return 'Rp '.number_format((float) $akun->saldo, 2, ',', '.');
                                }),
                            TextInput::make('nominal')
                                ->label('Nominal Setoran')
                                ->numeric()
                                ->minValue(1)
                                ->prefix('Rp')
                                ->required(),
                            DatePicker::make('tanggal')
                                ->label('Tanggal Setoran')
                                ->default(now())
                                ->required(),
                            TextInput::make('referensi')
                                ->label('Referensi')
                                ->maxLength(100),
                            Textarea::make('keterangan')
                                ->label('Keterangan')
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }

    public function submit(SimpananService $service): void
    {
        try {
            $data = $this->form->getState();
            $anggota = Anggota::query()->find($data['anggota_id']);
            $jenis = JenisSimpanan::query()
                ->where('is_active', true)
                ->find($data['jenis_simpanan_id']);

            if ($anggota === null) {
                throw new InvalidArgumentException('Anggota tidak ditemukan.');
            }

            if ($jenis === null) {
                throw new InvalidArgumentException('Produk simpanan tidak ditemukan atau sudah tidak aktif.');
            }

            $akun = $service->openAccount($anggota, $jenis);

            $service->setor($akun, (float) $data['nominal'], [
                'tanggal' => $data['tanggal'],
                'referensi' => $data['referensi'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Setoran simpanan gagal')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()->title('Setoran simpanan berhasil dicatat')->success()->send();
        $this->form->fill();
    }
}

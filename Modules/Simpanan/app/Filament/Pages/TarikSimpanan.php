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
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Simpanan\Models\SimpananAkun;
use Modules\Simpanan\Services\SimpananService;
use Throwable;
use UnitEnum;

class TarikSimpanan extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Simpanan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Tarik Simpanan';

    protected static ?int $navigationSort = 21;

    protected string $view = 'simpanan::filament.pages.tarik-simpanan';

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

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Data Anggota')
                    ->description('Pilih anggota yang akan melakukan penarikan simpanan.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('anggota_id')
                                ->label('Anggota')
                                ->options($anggotaOptions)
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('simpanan_akun_id', null);
                                    $set('nominal', null);
                                })
                                ->required(),
                            Placeholder::make('ringkasan_anggota')
                                ->label('Total Saldo Simpanan')
                                ->visible(fn (Get $get): bool => filled($get('anggota_id')))
                                ->content(function (Get $get): string {
                                    $anggota = Anggota::query()->find($get('anggota_id'));

                                    if ($anggota === null) {
                                        return 'Anggota tidak ditemukan.';
                                    }

                                    $akun = SimpananAkun::query()
                                        ->where('anggota_id', $anggota->getKey())
                                        ->where('status', 'active')
                                        ->get();

                                    $total = $akun->sum(fn (SimpananAkun $row): float => (float) $row->saldo);

                                    return sprintf(
                                        'Rp %s · %d produk simpanan',
                                        number_format($total, 2, ',', '.'),
                                        $akun->count(),
                                    );
                                }),
                        ]),
                    ]),
                Section::make('Produk Simpanan')
                    ->description('Daftar produk simpanan anggota beserta saldo yang dapat dipilih.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('simpanan_akun_id')
                                ->label('Produk Simpanan & Saldo')
                                ->options(function (Get $get): array {
                                    $anggota = Anggota::query()->find($get('anggota_id'));

                                    if ($anggota === null) {
                                        return [];
                                    }

                                    return collect(app(SimpananService::class)->akunUntukPenarikan($anggota))
                                        ->mapWithKeys(fn (array $row): array => [
                                            $row['akun_id'] => sprintf(
                                                '%s — Rp %s — No. Rek. %s%s',
                                                $row['produk'],
                                                number_format((float) $row['saldo'], 2, ',', '.'),
                                                $row['nomor_rekening'],
                                                $row['bisa_ditarik'] ? '' : ' ('.$row['alasan_tidak_bisa'].')',
                                            ),
                                        ])
                                        ->all();
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('nominal', null);
                                })
                                ->disableOptionWhen(function (mixed $value, Get $get): bool {
                                    $anggota = Anggota::query()->find($get('anggota_id'));

                                    if ($anggota === null) {
                                        return true;
                                    }

                                    $row = collect(app(SimpananService::class)->akunUntukPenarikan($anggota))
                                        ->firstWhere('akun_id', (int) $value);

                                    return $row === null || ! $row['bisa_ditarik'];
                                })
                                ->helperText(fn (Get $get): ?string => blank($get('anggota_id'))
                                    ? 'Pilih anggota terlebih dahulu.'
                                    : null)
                                ->disabled(fn (Get $get): bool => blank($get('anggota_id')))
                                ->required(),
                            Placeholder::make('daftar_produk_simpanan')
                                ->label('Daftar Produk Simpanan')
                                ->visible(fn (Get $get): bool => filled($get('anggota_id')))
                                ->html()
                                ->content(function (Get $get): HtmlString {
                                    $anggota = Anggota::query()->find($get('anggota_id'));

                                    if ($anggota === null) {
                                        return new HtmlString('');
                                    }

                                    $rows = app(SimpananService::class)->akunUntukPenarikan($anggota);

                                    if ($rows === []) {
                                        return new HtmlString('Belum ada rekening simpanan aktif. Gunakan menu Setor Simpanan untuk membuka rekening.');
                                    }

                                    $items = collect($rows)
                                        ->map(fn (array $row): string => sprintf(
                                            '<li><strong>%s</strong> — Rp %s — No. Rek. %s (%s)</li>',
                                            e($row['produk']),
                                            number_format((float) $row['saldo'], 2, ',', '.'),
                                            e($row['nomor_rekening']),
                                            e($row['bisa_ditarik'] ? 'Dapat ditarik' : (string) $row['alasan_tidak_bisa']),
                                        ))
                                        ->implode('');

                                    return new HtmlString('<ul class="list-disc space-y-1 pl-5 text-sm">'.$items.'</ul>');
                                })
                                ->columnSpanFull(),
                            Placeholder::make('detail_akun')
                                ->label('Detail Rekening Terpilih')
                                ->visible(fn (Get $get): bool => filled($get('simpanan_akun_id')))
                                ->content(function (Get $get): string {
                                    $akun = SimpananAkun::query()
                                        ->with('jenisSimpanan')
                                        ->find($get('simpanan_akun_id'));

                                    if ($akun === null) {
                                        return 'Rekening tidak ditemukan.';
                                    }

                                    return sprintf(
                                        '%s · No. Rek. %s · Saldo Rp %s · Kategori %s · Jatuh tempo %s',
                                        $akun->jenisSimpanan?->nama ?? '-',
                                        $akun->nomor_rekening,
                                        number_format((float) $akun->saldo, 2, ',', '.'),
                                        $akun->jenisSimpanan?->kategori?->name ?? '-',
                                        $akun->jatuh_tempo?->format('d/m/Y') ?? '-',
                                    );
                                }),
                        ]),
                    ]),
                Section::make('Detail Penarikan')
                    ->description('Masukkan nominal penarikan. Saldo tersedia menjadi batas maksimal.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('nominal')
                                ->label('Nominal Penarikan')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(function (Get $get): ?float {
                                    $akun = SimpananAkun::query()->find($get('simpanan_akun_id'));

                                    return $akun === null ? null : (float) $akun->saldo;
                                })
                                ->prefix('Rp')
                                ->helperText(function (Get $get): string {
                                    $akun = SimpananAkun::query()->find($get('simpanan_akun_id'));

                                    return $akun === null
                                        ? 'Pilih produk simpanan terlebih dahulu.'
                                        : 'Maksimal penarikan: Rp '.number_format((float) $akun->saldo, 2, ',', '.');
                                })
                                ->required(),
                            DatePicker::make('tanggal')
                                ->label('Tanggal Penarikan')
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

            if ($anggota === null) {
                throw new InvalidArgumentException('Anggota tidak ditemukan.');
            }

            $akun = SimpananAkun::query()
                ->whereKey($data['simpanan_akun_id'])
                ->where('anggota_id', $anggota->getKey())
                ->first();

            if ($akun === null) {
                throw new InvalidArgumentException('Rekening simpanan tidak ditemukan untuk anggota terpilih.');
            }

            $service->tarik($akun, (float) $data['nominal'], [
                'tanggal' => $data['tanggal'],
                'referensi' => $data['referensi'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Penarikan simpanan gagal')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()->title('Penarikan simpanan berhasil dicatat')->success()->send();
        $this->form->fill();
    }
}

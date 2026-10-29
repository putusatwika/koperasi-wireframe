<?php

namespace Modules\Peminjaman\Filament\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Filament\Resources\Peminjamen\Schemas\PeminjamanForm;
use Modules\Peminjaman\Filament\Resources\PengajuanPeminjamans\PengajuanPeminjamanResource;
use Modules\Peminjaman\Models\JenisPinjaman;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use RuntimeException;
use UnitEnum;

class AjukanPeminjaman extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = 'Peminjaman';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentPlus;

    protected static ?string $navigationLabel = 'Pengajuan Pinjaman';

    protected static ?int $navigationSort = 20;

    protected string $view = 'peminjaman::filament.pages.ajukan-peminjaman';

    public ?array $data = [];

    public ?array $simulationData = [
        'jenis_pinjaman_id' => 'custom',
        'jumlah_pinjaman' => null,
        'metode_bunga' => null,
        'persentase_bunga_per_bulan' => null,
        'tenor_bulan' => '24',
        'tenor_bulan_custom' => null,
        'peruntukan' => null,
        'peruntukan_lainnya' => null,
        'rencana_mulai_cicilan' => null,
    ];

    public ?array $result = null;

    public ?int $draftId = null;

    public function mount(): void
    {
        $draftId = request()->integer('draft');

        if ($draftId > 0) {
            $anggotaId = auth()->user()?->anggota?->getKey();
            $draft = Peminjaman::query()
                ->whereKey($draftId)
                ->where('anggota_id', $anggotaId)
                ->where('status', 'DRAFT')
                ->firstOrFail();

            $this->draftId = $draft->getKey();
            $this->data = $draft->toArray();
            $this->form->fill($this->data);

            return;
        }

        $this->simulationData['rencana_mulai_cicilan'] ??= today()->format('Y-m');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! ($user?->can('peminjaman.pengajuan.create') ?? false)) {
            return false;
        }

        $anggota = $user?->anggota;

        return $anggota !== null && $anggota->status === 'active';
    }

    public function form(Schema $schema): Schema
    {
        return PeminjamanForm::configure(
            $schema,
            false,
            auth()->user()?->anggota?->getKey(),
        )
            ->statePath('data')
            ->model(Peminjaman::class)
            ->operation('create');
    }

    public function simulationForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('simulationData')
            ->components([
                Select::make('jenis_pinjaman_id')
                    ->label('Jenis Pinjaman')
                    ->options(fn (): array => ['custom' => 'Custom'] + JenisPinjaman::query()
                        ->orderBy('kode')
                        ->get()
                        ->mapWithKeys(fn (JenisPinjaman $jenisPinjaman): array => [
                            $jenisPinjaman->getKey() => sprintf(
                                '%s — %s (%s%%/bln, %s)',
                                $jenisPinjaman->kode,
                                $jenisPinjaman->nama,
                                $jenisPinjaman->persentase_bunga_per_bulan,
                                match ($jenisPinjaman->metode_bunga?->value) {
                                    JenisBungaPeminjaman::Flat->value => 'Bunga Tetap (flat)',
                                    JenisBungaPeminjaman::Effective->value => 'Saldo Menurun (efektif)',
                                    JenisBungaPeminjaman::Annuity->value => 'Anuitas',
                                    default => 'Metode Bunga',
                                },
                            ),
                        ])
                        ->all())
                    ->default('custom')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        if ($state === 'custom' || blank($state)) {
                            $set('metode_bunga', null);
                            $set('persentase_bunga_per_bulan', null);

                            return;
                        }

                        $jenisPinjaman = JenisPinjaman::query()->find((string) $state);

                        $set('metode_bunga', $jenisPinjaman?->metode_bunga?->value);
                        $set(
                            'persentase_bunga_per_bulan',
                            $jenisPinjaman?->getRawOriginal('persentase_bunga_per_bulan'),
                        );
                    })
                    ->partiallyRenderComponentsAfterStateUpdated([
                        'metode_bunga',
                        'persentase_bunga_per_bulan',
                    ])
                    ->required(),
                TextInput::make('jumlah_pinjaman')
                    ->label('Jumlah Pinjaman')
                    ->numeric()
                    ->minValue(1)
                    ->prefix('Rp')
                    ->required(),
                Select::make('metode_bunga')
                    ->label('Metode Perhitungan Bunga')
                    ->options([
                        JenisBungaPeminjaman::Flat->value => 'Flat',
                        JenisBungaPeminjaman::Effective->value => 'Efektif',
                        JenisBungaPeminjaman::Annuity->value => 'Anuitas',
                    ])
                    ->disabled(fn (Get $get): bool => $get('jenis_pinjaman_id') !== 'custom')
                    ->dehydrated()
                    ->required(),
                TextInput::make('persentase_bunga_per_bulan')
                    ->label('Persentase Bunga per Bulan (%)')
                    ->numeric()
                    ->minValue(0)
                    ->disabled(fn (Get $get): bool => $get('jenis_pinjaman_id') !== 'custom')
                    ->dehydrated()
                    ->required(),
                Select::make('tenor_bulan')
                    ->label('Tenor (Bulan)')
                    ->options([
                        '12' => '12 Bulan',
                        '18' => '18 Bulan',
                        '24' => '24 Bulan',
                        '36' => '36 Bulan',
                        'lainnya' => 'Lainnya',
                    ])
                    ->default(24)
                    ->live()
                    ->required(),
                TextInput::make('tenor_bulan_custom')
                    ->label('Tenor Lainnya (Bulan)')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->visible(fn (Get $get): bool => $get('tenor_bulan') === 'lainnya')
                    ->required(fn (Get $get): bool => $get('tenor_bulan') === 'lainnya'),
                Select::make('peruntukan')
                    ->label('Peruntukan')
                    ->options([
                        TujuanPeminjaman::Consumption->value => 'Konsumsi',
                        TujuanPeminjaman::Vehicle->value => 'Beli Kendaraan',
                        TujuanPeminjaman::Education->value => 'Pendidikan',
                        TujuanPeminjaman::Productive->value => 'Produktif/Usaha',
                        TujuanPeminjaman::Other->value => 'Lainnya',
                    ])
                    ->live()
                    ->required(),
                TextInput::make('peruntukan_lainnya')
                    ->label('Peruntukan Lainnya')
                    ->maxLength(30)
                    ->visible(fn (Get $get): bool => $get('peruntukan') === TujuanPeminjaman::Other->value)
                    ->required(fn (Get $get): bool => $get('peruntukan') === TujuanPeminjaman::Other->value),
                TextInput::make('rencana_mulai_cicilan')
                    ->label('Rencana Mulai Cicilan')
                    ->type('month')
                    ->default(today()->format('Y-m'))
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                        ? Carbon::parse($state)->startOfMonth()->toDateString()
                        : null)
                    ->required(),
            ]);
    }

    public function simulate(PeminjamanService $service): void
    {
        $simulationData = $this->simulationForm->getState();

        try {
            $calculationData = $this->simulationCalculationData($simulationData);
            $this->result = $service->simulate($calculationData);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->notifyFailure($exception);

            return;
        }
        $this->data = [...$this->simulationData];

        Notification::make()
            ->title('Simulasi berhasil dihitung')
            ->success()
            ->send();
    }

    public function continueApplication(PeminjamanService $service): void
    {
        if ($this->result === null) {
            Notification::make()
                ->title('Hitung simulasi terlebih dahulu')
                ->warning()
                ->send();

            return;
        }

        try {
            $simulationData = $this->simulationForm->getState();
            $draft = $service->createDraft($this->peminjamanData($simulationData));
            $service->createSimulation($this->simulationStorageData($simulationData));
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->notifyFailure($exception);

            return;
        }

        $this->redirect(static::getUrl([
            'draft' => $draft->getKey(),
        ]));
    }

    public function submit(PeminjamanService $service): void
    {
        if ($this->draftId === null) {
            Notification::make()
                ->title('Lanjutkan simulasi ke pengajuan terlebih dahulu')
                ->warning()
                ->send();

            return;
        }

        try {
            $peminjaman = Peminjaman::query()->findOrFail($this->draftId);
            $service->updateApplication($peminjaman, $this->form->getState());
            $service->submit($peminjaman);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->notifyFailure($exception);

            return;
        }

        Notification::make()
            ->title('Pengajuan pinjaman berhasil dibuat')
            ->success()
            ->send();

        $this->redirect(PengajuanPeminjamanResource::getUrl('index'));
    }

    private function notifyFailure(InvalidArgumentException|RuntimeException $exception): void
    {
        Notification::make()
            ->title('Pengajuan tidak dapat diproses')
            ->body($exception->getMessage())
            ->danger()
            ->persistent()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function simulationCalculationData(array $data): array
    {
        return [
            'loan_amount' => $data['jumlah_pinjaman'],
            'interest_type' => $data['metode_bunga'],
            'interest_rate' => (float) $data['persentase_bunga_per_bulan'] * 12,
            'term_months' => $this->resolveTenorBulan($data),
            'purpose' => $this->resolvePeruntukan($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveTenorBulan(array $data): int
    {
        $tenorBulan = $data['tenor_bulan'] === 'lainnya'
            ? $data['tenor_bulan_custom'] ?? 0
            : $data['tenor_bulan'] ?? 0;

        if ((int) $tenorBulan < 1) {
            throw new InvalidArgumentException('Tenor pinjaman wajib diisi dengan angka yang valid.');
        }

        return (int) $tenorBulan;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function peminjamanData(array $data): array
    {
        $jenisPinjamanId = $data['jenis_pinjaman_id'] ?? null;

        return $this->simulationCalculationData($data) + [
            'anggota_id' => $data['anggota_id'] ?? null,
            'jenis_pinjaman_id' => in_array($jenisPinjamanId, [null, '', 'custom'], true) ? null : $jenisPinjamanId,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function simulationStorageData(array $data): array
    {
        $calculationData = $this->simulationCalculationData($data);

        $data['peruntukan'] = $calculationData['purpose'];
        $data['tenor_bulan'] = $calculationData['term_months'];
        unset($data['peruntukan_lainnya'], $data['tenor_bulan_custom']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvePeruntukan(array $data): string
    {
        if (($data['peruntukan'] ?? null) !== TujuanPeminjaman::Other->value) {
            return (string) ($data['peruntukan'] ?? '');
        }

        $peruntukanLainnya = trim((string) ($data['peruntukan_lainnya'] ?? ''));

        if ($peruntukanLainnya === '') {
            throw new InvalidArgumentException('Peruntukan lainnya wajib diisi.');
        }

        return $peruntukanLainnya;
    }
}

<?php

namespace Modules\Peminjaman\Filament\Resources\Peminjamen\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\TipeSimulasiPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Models\SimulasiPeminjaman;

class PeminjamanForm
{
    public static function configure(Schema $schema, bool $forOtherMember = false, ?int $forcedAnggotaId = null): Schema
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin') ?? false;
        $ownAnggotaId = $user?->anggota?->getKey();
        $simulationAnggotaId = $forcedAnggotaId ?? $ownAnggotaId;
        $simulationOptions = self::simulationOptions($simulationAnggotaId);
        $simulationDefaults = self::simulationDefaults($simulationAnggotaId);

        $anggotaField = Select::make('anggota_id')
            ->label('Anggota')
            ->options(fn (): array => Anggota::query()
                ->with('user:id,name,email')
                ->where('status', 'active')
                ->when(
                    ! $forOtherMember,
                    fn ($query) => $query->whereKey($ownAnggotaId ?? 0),
                )
                ->when(
                    $forOtherMember && ! $isSuperAdmin && $ownAnggotaId !== null,
                    fn ($query) => $query->whereKeyNot($ownAnggotaId),
                )
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (Anggota $anggota): array => [
                    $anggota->getKey() => "{$anggota->user->name} - {$anggota->member_number}",
                ])
                ->all())
            ->default($forcedAnggotaId)
            ->searchable()
            ->preload()
            ->disabled($forcedAnggotaId !== null)
            ->required();

        return $schema
            ->components([
                $anggotaField,
                TextInput::make('account_number')
                    ->label('Nomor Rekening Pencairan')
                    ->visible(fn (): bool => $forOtherMember)
                    ->required()
                    ->maxLength(50),
                Select::make('simulation_type')
                    ->label('Tipe Simulasi')
                    ->options([
                        TipeSimulasiPeminjaman::Default->value => 'Default',
                        TipeSimulasiPeminjaman::Custom->value => 'Custom',
                    ])
                    ->default(TipeSimulasiPeminjaman::Default->value)
                    ->live()
                    ->required(),
                Select::make('simulation_id')
                    ->label('Simulasi Sebelumnya')
                    ->options($simulationOptions)
                    ->default(array_key_first($simulationOptions))
                    ->visible($simulationOptions !== [])
                    ->disabled()
                    ->dehydrated(false),
                Select::make('interest_type')
                    ->label('Jenis Bunga')
                    ->options([
                        JenisBungaPeminjaman::Flat->value => 'Flat',
                        JenisBungaPeminjaman::Effective->value => 'Efektif',
                        JenisBungaPeminjaman::Annuity->value => 'Anuitas',
                    ])
                    ->default($simulationDefaults['interest_type'] ?? null)
                    ->disabled(fn (Get $get): bool => $get('simulation_type') === TipeSimulasiPeminjaman::Default->value)
                    ->dehydrated()
                    ->required(),
                TextInput::make('interest_rate')
                    ->label('Nilai Bunga per Bulan (%)')
                    ->numeric()
                    ->minValue(0)
                    ->default($simulationDefaults['interest_rate'] ?? null)
                    ->formatStateUsing(fn (mixed $state): mixed => filled($state) ? (float) $state / 12 : $state)
                    ->dehydrateStateUsing(fn (mixed $state): ?float => filled($state) ? (float) $state * 12 : null)
                    ->disabled(fn (Get $get): bool => $get('simulation_type') === TipeSimulasiPeminjaman::Default->value)
                    ->dehydrated()
                    ->required(),
                TextInput::make('term_months')
                    ->label('Lama Pinjaman (Bulan)')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default($simulationDefaults['term_months'] ?? null)
                    ->disabled(fn (Get $get): bool => $get('simulation_type') === TipeSimulasiPeminjaman::Default->value)
                    ->dehydrated()
                    ->required(),
                TextInput::make('loan_amount')
                    ->label('Jumlah Pinjaman')
                    ->numeric()
                    ->minValue(1)
                    ->prefix('Rp')
                    ->default($simulationDefaults['loan_amount'] ?? null)
                    ->disabled(fn (Get $get): bool => $get('simulation_type') === TipeSimulasiPeminjaman::Default->value)
                    ->dehydrated()
                    ->required(),
                TextInput::make('monthly_income')
                    ->label('Penghasilan per Bulan')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('dependents_count')
                    ->label('Jumlah Tanggungan')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),
                Select::make('purpose')
                    ->label('Peruntukan')
                    ->options([
                        TujuanPeminjaman::Consumption->value => 'Konsumsi',
                        TujuanPeminjaman::Vehicle->value => 'Beli Kendaraan',
                        TujuanPeminjaman::Education->value => 'Pendidikan',
                        TujuanPeminjaman::Productive->value => 'Produktif/Usaha',
                        TujuanPeminjaman::Other->value => 'Lainnya',
                    ])
                    ->default($simulationDefaults['purpose'] ?? null)
                    ->live()
                    ->disabled(fn (Get $get): bool => $get('simulation_type') === TipeSimulasiPeminjaman::Default->value)
                    ->dehydrated()
                    ->required(),
                Select::make('urgency')
                    ->label('Urgensi')
                    ->options([
                        'low' => 'Rendah',
                        'medium' => 'Sedang',
                        'high' => 'Tinggi',
                    ])
                    ->required(),
                Textarea::make('purpose_description')
                    ->label('Uraian Keperluan')
                    ->maxLength(2000)
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('need_description')
                    ->label('Penjelasan Kebutuhan Dana')
                    ->maxLength(2000)
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('funds_needed_at')
                    ->label('Tanggal Dana Dibutuhkan')
                    ->required(),
                Textarea::make('repayment_source')
                    ->label('Sumber Pembayaran')
                    ->maxLength(1000)
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('guarantee_required')
                    ->label('Membutuhkan Jaminan')
                    ->visible(fn (): bool => $forOtherMember)
                    ->default(false),
                TextInput::make('penalty_rate')
                    ->label('Denda (%)')
                    ->visible(fn (): bool => $forOtherMember)
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('late_fee')
                    ->label('Pinalti')
                    ->visible(fn (): bool => $forOtherMember)
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                FileUpload::make('statement_document')
                    ->label('Surat Pernyataan')
                    ->disk('public')
                    ->directory('peminjaman/surat-pernyataan')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240)
                    ->required(),
                FileUpload::make('identity_document')
                    ->label('Dokumen Identitas')
                    ->disk('public')
                    ->directory('peminjaman/dokumen')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240),
                FileUpload::make('income_document')
                    ->label('Dokumen Penghasilan')
                    ->disk('public')
                    ->directory('peminjaman/dokumen')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240),
                FileUpload::make('guarantee_document')
                    ->label('Dokumen Jaminan')
                    ->disk('public')
                    ->directory('peminjaman/dokumen')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240),
                FileUpload::make('supporting_documents')
                    ->label('Dokumen Pendukung Lainnya')
                    ->multiple()
                    ->disk('public')
                    ->directory('peminjaman/dokumen-pendukung')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240),
                FileUpload::make('approval_document')
                    ->label('Formulir Persetujuan')
                    ->visible(fn (): bool => $forOtherMember)
                    ->disk('public')
                    ->directory('peminjaman/persetujuan')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240),
                DatePicker::make('submitted_at')
                    ->label('Tanggal Pengajuan')
                    ->default(now())
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    /**
     * @return array{interest_type: ?string, interest_rate: ?float, term_months: ?int, loan_amount: ?string, purpose: ?string}
     */
    private static function simulationDefaults(?int $anggotaId): array
    {
        if ($anggotaId === null) {
            return [];
        }

        $simulation = SimulasiPeminjaman::query()
            ->where('anggota_id', $anggotaId)
            ->latest('id')
            ->first();

        if ($simulation === null) {
            return [];
        }

        return [
            'interest_type' => $simulation->metode_bunga?->value,
            'interest_rate' => (float) $simulation->persentase_bunga_per_bulan * 12,
            'term_months' => $simulation->tenor_bulan,
            'loan_amount' => (string) $simulation->jumlah_pinjaman,
            'purpose' => $simulation->peruntukan,
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function simulationOptions(?int $anggotaId): array
    {
        if ($anggotaId === null) {
            return [];
        }

        return SimulasiPeminjaman::query()
            ->where('anggota_id', $anggotaId)
            ->latest('id')
            ->get()
            ->mapWithKeys(fn (SimulasiPeminjaman $simulation): array => [
                $simulation->getKey() => sprintf(
                    '#%s - Rp %s - %s bulan',
                    $simulation->getKey(),
                    number_format((float) $simulation->jumlah_pinjaman, 0, ',', '.'),
                    $simulation->tenor_bulan,
                ),
            ])
            ->all();
    }
}

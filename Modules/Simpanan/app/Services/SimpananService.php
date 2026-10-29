<?php

namespace Modules\Simpanan\Services;

use App\Models\User;
use App\Services\Accounting\JurnalService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Simpanan\Enums\KategoriSimpanan;
use Modules\Simpanan\Enums\PeriodikSimpanan;
use Modules\Simpanan\Enums\SimpananCOAConfigEnum;
use Modules\Simpanan\Enums\StatusKewajiban;
use Modules\Simpanan\Enums\TipeTransaksiSimpanan;
use Modules\Simpanan\Models\CoaConfig;
use Modules\Simpanan\Models\JenisSimpanan;
use Modules\Simpanan\Models\KewajibanSimpanan;
use Modules\Simpanan\Models\SimpananAkun;
use Modules\Simpanan\Models\TransaksiSimpanan;
use RuntimeException;

class SimpananService
{
    public function __construct(private readonly JurnalService $jurnalService) {}

    /**
     * Open the mandatory savings accounts and first obligations for a newly registered member.
     */
    public function registerInitialSavings(Anggota $anggota): void
    {
        $now = CarbonImmutable::now();
        $jenisList = JenisSimpanan::query()->where('is_active', true)->get();

        foreach ($jenisList as $jenis) {
            $isMandatory = $jenis->kategori === KategoriSimpanan::Pokok
                || $jenis->kategori === KategoriSimpanan::Wajib
                || $jenis->periodik === PeriodikSimpanan::Monthly;

            if (! $isMandatory) {
                continue;
            }

            $this->openAccount($anggota, $jenis, $now);

            if ($jenis->kategori === KategoriSimpanan::Pokok) {
                $this->ensureObligation($anggota, $jenis, 'AWAL', $now->toDateString());
            }

            if ($jenis->kategori === KategoriSimpanan::Wajib || $jenis->periodik === PeriodikSimpanan::Monthly) {
                $this->ensureObligation($anggota, $jenis, $now->format('Y-m'), $this->dueDate($jenis, $now));
            }
        }

        if (! $this->hasPaidMandatory($anggota)) {
            $anggota->update(['status' => 'pending']);
        } else {
            $this->syncActivationStatus($anggota);
        }
    }

    public function openAccount(Anggota $anggota, JenisSimpanan $jenis, ?CarbonImmutable $openedAt = null): SimpananAkun
    {
        $openedAt ??= CarbonImmutable::now();

        return SimpananAkun::query()->firstOrCreate(
            ['anggota_id' => $anggota->getKey(), 'jenis_simpanan_id' => $jenis->getKey()],
            [
                'nomor_rekening' => $this->nextAccountNumber($anggota, $jenis),
                'saldo' => 0,
                'status' => 'active',
                'dibuka_pada' => $openedAt->toDateString(),
                'jatuh_tempo' => $jenis->kategori === KategoriSimpanan::Berjangka && $jenis->tenor_bulan
                    ? $openedAt->addMonths((int) $jenis->tenor_bulan)->toDateString()
                    : null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function setor(SimpananAkun $akun, float $nominal, array $meta = []): TransaksiSimpanan
    {
        abort_unless(auth()->user()?->can('simpanan.transaksi.create') ?? false, 403);

        return $this->record($akun, TipeTransaksiSimpanan::Setor, $nominal, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function tarik(SimpananAkun $akun, float $nominal, array $meta = []): TransaksiSimpanan
    {
        abort_unless(auth()->user()?->can('simpanan.transaksi.create') ?? false, 403);

        return $this->record($akun, TipeTransaksiSimpanan::Tarik, $nominal, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function bayarKewajiban(KewajibanSimpanan $kewajiban, ?float $nominal = null, array $meta = []): KewajibanSimpanan
    {
        abort_unless(auth()->user()?->can('simpanan.kewajiban.update') ?? false, 403);

        return DB::transaction(function () use ($kewajiban, $nominal, $meta): KewajibanSimpanan {
            $locked = KewajibanSimpanan::query()->lockForUpdate()->findOrFail($kewajiban->getKey());

            if ($locked->status === StatusKewajiban::Paid) {
                throw new InvalidArgumentException('Kewajiban simpanan sudah dibayar.');
            }

            $jenis = JenisSimpanan::query()->findOrFail($locked->jenis_simpanan_id);
            $anggota = Anggota::query()->findOrFail($locked->anggota_id);
            $akun = $this->openAccount($anggota, $jenis);
            $amount = round((float) ($nominal ?? $locked->nominal), 2);

            $transaksi = $this->record($akun, TipeTransaksiSimpanan::Setor, $amount, [
                ...$meta,
                'keterangan' => $meta['keterangan'] ?? "Pembayaran {$jenis->nama} periode {$locked->periode}",
            ]);

            $locked->update([
                'status' => StatusKewajiban::Paid,
                'paid_at' => now(),
                'simpanan_transaksi_id' => $transaksi->getKey(),
            ]);

            $this->syncActivationStatus($anggota);

            return $locked->refresh();
        });
    }

    public function generateMonthlyObligations(?string $periode = null): int
    {
        $period = $periode ?? CarbonImmutable::now()->format('Y-m');
        $date = CarbonImmutable::createFromFormat('Y-m', $period);

        if ($date === false) {
            throw new InvalidArgumentException('Format periode harus YYYY-MM.');
        }

        $accounts = SimpananAkun::query()
            ->with(['jenisSimpanan', 'anggota'])
            ->where('status', 'active')
            ->whereHas('jenisSimpanan', fn ($query) => $query
                ->where('periodik', PeriodikSimpanan::Monthly->value)
                ->where('is_active', true))
            ->get();

        $created = 0;

        foreach ($accounts as $akun) {
            $jenis = $akun->jenisSimpanan;
            $anggota = $akun->anggota;

            if ($jenis === null || $anggota === null) {
                continue;
            }

            if ($this->ensureObligation($anggota, $jenis, $period, $this->dueDate($jenis, $date))) {
                $created++;
            }
        }

        return $created;
    }

    public function prosesBunga(?string $periode = null): int
    {
        $end = CarbonImmutable::now()->endOfMonth();

        if ($periode !== null) {
            $parsed = CarbonImmutable::createFromFormat('Y-m', $periode);

            if ($parsed === false) {
                throw new InvalidArgumentException('Format periode harus YYYY-MM.');
            }

            $end = $parsed->endOfMonth();
        }

        $currentMonth = $end->startOfMonth();
        $accounts = SimpananAkun::query()->with('jenisSimpanan')->where('status', 'active')->get();
        $posted = 0;

        foreach ($accounts as $akun) {
            $jenis = $akun->jenisSimpanan;

            if ($jenis === null || ! $jenis->berbunga || (float) $jenis->bunga_per_bulan <= 0) {
                continue;
            }

            $cycleMonths = max(1, (int) $jenis->periode_bunga_bulan);
            $lastInterestDate = TransaksiSimpanan::query()
                ->where('simpanan_akun_id', $akun->getKey())
                ->where('tipe', TipeTransaksiSimpanan::Bunga->value)
                ->latest('tanggal')
                ->value('tanggal');

            if ($lastInterestDate !== null) {
                $lastMonth = CarbonImmutable::parse($lastInterestDate)->startOfMonth();

                if ((int) $lastMonth->diffInMonths($currentMonth) < $cycleMonths) {
                    continue;
                }
            } else {
                $openedMonth = CarbonImmutable::parse($akun->dibuka_pada)->startOfMonth();

                if ((int) $openedMonth->diffInMonths($currentMonth) < $cycleMonths - 1) {
                    continue;
                }
            }

            $cycleStart = $currentMonth->subMonths($cycleMonths - 1);
            $totalAverage = 0.0;

            for ($index = 0; $index < $cycleMonths; $index++) {
                $totalAverage += $this->averageDailyBalance($akun, $cycleStart->addMonths($index));
            }

            $interest = round($totalAverage * ((float) $jenis->bunga_per_bulan / 100), 2);

            if ($interest <= 0) {
                continue;
            }

            DB::transaction(function () use ($akun, $interest, $end): void {
                $userId = $this->journalUserId();
                $transaksi = TransaksiSimpanan::query()->create([
                    'simpanan_akun_id' => $akun->getKey(),
                    'tipe' => TipeTransaksiSimpanan::Bunga,
                    'tanggal' => $end->toDateString(),
                    'nominal' => $interest,
                    'keterangan' => 'Bunga simpanan',
                    'created_by' => $userId,
                ]);

                $akun->increment('saldo', $interest);

                $this->postJournal($transaksi, SimpananCOAConfigEnum::SavingsInterestExpense, 'DEBIT', $interest, $userId);
                $this->postJournal($transaksi, SimpananCOAConfigEnum::SavingsLiability, 'CREDIT', $interest, $userId);
            });

            $posted++;
        }

        return $posted;
    }

    public function markOverdueMembersPending(?CarbonImmutable $asOf = null): int
    {
        $asOf ??= CarbonImmutable::now();
        $periods = collect(range(2, 0))
            ->map(fn (int $monthsAgo): string => $asOf->startOfMonth()->subMonths($monthsAgo)->format('Y-m'))
            ->all();

        $wajibJenisIds = JenisSimpanan::query()
            ->where('is_active', true)
            ->where('periodik', PeriodikSimpanan::Monthly->value)
            ->pluck('id');

        if ($wajibJenisIds->isEmpty()) {
            return 0;
        }

        $rows = KewajibanSimpanan::query()
            ->whereIn('jenis_simpanan_id', $wajibJenisIds)
            ->whereIn('periode', $periods)
            ->where('status', StatusKewajiban::Pending->value)
            ->whereDate('jatuh_tempo', '<=', $asOf->toDateString())
            ->selectRaw('anggota_id, COUNT(DISTINCT periode) as unpaid_periods')
            ->groupBy('anggota_id')
            ->get();

        $candidateIds = $rows->where('unpaid_periods', '>=', 3)->pluck('anggota_id');

        if ($candidateIds->isEmpty()) {
            return 0;
        }

        return Anggota::query()
            ->whereIn('id', $candidateIds)
            ->where('status', 'active')
            ->get()
            ->reduce(function (int $count, Anggota $anggota): int {
                $anggota->update(['status' => 'pending']);

                return $count + 1;
            }, 0);
    }

    /**
     * @return array{total: string, accounts: array<int, array<string, mixed>>}
     */
    public function saldoAnggota(Anggota $anggota): array
    {
        $akunByJenis = SimpananAkun::query()
            ->where('anggota_id', $anggota->getKey())
            ->get()
            ->keyBy('jenis_simpanan_id');

        $accounts = JenisSimpanan::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn (JenisSimpanan $jenis): array => [
                'akun_id' => $akunByJenis->get($jenis->getKey())?->getKey(),
                'nomor_rekening' => $akunByJenis->get($jenis->getKey())?->nomor_rekening,
                'jenis' => $jenis->nama,
                'kategori' => $jenis->kategori->value,
                'saldo' => (string) ($akunByJenis->get($jenis->getKey())?->saldo ?? '0.00'),
                'jatuh_tempo' => $akunByJenis->get($jenis->getKey())?->jatuh_tempo?->toDateString(),
            ])
            ->all();

        $totalCents = collect($accounts)
            ->sum(fn (array $row): int => (int) round(((float) $row['saldo']) * 100));

        return [
            'total' => Money::fromCents($totalCents),
            'accounts' => $accounts,
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function record(SimpananAkun $akun, TipeTransaksiSimpanan $tipe, float $nominal, array $meta = []): TransaksiSimpanan
    {
        $nominal = round($nominal, 2);

        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal simpanan harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($akun, $tipe, $nominal, $meta): TransaksiSimpanan {
            $userId = auth()->id();

            if (blank($userId)) {
                throw new RuntimeException('Pengguna terautentikasi diperlukan untuk transaksi simpanan.');
            }

            $locked = SimpananAkun::query()->lockForUpdate()->findOrFail($akun->getKey());

            if ($locked->status !== 'active') {
                throw new InvalidArgumentException('Rekening simpanan tidak aktif.');
            }

            if ($tipe === TipeTransaksiSimpanan::Tarik
                && $locked->jatuh_tempo !== null
                && $locked->jatuh_tempo->isFuture()) {
                throw new InvalidArgumentException('Simpanan berjangka belum jatuh tempo dan tidak dapat ditarik.');
            }

            if ($tipe === TipeTransaksiSimpanan::Tarik && (float) $locked->saldo < $nominal) {
                throw new InvalidArgumentException('Saldo simpanan tidak mencukupi untuk penarikan.');
            }

            $transaksi = TransaksiSimpanan::query()->create([
                'simpanan_akun_id' => $locked->getKey(),
                'tipe' => $tipe,
                'tanggal' => $meta['tanggal'] ?? CarbonImmutable::now()->toDateString(),
                'nominal' => $nominal,
                'referensi' => $meta['referensi'] ?? null,
                'keterangan' => $meta['keterangan'] ?? null,
                'created_by' => $userId,
            ]);

            if ($tipe === TipeTransaksiSimpanan::Setor) {
                $locked->increment('saldo', $nominal);
                $this->postJournal($transaksi, SimpananCOAConfigEnum::SavingsCash, 'DEBIT', $nominal, $userId);
                $this->postJournal($transaksi, SimpananCOAConfigEnum::SavingsLiability, 'CREDIT', $nominal, $userId);

                return $transaksi->refresh();
            }

            $locked->decrement('saldo', $nominal);
            $this->postJournal($transaksi, SimpananCOAConfigEnum::SavingsLiability, 'DEBIT', $nominal, $userId);
            $this->postJournal($transaksi, SimpananCOAConfigEnum::SavingsCash, 'CREDIT', $nominal, $userId);

            return $transaksi->refresh();
        });
    }

    private function ensureObligation(Anggota $anggota, JenisSimpanan $jenis, string $periode, string $jatuhTempo): bool
    {
        if ((float) $jenis->nominal_default <= 0) {
            return false;
        }

        $obligation = KewajibanSimpanan::query()->firstOrCreate(
            [
                'anggota_id' => $anggota->getKey(),
                'jenis_simpanan_id' => $jenis->getKey(),
                'periode' => $periode,
            ],
            [
                'nominal' => $jenis->nominal_default,
                'jatuh_tempo' => $jatuhTempo,
                'status' => StatusKewajiban::Pending,
            ],
        );

        return $obligation->wasRecentlyCreated;
    }

    private function hasPaidMandatory(Anggota $anggota): bool
    {
        $mandatoryJenis = JenisSimpanan::query()
            ->where('is_active', true)
            ->where('nominal_default', '>', 0)
            ->where(function ($query): void {
                $query->whereIn('kategori', [KategoriSimpanan::Pokok->value, KategoriSimpanan::Wajib->value])
                    ->orWhere('periodik', PeriodikSimpanan::Monthly->value);
            })
            ->get();

        if ($mandatoryJenis->isEmpty()) {
            return true;
        }

        foreach ($mandatoryJenis as $jenis) {
            $paid = KewajibanSimpanan::query()
                ->where('anggota_id', $anggota->getKey())
                ->where('jenis_simpanan_id', $jenis->getKey())
                ->where('status', StatusKewajiban::Paid->value)
                ->exists();

            if (! $paid) {
                return false;
            }
        }

        return true;
    }

    private function syncActivationStatus(Anggota $anggota): void
    {
        if ($anggota->status === 'active') {
            return;
        }

        if ($this->hasPaidMandatory($anggota)) {
            $anggota->update(['status' => 'active']);
        }
    }

    private function averageDailyBalance(SimpananAkun $akun, CarbonImmutable $month): float
    {
        $start = $month->startOfMonth();
        $days = $start->daysInMonth;
        $balanceCents = $this->signedBalanceBefore($akun, $start);

        $transactions = TransaksiSimpanan::query()
            ->where('simpanan_akun_id', $akun->getKey())
            ->whereBetween('tanggal', [$start->toDateString(), $month->endOfMonth()->toDateString()])
            ->get()
            ->groupBy(fn (TransaksiSimpanan $transaksi): string => $transaksi->tanggal->toDateString());

        $totalCents = 0;

        for ($day = 1; $day <= $days; $day++) {
            $date = $start->setDay($day)->toDateString();

            foreach ($transactions->get($date, collect()) as $transaksi) {
                $amountCents = (int) round((float) $transaksi->nominal * 100);
                $balanceCents += $transaksi->tipe === TipeTransaksiSimpanan::Tarik ? -$amountCents : $amountCents;
            }

            $totalCents += $balanceCents;
        }

        return ($totalCents / 100) / $days;
    }

    private function signedBalanceBefore(SimpananAkun $akun, CarbonImmutable $date): int
    {
        $transactions = TransaksiSimpanan::query()
            ->where('simpanan_akun_id', $akun->getKey())
            ->where('tanggal', '<', $date->toDateString())
            ->get();

        return $transactions->reduce(function (int $balance, TransaksiSimpanan $transaksi): int {
            $amountCents = (int) round((float) $transaksi->nominal * 100);

            return $transaksi->tipe === TipeTransaksiSimpanan::Tarik
                ? $balance - $amountCents
                : $balance + $amountCents;
        }, 0);
    }

    private function dueDate(JenisSimpanan $jenis, CarbonImmutable $period): string
    {
        $day = max(1, min((int) ($jenis->tanggal_jatuh_tempo ?? 1), $period->daysInMonth));

        return $period->setDay($day)->toDateString();
    }

    private function nextAccountNumber(Anggota $anggota, JenisSimpanan $jenis): string
    {
        return 'SMP-'.str_pad((string) $anggota->getKey(), 6, '0', STR_PAD_LEFT).'-'.strtoupper((string) $jenis->kode);
    }

    private function postJournal(
        TransaksiSimpanan $transaksi,
        SimpananCOAConfigEnum $key,
        string $position,
        float $amount,
        int $userId,
    ): void {
        $this->jurnalService->create([
            'jurnal_number' => "SIMPANAN-{$transaksi->getKey()}-{$position}",
            'jurnal_date' => $transaksi->tanggal->toDateString(),
            'source_module' => 'Simpanan',
            'source_id' => $transaksi->getKey(),
            'description' => "Simpanan {$transaksi->tipe->value} #{$transaksi->getKey()}",
            'status' => 'POSTED',
            'created_by' => $userId,
            'coa_account_id' => $this->coaAccountId($key),
            'position' => $position,
            'amount' => $amount,
            'posted_at' => now(),
            'posted_by' => $userId,
        ]);
    }

    private function coaAccountId(SimpananCOAConfigEnum $key): int
    {
        $accountId = CoaConfig::query()->where('key', $key->value)->value('coa_account_id');

        if (blank($accountId)) {
            throw new RuntimeException("Konfigurasi COA {$key->value} wajib diisi sebelum transaksi simpanan.");
        }

        return (int) $accountId;
    }

    private function journalUserId(): int
    {
        $userId = auth()->id() ?? User::query()->orderBy('id')->value('id');

        if (blank($userId)) {
            throw new RuntimeException('Tidak ada pengguna untuk mencatat jurnal simpanan.');
        }

        return (int) $userId;
    }
}

<?php

namespace Modules\Dashboard\Services;

use App\Models\CoaAccount;
use App\Models\Jurnal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Keuangan\Enums\KeuanganCOAConfigEnum;
use Modules\Keuangan\Models\CoaConfig as KeuanganCoaConfig;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CoaConfig as PembayaranCoaConfig;
use Modules\Peminjaman\Enums\PeminjamanCOAConfigEnum;
use Modules\Peminjaman\Enums\StatusJadwalAngsuran;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Models\CoaConfig as PeminjamanCoaConfig;
use Modules\Peminjaman\Models\JadwalAngsuran;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use Modules\Simpanan\Enums\SimpananCOAConfigEnum;
use Modules\Simpanan\Models\CoaConfig as SimpananCoaConfig;

class DashboardService
{
    /**
     * Saldo kas berjenjang: total di depan, rincian per akun kas di belakang.
     *
     * @return array{total: float, accounts: array<int, array<string, mixed>>}
     */
    public function kas(): array
    {
        $accountIds = collect([
            PembayaranCoaConfig::query()->where('key', PembayaranCOAConfigEnum::PaymentCash->value)->value('coa_account_id'),
            PeminjamanCoaConfig::query()->where('key', PeminjamanCOAConfigEnum::DisbursementCash->value)->value('coa_account_id'),
            SimpananCoaConfig::query()->where('key', SimpananCOAConfigEnum::SavingsCash->value)->value('coa_account_id'),
            KeuanganCoaConfig::query()->where('key', KeuanganCOAConfigEnum::Kas->value)->value('coa_account_id'),
        ])
            ->filter()
            ->map(fn (mixed $accountId): int => (int) $accountId)
            ->unique()
            ->values();

        $accounts = CoaAccount::query()
            ->whereIn('id', $accountIds)
            ->orderBy('code')
            ->get()
            ->map(function (CoaAccount $account): array {
                $debit = (float) Jurnal::query()
                    ->where('coa_account_id', $account->getKey())
                    ->where('position', 'DEBIT')
                    ->where('status', 'POSTED')
                    ->sum('amount');
                $credit = (float) Jurnal::query()
                    ->where('coa_account_id', $account->getKey())
                    ->where('position', 'CREDIT')
                    ->where('status', 'POSTED')
                    ->sum('amount');

                return [
                    'kode' => $account->code,
                    'nama' => $account->name,
                    'saldo' => round($debit - $credit, 2),
                ];
            });

        return [
            'total' => round((float) $accounts->sum(fn (array $row): float => $row['saldo']), 2),
            'accounts' => $accounts->all(),
        ];
    }

    /**
     * @return array{sisa_pokok: float, pinjaman: int}
     */
    public function piutang(): array
    {
        $peminjamanIds = Peminjaman::query()
            ->where('status', StatusPeminjaman::Disbursed->value)
            ->pluck('id');

        $sisa = (float) JadwalAngsuran::query()
            ->whereIn('peminjaman_id', $peminjamanIds)
            ->selectRaw('COALESCE(SUM(pokok - dibayar_pokok), 0) as sisa')
            ->value('sisa');

        return [
            'sisa_pokok' => round($sisa, 2),
            'pinjaman' => $peminjamanIds->count(),
        ];
    }

    /**
     * @return array{angsuran: int, jumlah: float}
     */
    public function tunggakan(?CarbonImmutable $asOf = null): array
    {
        $asOf ??= CarbonImmutable::today();

        $installments = $this->overdueInstallments($asOf);

        $jumlah = round((float) $installments->sum(
            fn (JadwalAngsuran $installment): float => max(
                0.0,
                (float) $installment->total - (float) $installment->dibayar_pokok - (float) $installment->dibayar_bunga,
            ),
        ), 2);

        return [
            'angsuran' => $installments->count(),
            'jumlah' => $jumlah,
        ];
    }

    public function dendaBelumDicairkan(?CarbonImmutable $asOf = null): float
    {
        $asOf ??= CarbonImmutable::today();
        $service = app(PeminjamanService::class);

        $total = $this->overdueInstallments($asOf)
            ->sum(fn (JadwalAngsuran $installment): float => $service->dendaBelumDibayar($installment, $asOf));

        return round((float) $total, 2);
    }

    /**
     * Ringkasan pendapatan & pengeluaran dari seluruh jurnal akun pendapatan
     * dan beban dalam periode — mencakup transaksi kas operasional maupun
     * pendapatan pinjaman (administrasi, provisi, bunga, denda).
     *
     * @return array{pendapatan: float, pengeluaran: float, selisih: float, pendapatan_detail: array<int, array<string, mixed>>, pengeluaran_detail: array<int, array<string, mixed>>}
     */
    public function keuangan(string $dari, string $sampai): array
    {
        $pendapatanDetail = $this->ledgerRows('REVENUE', $dari, $sampai);
        $pengeluaranDetail = $this->ledgerRows('EXPENSE', $dari, $sampai);

        $pendapatan = round((float) $pendapatanDetail->sum(fn (array $row): float => $row['nominal']), 2);
        $pengeluaran = round((float) $pengeluaranDetail->sum(fn (array $row): float => $row['nominal']), 2);

        return [
            'pendapatan' => $pendapatan,
            'pengeluaran' => $pengeluaran,
            'selisih' => round($pendapatan - $pengeluaran, 2),
            'pendapatan_detail' => $pendapatanDetail->all(),
            'pengeluaran_detail' => $pengeluaranDetail->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function ledgerRows(string $accountTypeCode, string $dari, string $sampai)
    {
        return CoaAccount::query()
            ->whereHas('accountType', fn ($query) => $query->where('code', $accountTypeCode))
            ->withSum([
                'jurnals as debit_total' => fn ($query) => $query
                    ->where('position', 'DEBIT')
                    ->where('status', 'POSTED')
                    ->whereBetween('jurnal_date', [$dari, $sampai]),
            ], 'amount')
            ->withSum([
                'jurnals as credit_total' => fn ($query) => $query
                    ->where('position', 'CREDIT')
                    ->where('status', 'POSTED')
                    ->whereBetween('jurnal_date', [$dari, $sampai]),
            ], 'amount')
            ->orderBy('code')
            ->get()
            ->map(function (CoaAccount $account) use ($accountTypeCode): array {
                $debit = (float) ($account->debit_total ?? 0);
                $credit = (float) ($account->credit_total ?? 0);
                $nominal = $accountTypeCode === 'REVENUE'
                    ? round($credit - $debit, 2)
                    : round($debit - $credit, 2);

                return [
                    'kode' => $account->code,
                    'nama' => $account->name,
                    'nominal' => $nominal,
                ];
            })
            ->filter(fn (array $row): bool => $row['nominal'] != 0.0)
            ->values();
    }

    /**
     * @return array{dari: string, sampai: string, label: string}
     */
    public function periode(string $filter, ?string $dari = null, ?string $sampai = null): array
    {
        $today = CarbonImmutable::today();

        return match ($filter) {
            'hari' => [
                'dari' => $today->toDateString(),
                'sampai' => $today->toDateString(),
                'label' => 'Hari ini',
            ],
            'rentang' => [
                'dari' => $dari ?? $today->startOfMonth()->toDateString(),
                'sampai' => $sampai ?? $today->toDateString(),
                'label' => 'Rentang tanggal',
            ],
            default => [
                'dari' => $today->startOfMonth()->toDateString(),
                'sampai' => $today->endOfMonth()->toDateString(),
                'label' => 'Bulan ini',
            ],
        };
    }

    /**
     * @return iterable<JadwalAngsuran>
     */
    private function overdueInstallments(CarbonImmutable $asOf)
    {
        $peminjamanIds = Peminjaman::query()
            ->whereIn('status', [StatusPeminjaman::Disbursed->value, StatusPeminjaman::Lunas->value])
            ->pluck('id');

        return JadwalAngsuran::query()
            ->whereIn('peminjaman_id', $peminjamanIds)
            ->where('status', '!=', StatusJadwalAngsuran::Lunas->value)
            ->whereDate('jatuh_tempo', '<', $asOf->toDateString())
            ->orderBy('jatuh_tempo')
            ->get();
    }
}

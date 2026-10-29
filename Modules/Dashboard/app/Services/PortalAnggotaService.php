<?php

namespace Modules\Dashboard\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Pembayaran\Models\Pembayaran;
use Modules\Peminjaman\Enums\StatusJadwalAngsuran;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use Modules\Simpanan\Enums\StatusKewajiban;
use Modules\Simpanan\Models\KewajibanSimpanan;
use Modules\Simpanan\Services\SimpananService;

class PortalAnggotaService
{
    /**
     * Satu ringkasan portal untuk anggota: simpanan, kredit aktif,
     * status pengajuan, denda, pembayaran terdekat, dan riwayat.
     *
     * @return array<string, mixed>
     */
    public function ringkasan(Anggota $anggota): array
    {
        $peminjamanService = app(PeminjamanService::class);
        $simpananService = app(SimpananService::class);

        $loans = Peminjaman::query()
            ->with('jadwalAngsurans')
            ->where('anggota_id', $anggota->getKey())
            ->latest('id')
            ->get();

        $pengajuanStatuses = [
            StatusPeminjaman::Draft,
            StatusPeminjaman::Submitted,
            StatusPeminjaman::InReview,
            StatusPeminjaman::ReadyForApproval,
            StatusPeminjaman::Approved,
            StatusPeminjaman::CancellationRequested,
            StatusPeminjaman::Rejected,
            StatusPeminjaman::Cancelled,
        ];

        $pengajuan = $loans
            ->whereIn('status', $pengajuanStatuses)
            ->map(fn (Peminjaman $peminjaman): array => [
                'peminjaman' => $peminjaman,
                'status' => $peminjaman->status,
                'alasan_penolakan' => $peminjaman->rejection_reason,
                'diajukan' => $peminjaman->submitted_at,
            ]);

        $kreditAktif = $loans
            ->where('status', StatusPeminjaman::Disbursed)
            ->map(function (Peminjaman $peminjaman) use ($peminjamanService): array {
                $schedule = $peminjaman->jadwalAngsurans->sortBy('angsuran_ke');
                $next = $schedule->first(fn ($row): bool => $row->status !== StatusJadwalAngsuran::Lunas);

                return [
                    'peminjaman' => $peminjaman,
                    'sisa_pokok' => round((float) $schedule->sum(
                        fn ($row): float => (float) $row->pokok - (float) $row->dibayar_pokok,
                    ), 2),
                    'next' => $next,
                    'denda' => round((float) $schedule->sum(
                        fn ($row): float => $peminjamanService->dendaBelumDibayar($row),
                    ), 2),
                ];
            });

        $riwayat = $loans
            ->whereIn('status', [StatusPeminjaman::Lunas, StatusPeminjaman::Rejected, StatusPeminjaman::Cancelled])
            ->map(fn (Peminjaman $peminjaman): array => [
                'peminjaman' => $peminjaman,
                'sisa_pokok' => round((float) $peminjaman->jadwalAngsurans->sum(
                    fn ($row): float => (float) $row->pokok - (float) $row->dibayar_pokok,
                ), 2),
            ]);

        $simpanan = $simpananService->saldoAnggota($anggota);
        $kewajiban = KewajibanSimpanan::query()
            ->where('anggota_id', $anggota->getKey())
            ->where('status', StatusKewajiban::Pending->value)
            ->get();

        $pembayaran = Pembayaran::query()
            ->with('peminjaman')
            ->where('anggota_id', $anggota->getKey())
            ->latest('payment_date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Pembayaran $row): array => [
                'tanggal' => $row->payment_date,
                'nomor_pinjaman' => $row->peminjaman?->loan_number,
                'jumlah' => (float) $row->amount,
                'jenis' => $row->payment_type,
                'angsuran_ke' => $row->installment_number,
            ]);

        return [
            'simpanan' => $simpanan,
            'kewajiban' => [
                'jumlah' => $kewajiban->count(),
                'total' => round((float) $kewajiban->sum('nominal'), 2),
            ],
            'pengajuan' => $pengajuan->values(),
            'kredit_aktif' => $kreditAktif->values(),
            'riwayat' => $riwayat->values(),
            'pembayaran' => $pembayaran->all(),
            'total_sisa_pokok' => round((float) $kreditAktif->sum('sisa_pokok'), 2),
            'total_denda' => round((float) $kreditAktif->sum('denda'), 2),
            'pembayaran_terdekat' => $this->pembayaranTerdekat($kreditAktif),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $kreditAktif
     * @return array{nominal: float, tanggal: string}|null
     */
    private function pembayaranTerdekat($kreditAktif): ?array
    {
        $next = $kreditAktif
            ->filter(fn (array $row): bool => $row['next'] !== null)
            ->sortBy(fn (array $row): string => $row['next']->jatuh_tempo->toDateString())
            ->first();

        if ($next === null) {
            return null;
        }

        return [
            'nominal' => (float) $next['next']->total,
            'tanggal' => CarbonImmutable::parse($next['next']->jatuh_tempo)->toDateString(),
            'nomor_pinjaman' => $next['peminjaman']->loan_number,
        ];
    }
}

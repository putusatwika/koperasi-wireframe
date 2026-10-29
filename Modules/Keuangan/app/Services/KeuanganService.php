<?php

namespace Modules\Keuangan\Services;

use App\Services\Accounting\JurnalService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Keuangan\Enums\KeuanganCOAConfigEnum;
use Modules\Keuangan\Enums\TipeKategoriKeuangan;
use Modules\Keuangan\Enums\TipeTransaksiKeuangan;
use Modules\Keuangan\Models\CoaConfig;
use Modules\Keuangan\Models\KategoriKeuangan;
use Modules\Keuangan\Models\TransaksiKeuangan;
use RuntimeException;

class KeuanganService
{
    public function __construct(private readonly JurnalService $jurnalService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTransaksi(array $data): TransaksiKeuangan
    {
        abort_unless(auth()->user()?->can('keuangan.transaksi.create') ?? false, 403);

        return DB::transaction(function () use ($data): TransaksiKeuangan {
            $userId = auth()->id();

            if (blank($userId)) {
                throw new RuntimeException('Pengguna terautentikasi diperlukan untuk mencatat transaksi keuangan.');
            }

            $kategori = KategoriKeuangan::query()->findOrFail($data['kategori_id'] ?? null);

            if (! $kategori->is_active) {
                throw new InvalidArgumentException('Kategori keuangan tidak aktif.');
            }

            if ($kategori->coa_account_id === null) {
                throw new InvalidArgumentException("Kategori {$kategori->nama} belum memiliki akun COA.");
            }

            $nominal = $this->positiveAmount($data['nominal'] ?? null);
            $tipe = $kategori->tipe === TipeKategoriKeuangan::Pendapatan
                ? TipeTransaksiKeuangan::Masuk
                : TipeTransaksiKeuangan::Keluar;

            $transaksi = TransaksiKeuangan::query()->create([
                'nomor' => $this->nextNomor(),
                'tipe' => $tipe,
                'kategori_id' => $kategori->getKey(),
                'tanggal' => $data['tanggal'] ?? CarbonImmutable::now()->toDateString(),
                'nominal' => $nominal,
                'keterangan' => $data['keterangan'] ?? null,
                'referensi' => $data['referensi'] ?? null,
                'lampiran' => $data['lampiran'] ?? null,
                'created_by' => $userId,
            ]);

            $this->postJournals($transaksi, $kategori, $nominal, $userId);

            return $transaksi->refresh();
        });
    }

    /**
     * @return array{pendapatan: float, pengeluaran: float, selisih: float}
     */
    public function ringkasan(?string $dari = null, ?string $sampai = null): array
    {
        $query = TransaksiKeuangan::query();

        if (filled($dari)) {
            $query->whereDate('tanggal', '>=', $dari);
        }

        if (filled($sampai)) {
            $query->whereDate('tanggal', '<=', $sampai);
        }

        $pendapatan = (float) (clone $query)
            ->where('tipe', TipeTransaksiKeuangan::Masuk->value)
            ->sum('nominal');
        $pengeluaran = (float) (clone $query)
            ->where('tipe', TipeTransaksiKeuangan::Keluar->value)
            ->sum('nominal');

        return [
            'pendapatan' => round($pendapatan, 2),
            'pengeluaran' => round($pengeluaran, 2),
            'selisih' => round($pendapatan - $pengeluaran, 2),
        ];
    }

    private function postJournals(TransaksiKeuangan $transaksi, KategoriKeuangan $kategori, float $nominal, int $userId): void
    {
        $kasAccountId = $this->coaAccountId(KeuanganCOAConfigEnum::Kas);
        $kategoriAccountId = (int) $kategori->coa_account_id;

        // Aturan emas: kas masuk = debit kas / kredit pendapatan;
        // kas keluar = debit beban / kredit kas.
        [$debitAccountId, $creditAccountId] = $transaksi->tipe === TipeTransaksiKeuangan::Masuk
            ? [$kasAccountId, $kategoriAccountId]
            : [$kategoriAccountId, $kasAccountId];

        $journalData = [
            'jurnal_date' => $transaksi->tanggal->toDateString(),
            'source_module' => 'Keuangan',
            'source_id' => $transaksi->getKey(),
            'status' => 'POSTED',
            'created_by' => $userId,
            'posted_at' => now(),
            'posted_by' => $userId,
            'amount' => $nominal,
        ];

        $this->jurnalService->create([
            ...$journalData,
            'jurnal_number' => "KEUANGAN-{$transaksi->getKey()}-DEBIT",
            'description' => "Kas {$transaksi->tipe->value} {$kategori->nama}",
            'coa_account_id' => $debitAccountId,
            'position' => 'DEBIT',
        ]);

        $this->jurnalService->create([
            ...$journalData,
            'jurnal_number' => "KEUANGAN-{$transaksi->getKey()}-CREDIT",
            'description' => "Kas {$transaksi->tipe->value} {$kategori->nama}",
            'coa_account_id' => $creditAccountId,
            'position' => 'CREDIT',
        ]);
    }

    private function coaAccountId(KeuanganCOAConfigEnum $key): int
    {
        $accountId = CoaConfig::query()->where('key', $key->value)->value('coa_account_id');

        if (blank($accountId)) {
            throw new RuntimeException("Konfigurasi COA {$key->value} wajib diisi sebelum transaksi keuangan.");
        }

        return (int) $accountId;
    }

    private function nextNomor(): string
    {
        return 'KEU-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function positiveAmount(mixed $value): float
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            throw new InvalidArgumentException('Nominal transaksi keuangan harus lebih besar dari nol.');
        }

        return round((float) $value, 2);
    }
}

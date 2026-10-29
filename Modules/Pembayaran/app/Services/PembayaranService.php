<?php

namespace Modules\Pembayaran\Services;

use App\Services\Accounting\JurnalService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Pembayaran\Enums\JenisPembayaran;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CoaConfig;
use Modules\Pembayaran\Models\Pembayaran;
use Modules\Peminjaman\Enums\StatusJadwalAngsuran;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Models\JadwalAngsuran;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class PembayaranService
{
    public function __construct(private readonly JurnalService $jurnalService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createMemberPayment(array $data): Pembayaran
    {
        $user = auth()->user();
        $anggotaId = $user?->anggota?->getKey();

        if (blank($anggotaId)) {
            throw new RuntimeException('Data anggota untuk user yang sedang login belum tersedia.');
        }

        $data['anggota_id'] = $anggotaId;

        return $this->createPayment($data, JenisPembayaran::Manual, $anggotaId);
    }

    /**
     * Record a manual payment made by the treasurer on behalf of a member.
     *
     * @param  array<string, mixed>  $data
     */
    public function createManualPayment(array $data): Pembayaran
    {
        abort_unless(auth()->user()?->can('pembayaran.pembayaran.create') ?? false, 403);

        return $this->createPayment($data, JenisPembayaran::Manual);
    }

    /**
     * Record the first installment deducted from the disbursement. The journals
     * for this deduction are posted by the loan disbursement itself, so this
     * only stores the payment history entry without posting again.
     */
    public function recordFirstInstallmentDeduction(
        Peminjaman $peminjaman,
        float $principal,
        float $interest,
        string $paymentDate,
        int $userId,
    ): Pembayaran {
        return Pembayaran::query()->create([
            'payment_number' => $this->nextPaymentNumber(),
            'peminjaman_id' => $peminjaman->getKey(),
            'anggota_id' => $peminjaman->anggota_id,
            'payment_type' => JenisPembayaran::Potongan,
            'installment_number' => 1,
            'payment_date' => $paymentDate,
            'amount' => round($principal + $interest, 2),
            'principal_amount' => round($principal, 2),
            'interest_amount' => round($interest, 2),
            'penalty_amount' => 0,
            'notes' => 'Potongan cicilan pertama saat pencairan',
            'created_by' => $userId,
        ]);
    }

    /**
     * @return array{sisa_pokok: float, sisa_bunga: float, penalti: float, total: float}
     */
    public function ringkasanPelunasanDipercepat(Peminjaman $peminjaman): array
    {
        $installments = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->get();

        $sisaPokok = round(max(0.0, (float) $peminjaman->loan_amount - (float) $installments->sum('dibayar_pokok')), 2);
        $sisaBunga = round(max(0.0, (float) $installments->reduce(
            fn (float $carry, JadwalAngsuran $installment): float => $carry
                + (float) $installment->bunga
                - (float) $installment->dibayar_bunga,
            0.0,
        )), 2);
        $penalti = app(PeminjamanService::class)->hitungPenaltiPelunasan($peminjaman);

        return [
            'sisa_pokok' => $sisaPokok,
            'sisa_bunga' => $sisaBunga,
            'penalti' => $penalti,
            'total' => round($sisaPokok + $sisaBunga + $penalti, 2),
        ];
    }

    /**
     * Settle the remaining principal, outstanding interest, and the early
     * payoff penalty in a single balanced payment.
     *
     * @param  array<string, mixed>  $meta
     */
    public function settleEarly(Peminjaman $peminjaman, array $meta = []): Pembayaran
    {
        abort_unless(auth()->user()?->can('pembayaran.pembayaran.create') ?? false, 403);

        $ringkasan = $this->ringkasanPelunasanDipercepat($peminjaman);

        if ($ringkasan['total'] <= 0) {
            throw new InvalidArgumentException('Pinjaman sudah lunas.');
        }

        $payment = $this->createPayment([
            'peminjaman_id' => $peminjaman->getKey(),
            'payment_date' => $meta['payment_date'] ?? now()->toDateString(),
            'amount' => $ringkasan['total'],
            'principal_amount' => $ringkasan['sisa_pokok'],
            'interest_amount' => $ringkasan['sisa_bunga'],
            'penalty_amount' => $ringkasan['penalti'],
            'reference_number' => $meta['reference_number'] ?? null,
            'receipt_document' => $meta['receipt_document'] ?? null,
            'notes' => $meta['notes'] ?? 'Pelunasan dipercepat',
        ], JenisPembayaran::Manual);

        $this->tandaiLunasOtomatis($peminjaman->refresh(), $payment);

        return $payment;
    }

    /**
     * Setelah pelunasan dipercepat sukses seluruh angsuran berstatus LUNAS,
     * sehingga pinjaman otomatis ditandai lunas. Tombol "Tandai Lunas" manual
     * tetap tersedia untuk kasus lain.
     */
    private function tandaiLunasOtomatis(Peminjaman $peminjaman, Pembayaran $payment): void
    {
        $masihAdaAngsuran = JadwalAngsuran::query()
            ->where('peminjaman_id', $peminjaman->getKey())
            ->where('status', '!=', StatusJadwalAngsuran::Lunas->value)
            ->exists();

        if ($masihAdaAngsuran || $peminjaman->status !== StatusPeminjaman::Disbursed) {
            return;
        }

        $peminjaman->update([
            'status' => StatusPeminjaman::Lunas,
            'lunas_at' => now(),
            'lunas_by' => auth()->id() ?? $payment->created_by,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAutodebetPayment(array $data): Pembayaran
    {
        abort_unless(auth()->user()?->can('pembayaran.autodebet.create') ?? false, 403);

        return $this->createPayment($data, JenisPembayaran::Autodebet);
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    public function importAutodebetRows(Collection $rows): int
    {
        abort_unless(auth()->user()?->can('pembayaran.autodebet.create') ?? false, 403);

        return DB::transaction(function () use ($rows): int {
            $imported = 0;

            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $data = $this->rowData($row, $line);
                $peminjaman = Peminjaman::query()
                    ->where('loan_number', $data['loan_number'])
                    ->where('status', StatusPeminjaman::Disbursed)
                    ->whereHas('anggota', fn ($query) => $query->where('member_number', $data['member_number']))
                    ->first();

                if ($peminjaman === null) {
                    throw new InvalidArgumentException("Baris {$line}: nomor pinjaman atau nomor anggota tidak ditemukan.");
                }

                $data['peminjaman_id'] = $peminjaman->getKey();
                $data['anggota_id'] = $peminjaman->anggota_id;
                $this->createPayment($data, JenisPembayaran::Autodebet);
                $imported++;
            }

            return $imported;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createPayment(array $data, JenisPembayaran $type, ?int $allowedAnggotaId = null): Pembayaran
    {
        return DB::transaction(function () use ($data, $type, $allowedAnggotaId): Pembayaran {
            $userId = auth()->id();

            if (blank($userId)) {
                throw new RuntimeException('Pengguna terautentikasi diperlukan untuk mencatat pembayaran.');
            }

            $peminjaman = Peminjaman::query()
                ->whereKey($data['peminjaman_id'] ?? null)
                ->where('status', StatusPeminjaman::Disbursed)
                ->with('anggota')
                ->first();

            if ($peminjaman === null) {
                throw new InvalidArgumentException('Pinjaman yang dipilih belum berstatus dicairkan.');
            }

            if ($allowedAnggotaId !== null && (int) $peminjaman->anggota_id !== $allowedAnggotaId) {
                throw new RuntimeException('Anda hanya dapat membayar pinjaman milik sendiri.');
            }

            $amount = $this->positiveAmount($data['amount'] ?? null, 'Jumlah pembayaran');
            $principal = $this->componentAmount($data['principal_amount'] ?? null);
            $interest = $this->componentAmount($data['interest_amount'] ?? null);
            $penalty = $this->componentAmount($data['penalty_amount'] ?? null);

            if ($principal + $interest + $penalty === 0.0) {
                $principal = $amount;
            }

            if (round($principal + $interest + $penalty, 2) !== round($amount, 2)) {
                throw new InvalidArgumentException('Total pokok, bunga, dan denda harus sama dengan jumlah pembayaran.');
            }

            $pembayaran = Pembayaran::query()->create([
                ...Arr::except($data, ['payment_number', 'payment_type']),
                'payment_number' => $this->nextPaymentNumber(),
                'peminjaman_id' => $peminjaman->getKey(),
                'anggota_id' => $peminjaman->anggota_id,
                'payment_type' => $type,
                'amount' => $amount,
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'penalty_amount' => $penalty,
                'created_by' => $userId,
            ]);

            $this->postJournals($pembayaran, $principal, $interest, $penalty, $userId);
            $this->applyToSchedule($pembayaran, $principal, $interest, $penalty);

            return $pembayaran->refresh();
        });
    }

    /** @param array<string, mixed> $row */
    private function rowData(array $row, int $line): array
    {
        $value = fn (string $key): mixed => $row[$key] ?? null;
        $loanNumber = trim((string) $value('nomor_pinjaman'));
        $memberNumber = trim((string) ($value('nomor_anggota') ?? $value('member_number')));
        $amount = $value('jumlah');

        if (blank($loanNumber) || blank($memberNumber) || ! is_numeric($amount) || (float) $amount <= 0) {
            throw new InvalidArgumentException("Baris {$line}: nomor pinjaman, nomor anggota, dan jumlah wajib valid.");
        }

        return [
            'loan_number' => $loanNumber,
            'member_number' => $memberNumber,
            'payment_date' => $this->excelDate($value('tanggal_pembayaran')),
            'installment_number' => $value('angsuran_ke'),
            'amount' => $amount,
            'principal_amount' => $value('pokok'),
            'interest_amount' => $value('bunga'),
            'penalty_amount' => $value('denda'),
            'reference_number' => $value('nomor_referensi'),
            'notes' => $value('catatan'),
        ];
    }

    private function positiveAmount(mixed $value, string $label): float
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            throw new InvalidArgumentException("{$label} harus lebih besar dari nol.");
        }

        return round((float) $value, 2);
    }

    private function componentAmount(mixed $value): float
    {
        return is_numeric($value) && (float) $value >= 0 ? round((float) $value, 2) : 0.0;
    }

    private function excelDate(mixed $value): string
    {
        if (blank($value)) {
            return now()->toDateString();
        }

        if (is_numeric($value) && (float) $value > 1 && (float) $value < 100000) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return (string) $value;
    }

    public function applyToSchedule(Pembayaran $payment, float $principal, float $interest, float $penalty = 0.0): void
    {
        $this->allocatePenalty($payment, $penalty);

        $sisaPokok = $principal;
        $sisaBunga = $interest;

        $installments = JadwalAngsuran::query()
            ->where('peminjaman_id', $payment->peminjaman_id)
            ->where('status', '!=', StatusJadwalAngsuran::Lunas->value)
            ->orderBy('angsuran_ke')
            ->get();

        foreach ($installments as $installment) {
            if ($sisaPokok <= 0 && $sisaBunga <= 0) {
                break;
            }

            $butuhPokok = max(0, (float) $installment->pokok - (float) $installment->dibayar_pokok);
            $bayarPokok = min($sisaPokok, $butuhPokok);
            $butuhBunga = max(0, (float) $installment->bunga - (float) $installment->dibayar_bunga);
            $bayarBunga = min($sisaBunga, $butuhBunga);

            $installment->dibayar_pokok = round((float) $installment->dibayar_pokok + $bayarPokok, 2);
            $installment->dibayar_bunga = round((float) $installment->dibayar_bunga + $bayarBunga, 2);
            $sisaPokok = round($sisaPokok - $bayarPokok, 2);
            $sisaBunga = round($sisaBunga - $bayarBunga, 2);

            $lunas = (float) $installment->dibayar_pokok >= (float) $installment->pokok
                && (float) $installment->dibayar_bunga >= (float) $installment->bunga;

            $installment->status = $lunas
                ? StatusJadwalAngsuran::Lunas
                : (((float) $installment->dibayar_pokok > 0 || (float) $installment->dibayar_bunga > 0)
                    ? StatusJadwalAngsuran::Sebagian
                    : StatusJadwalAngsuran::Belum);

            if ($lunas) {
                $installment->dibayar_pada = $payment->payment_date;
            }

            $installment->save();
        }
    }

    /**
     * Collect the recorded penalty against the earliest installments that still
     * have accrued late fees, so the outstanding penalty figure stays accurate.
     */
    private function allocatePenalty(Pembayaran $payment, float $penalty): void
    {
        $sisa = round($penalty, 2);

        if ($sisa <= 0) {
            return;
        }

        $peminjamanService = app(PeminjamanService::class);

        $installments = JadwalAngsuran::query()
            ->where('peminjaman_id', $payment->peminjaman_id)
            ->orderBy('angsuran_ke')
            ->get();

        foreach ($installments as $installment) {
            if ($sisa <= 0) {
                break;
            }

            $dendaTerutang = $peminjamanService->dendaBelumDibayar(
                $installment,
                CarbonImmutable::parse($payment->payment_date),
            );

            if ($dendaTerutang <= 0) {
                continue;
            }

            $dibayar = min($sisa, $dendaTerutang);
            $installment->denda_dibayar = round((float) $installment->denda_dibayar + $dibayar, 2);
            $sisa = round($sisa - $dibayar, 2);
            $installment->save();
        }
    }

    private function postJournals(Pembayaran $payment, float $principal, float $interest, float $penalty, int $userId): void
    {
        $cashAccountId = $this->coaAccountId(PembayaranCOAConfigEnum::PaymentCash);
        $this->jurnalService->create([
            'jurnal_number' => "PEMBAYARAN-{$payment->getKey()}-DEBIT",
            'jurnal_date' => $payment->payment_date->toDateString(),
            'source_module' => 'Pembayaran',
            'source_id' => $payment->getKey(),
            'description' => "Pembayaran {$payment->payment_number}",
            'status' => 'POSTED',
            'created_by' => $userId,
            'coa_account_id' => $cashAccountId,
            'position' => 'DEBIT',
            'amount' => $payment->amount,
            'posted_at' => now(),
            'posted_by' => $userId,
        ]);

        foreach ([
            [PembayaranCOAConfigEnum::LoanReceivable, $principal, 'Pokok'],
            [PembayaranCOAConfigEnum::InterestIncome, $interest, 'Bunga'],
            [PembayaranCOAConfigEnum::PenaltyIncome, $penalty, 'Denda'],
        ] as [$key, $amount, $label]) {
            if ($amount <= 0) {
                continue;
            }

            $this->jurnalService->create([
                'jurnal_number' => "PEMBAYARAN-{$payment->getKey()}-".strtoupper($label),
                'jurnal_date' => $payment->payment_date->toDateString(),
                'source_module' => 'Pembayaran',
                'source_id' => $payment->getKey(),
                'description' => "Pembayaran {$label} {$payment->payment_number}",
                'status' => 'POSTED',
                'created_by' => $userId,
                'coa_account_id' => $this->coaAccountId($key),
                'position' => 'CREDIT',
                'amount' => $amount,
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);
        }
    }

    private function coaAccountId(PembayaranCOAConfigEnum $key): int
    {
        $accountId = CoaConfig::query()->where('key', $key->value)->value('coa_account_id');

        if (blank($accountId)) {
            throw new RuntimeException("Konfigurasi COA {$key->value} wajib diisi sebelum pembayaran.");
        }

        return (int) $accountId;
    }

    private function nextPaymentNumber(): string
    {
        return 'PAY-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

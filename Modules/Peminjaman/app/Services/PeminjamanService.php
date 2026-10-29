<?php

namespace Modules\Peminjaman\Services;

use App\Services\Accounting\JurnalService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\PeminjamanCOAConfigEnum;
use Modules\Peminjaman\Enums\StatusJadwalAngsuran;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Enums\TahapPenolakanPeminjaman;
use Modules\Peminjaman\Enums\TipeSimulasiPeminjaman;
use Modules\Peminjaman\Events\PinjamanDicairkan;
use Modules\Peminjaman\Models\ApprovalWorkflow;
use Modules\Peminjaman\Models\ApprovalWorkflowStep;
use Modules\Peminjaman\Models\CoaConfig;
use Modules\Peminjaman\Models\JadwalAngsuran;
use Modules\Peminjaman\Models\JenisPinjaman;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Models\SimulasiPeminjaman;
use RuntimeException;

class PeminjamanService
{
    public function __construct(private readonly JurnalService $jurnalService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, bool $forOtherMember = false): Peminjaman
    {
        return DB::transaction(function () use ($data, $forOtherMember): Peminjaman {
            $user = auth()->user();
            $userId = $user?->getKey();

            if (blank($userId)) {
                throw new RuntimeException('Pengguna terautentikasi diperlukan untuk membuat pengajuan.');
            }

            $ownAnggotaId = $user->anggota?->getKey();
            $selectedAnggotaId = (int) ($data['anggota_id'] ?? 0);
            $isSuperAdmin = $user->hasRole('super_admin');

            if ($forOtherMember) {
                if (! $isSuperAdmin && ! $user->can('peminjaman.pengajuan.create_any')) {
                    throw new RuntimeException('Anda tidak memiliki permission untuk membuat pengajuan anggota.');
                }

                if ($ownAnggotaId !== null && $selectedAnggotaId === (int) $ownAnggotaId) {
                    throw new InvalidArgumentException('Pengajuan atas nama sendiri harus menggunakan page Ajukan Pinjaman.');
                }

                $anggota = Anggota::query()->where('status', 'active')->findOrFail($selectedAnggotaId);
            } else {
                if (! $isSuperAdmin && ! $user->can('peminjaman.pengajuan.create')) {
                    throw new RuntimeException('Anda tidak memiliki permission untuk membuat pengajuan.');
                }

                if ($ownAnggotaId === null) {
                    throw new RuntimeException('Data anggota untuk user yang sedang login belum tersedia.');
                }

                $anggota = Anggota::query()->where('status', 'active')->findOrFail($ownAnggotaId);
                $data['anggota_id'] = $ownAnggotaId;
            }

            $jenisPinjamanId = $data['jenis_pinjaman_id'] ?? null;

            if (filled($jenisPinjamanId)) {
                $jenisPinjaman = JenisPinjaman::query()->find($jenisPinjamanId);

                if ($jenisPinjaman !== null) {
                    $data['biaya_administrasi'] = $jenisPinjaman->biaya_administrasi;
                    $data['biaya_provisi_persen'] = $jenisPinjaman->biaya_provisi_persen;
                    $data['denda_keterlambatan_persen'] = $jenisPinjaman->denda_keterlambatan_persen;
                    $data['penalti_pelunasan_persen'] = $jenisPinjaman->penalti_pelunasan_persen;
                }
            }

            $peminjaman = Peminjaman::query()->create([
                ...Arr::except($data, ['loan_number', 'status']),
                'loan_number' => $this->nextLoanNumber(),
                'account_number' => $data['account_number'] ?? $anggota->account_number,
                'status' => StatusPeminjaman::Submitted,
                'submitted_at' => now(),
                'submitted_by' => $userId,
            ]);

            return $peminjaman;
        });
    }

    /**
     * Create a saved simulation that can be completed before submission.
     *
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, bool $forOtherMember = false): Peminjaman
    {
        return DB::transaction(function () use ($data, $forOtherMember): Peminjaman {
            $peminjaman = $this->create([...$data, 'status' => StatusPeminjaman::Draft], $forOtherMember);

            $peminjaman->update([
                'status' => StatusPeminjaman::Draft,
                'submitted_at' => null,
                'submitted_by' => null,
            ]);

            $this->startApprovalWorkflow($peminjaman, 'loan');

            return $peminjaman->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSimulation(array $data): SimulasiPeminjaman
    {
        return DB::transaction(function () use ($data): SimulasiPeminjaman {
            if (($data['jenis_pinjaman_id'] ?? null) === 'custom') {
                $data['jenis_pinjaman_id'] = null;
            }

            return SimulasiPeminjaman::query()->create($data);
        });
    }

    public function submit(Peminjaman $peminjaman): Peminjaman
    {
        $this->ensureStatus($peminjaman, StatusPeminjaman::Draft);

        $peminjaman->update([
            'status' => StatusPeminjaman::Submitted,
            'submitted_at' => now(),
            'submitted_by' => auth()->id(),
        ]);

        return $peminjaman->refresh();
    }

    /**
     * Update an application while preserving the completed default simulation.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateApplication(Peminjaman $peminjaman, array $data): Peminjaman
    {
        $simulationType = $data['simulation_type'] ?? $peminjaman->simulation_type;

        if ($simulationType === TipeSimulasiPeminjaman::Default || $simulationType === TipeSimulasiPeminjaman::Default->value) {
            $data = Arr::except($data, [
                'interest_type',
                'interest_rate',
                'term_months',
                'loan_amount',
                'purpose',
            ]);
        }

        $peminjaman->update($data);

        return $peminjaman->refresh();
    }

    public function review(Peminjaman $peminjaman): Peminjaman
    {
        $this->ensureStatus($peminjaman, StatusPeminjaman::Submitted);

        $workflow = $this->activeWorkflow($peminjaman, 'loan', true);
        $step = $this->ensureWorkflowStep($workflow, 'verification');
        $step->update(['status' => 'IN_PROGRESS']);
        $workflow->update(['status' => 'IN_PROGRESS']);

        $peminjaman->update([
            'status' => StatusPeminjaman::InReview,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return $peminjaman->refresh();
    }

    public function completeReview(Peminjaman $peminjaman, ?string $rekomendasi = null): Peminjaman
    {
        $this->ensureStatus($peminjaman, StatusPeminjaman::InReview);

        $this->completeWorkflowStep($peminjaman, 'loan', 'verification');

        $update = ['status' => StatusPeminjaman::ReadyForApproval];

        if (filled($rekomendasi)) {
            $update += [
                'rekomendasi' => $rekomendasi,
                'rekomendasi_by' => auth()->id(),
                'rekomendasi_at' => now(),
            ];
        }

        $peminjaman->update($update);

        return $peminjaman->refresh();
    }

    public function requestCancellation(Peminjaman $peminjaman, string $reason): Peminjaman
    {
        if (! in_array($peminjaman->status, [StatusPeminjaman::Submitted, StatusPeminjaman::InReview], true)) {
            throw new InvalidArgumentException('Pembatalan hanya dapat diajukan sebelum persetujuan Ketua.');
        }

        if (blank($reason)) {
            throw new InvalidArgumentException('Alasan pembatalan wajib diisi.');
        }

        $peminjaman->update([
            'status' => StatusPeminjaman::CancellationRequested,
            'cancellation_previous_status' => $peminjaman->status,
            'cancellation_reason' => $reason,
            'cancellation_requested_at' => now(),
            'cancellation_requested_by' => auth()->id(),
        ]);

        $this->startApprovalWorkflow($peminjaman, 'cancellation');

        return $peminjaman->refresh();
    }

    public function approveCancellation(Peminjaman $peminjaman): Peminjaman
    {
        $this->ensureStatus($peminjaman, StatusPeminjaman::CancellationRequested);

        $workflow = $this->activeWorkflow($peminjaman, 'cancellation');
        $this->completeWorkflowStep($peminjaman, 'cancellation', $workflow?->currentStep()?->step_key);
        $workflow?->refresh();

        if ($workflow?->status === 'COMPLETED') {
            $peminjaman->update([
                'status' => StatusPeminjaman::Cancelled,
                'cancellation_approved_at' => now(),
                'cancellation_approved_by' => auth()->id(),
            ]);
        }

        return $peminjaman->refresh();
    }

    public function rejectCancellation(Peminjaman $peminjaman): Peminjaman
    {
        $this->ensureStatus($peminjaman, StatusPeminjaman::CancellationRequested);

        $peminjaman->update([
            'status' => $peminjaman->cancellation_previous_status ?? StatusPeminjaman::Submitted,
            'cancellation_previous_status' => null,
            'cancellation_reason' => null,
            'cancellation_requested_at' => null,
            'cancellation_requested_by' => null,
        ]);

        $this->activeWorkflow($peminjaman, 'cancellation')?->update(['status' => 'REJECTED']);

        return $peminjaman->refresh();
    }

    public function approve(Peminjaman $peminjaman): Peminjaman
    {
        $this->ensureStatus($peminjaman, StatusPeminjaman::ReadyForApproval);

        $this->completeWorkflowStep($peminjaman, 'loan', 'ketua_approval');

        $nextStep = $this->currentWorkflowStep($peminjaman, 'loan');

        $peminjaman->update([
            'status' => $nextStep?->step_key === 'disbursement' || $nextStep === null
                ? StatusPeminjaman::Approved
                : StatusPeminjaman::ReadyForApproval,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return $peminjaman->refresh();
    }

    public function rejectDuringReview(Peminjaman $peminjaman, string $reason): Peminjaman
    {
        $this->ensureStatus($peminjaman, StatusPeminjaman::InReview);

        return $this->reject($peminjaman, $reason, TahapPenolakanPeminjaman::Pemeriksaan);
    }

    public function rejectByApproval(Peminjaman $peminjaman, string $reason): Peminjaman
    {
        $this->ensureStatus($peminjaman, StatusPeminjaman::ReadyForApproval);

        return $this->reject($peminjaman, $reason, TahapPenolakanPeminjaman::Persetujuan);
    }

    private function reject(Peminjaman $peminjaman, string $reason, TahapPenolakanPeminjaman $stage): Peminjaman
    {

        if (blank($reason)) {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        $peminjaman->update([
            'status' => StatusPeminjaman::Rejected,
            'rejection_stage' => $stage,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
        ]);

        $this->activeWorkflow($peminjaman, 'loan')?->update(['status' => 'REJECTED']);

        return $peminjaman->refresh();
    }

    public function cancel(Peminjaman $peminjaman): Peminjaman
    {
        if (! in_array($peminjaman->status, [StatusPeminjaman::Submitted, StatusPeminjaman::InReview], true)) {
            throw new InvalidArgumentException('Peminjaman hanya dapat dibatalkan sebelum disetujui.');
        }

        $peminjaman->update([
            'status' => StatusPeminjaman::Cancelled,
        ]);

        return $peminjaman->refresh();
    }

    public function disburse(Peminjaman $peminjaman): Peminjaman
    {
        return DB::transaction(function () use ($peminjaman): Peminjaman {
            $this->ensureStatus($peminjaman, StatusPeminjaman::Approved);

            $userId = auth()->id();

            if (blank($userId)) {
                throw new RuntimeException('Pengguna terautentikasi diperlukan untuk pencairan.');
            }

            $this->generateSchedule($peminjaman, CarbonImmutable::now());

            $firstInstallment = $peminjaman->jadwalAngsurans()->orderBy('angsuran_ke')->firstOrFail();

            $amount = round((float) $peminjaman->loan_amount, 2);
            $adminFee = round((float) $peminjaman->biaya_administrasi, 2);
            $provisionFee = round($amount * ((float) $peminjaman->biaya_provisi_persen / 100), 2);
            $firstPrincipal = round((float) $firstInstallment->pokok, 2);
            $firstInterest = round((float) $firstInstallment->bunga, 2);
            $firstTotal = round((float) $firstInstallment->total, 2);
            $netDisbursed = round($amount - $adminFee - $provisionFee - $firstTotal, 2);

            if ($netDisbursed < 0) {
                throw new InvalidArgumentException('Biaya administrasi, provisi, dan cicilan pertama melebihi jumlah pinjaman.');
            }

            $journalPrefix = "PEMINJAMAN-{$peminjaman->getKey()}";
            $journalData = [
                'jurnal_date' => now()->toDateString(),
                'source_module' => 'Peminjaman',
                'source_id' => $peminjaman->getKey(),
                'status' => 'POSTED',
                'created_by' => $userId,
                'posted_at' => now(),
                'posted_by' => $userId,
            ];

            $this->jurnalService->create([
                ...$journalData,
                'jurnal_number' => "{$journalPrefix}-RECEIVABLE",
                'description' => "Pencairan pinjaman {$peminjaman->loan_number}",
                'coa_account_id' => $this->coaAccountId(PeminjamanCOAConfigEnum::LoanReceivable),
                'position' => 'DEBIT',
                'amount' => $amount,
            ]);

            $credits = [
                [PeminjamanCOAConfigEnum::DisbursementCash, $netDisbursed, 'CASH'],
                [PeminjamanCOAConfigEnum::AdminIncome, $adminFee, 'ADMIN'],
                [PeminjamanCOAConfigEnum::ProvisionIncome, $provisionFee, 'PROVISION'],
                [PeminjamanCOAConfigEnum::LoanReceivable, $firstPrincipal, 'FIRST-PRINCIPAL'],
                [PeminjamanCOAConfigEnum::InterestIncome, $firstInterest, 'FIRST-INTEREST'],
            ];

            foreach ($credits as [$coaKey, $creditAmount, $suffix]) {
                if ($creditAmount <= 0) {
                    continue;
                }

                $this->jurnalService->create([
                    ...$journalData,
                    'jurnal_number' => "{$journalPrefix}-{$suffix}",
                    'description' => "Pencairan pinjaman {$peminjaman->loan_number} ({$suffix})",
                    'coa_account_id' => $this->coaAccountId($coaKey),
                    'position' => 'CREDIT',
                    'amount' => $creditAmount,
                ]);
            }

            $firstInstallment->update([
                'dibayar_pokok' => $firstPrincipal,
                'dibayar_bunga' => $firstInterest,
                'status' => StatusJadwalAngsuran::Lunas,
                'dibayar_pada' => now()->toDateString(),
            ]);

            PinjamanDicairkan::dispatch($peminjaman, $firstPrincipal, $firstInterest, now()->toDateString(), (int) $userId);

            $this->completeWorkflowStep($peminjaman, 'loan', 'disbursement');

            $peminjaman->update([
                'status' => StatusPeminjaman::Disbursed,
                'disbursed_at' => now(),
                'disbursed_by' => $userId,
            ]);

            return $peminjaman->refresh();
        });
    }

    public function generateSchedule(Peminjaman $peminjaman, ?CarbonImmutable $disbursedAt = null): void
    {
        if ($peminjaman->jadwalAngsurans()->exists()) {
            return;
        }

        $disbursedAt ??= CarbonImmutable::parse($peminjaman->disbursed_at ?? now());
        $interestType = $peminjaman->interest_type;

        $result = $this->simulate([
            'loan_amount' => $peminjaman->loan_amount,
            'interest_rate' => $peminjaman->interest_rate,
            'term_months' => $peminjaman->term_months,
            'interest_type' => $interestType instanceof JenisBungaPeminjaman ? $interestType->value : $interestType,
        ]);

        $firstDueDate = $disbursedAt;

        foreach ($result['schedule'] as $row) {
            JadwalAngsuran::query()->create([
                'peminjaman_id' => $peminjaman->getKey(),
                'angsuran_ke' => $row['month'],
                'jatuh_tempo' => $firstDueDate->addMonthsNoOverflow($row['month'] - 1)->toDateString(),
                'pokok' => $row['principal'],
                'bunga' => $row['interest'],
                'total' => $row['payment'],
                'dibayar_pokok' => 0,
                'dibayar_bunga' => 0,
                'status' => StatusJadwalAngsuran::Belum,
            ]);
        }
    }

    public function hitungDenda(JadwalAngsuran $installment, ?CarbonImmutable $asOf = null): float
    {
        if ($installment->status === StatusJadwalAngsuran::Lunas) {
            return 0.0;
        }

        $asOf = ($asOf ?? CarbonImmutable::now())->startOfDay();

        if ($installment->jatuh_tempo === null || ! $installment->jatuh_tempo->startOfDay()->lt($asOf)) {
            return 0.0;
        }

        $rate = (float) ($installment->peminjaman?->denda_keterlambatan_persen ?? 0);

        if ($rate <= 0) {
            return 0.0;
        }

        $outstanding = max(
            0.0,
            (float) $installment->total - (float) $installment->dibayar_pokok - (float) $installment->dibayar_bunga,
        );

        if ($outstanding <= 0) {
            return 0.0;
        }

        $days = (int) $installment->jatuh_tempo->startOfDay()->diffInDays($asOf);
        $denda = $days * ($rate / 100) * $outstanding;

        return round(min($denda, $outstanding), 2);
    }

    public function dendaBelumDibayar(JadwalAngsuran $installment, ?CarbonImmutable $asOf = null): float
    {
        $denda = $this->hitungDenda($installment, $asOf);

        if ($denda <= 0) {
            return 0.0;
        }

        return round(max(0.0, $denda - (float) $installment->denda_dibayar), 2);
    }

    public function hitungPenaltiPelunasan(Peminjaman $peminjaman): float
    {
        $rate = (float) $peminjaman->penalti_pelunasan_persen;

        if ($rate <= 0) {
            return 0.0;
        }

        $sisaPokok = (float) $peminjaman->loan_amount
            - (float) $peminjaman->jadwalAngsurans()->sum('dibayar_pokok');

        return round(max(0.0, $sisaPokok) * ($rate / 100), 2);
    }

    public function tandaiLunas(Peminjaman $peminjaman): Peminjaman
    {
        abort_unless(auth()->user()?->can('peminjaman.daftar_peminjaman.lunas') ?? false, 403);

        if ($peminjaman->status === StatusPeminjaman::Lunas) {
            throw new InvalidArgumentException('Pinjaman sudah ditandai lunas.');
        }

        if ($peminjaman->status !== StatusPeminjaman::Disbursed) {
            throw new InvalidArgumentException('Hanya pinjaman yang telah dicairkan dapat ditandai lunas.');
        }

        $userId = auth()->id();

        if (blank($userId)) {
            throw new RuntimeException('Pengguna terautentikasi diperlukan untuk menandai lunas.');
        }

        $peminjaman->update([
            'status' => StatusPeminjaman::Lunas,
            'lunas_at' => now(),
            'lunas_by' => $userId,
        ]);

        return $peminjaman->refresh();
    }

    /** @param  array<string, mixed>  $data */
    public function simulate(array $data): array
    {
        $principal = (float) ($data['loan_amount'] ?? 0);
        $annualRate = (float) ($data['interest_rate'] ?? 0);
        $termMonths = (int) ($data['term_months'] ?? 0);
        $interestValue = $data['interest_type'] ?? null;
        $interestType = $interestValue instanceof JenisBungaPeminjaman
            ? $interestValue
            : JenisBungaPeminjaman::tryFrom((string) $interestValue);

        if ($principal <= 0 || $termMonths <= 0 || $interestType === null) {
            throw new InvalidArgumentException('Nominal, tenor, dan jenis bunga wajib valid.');
        }

        $monthlyRate = $annualRate / 100 / 12;
        $annuityPayment = $monthlyRate > 0
            ? $principal * ($monthlyRate * ((1 + $monthlyRate) ** $termMonths)) / (((1 + $monthlyRate) ** $termMonths) - 1)
            : $principal / $termMonths;
        $schedule = [];
        $remainingPrincipal = round($principal, 2);

        for ($month = 1; $month <= $termMonths; $month++) {
            $openingBalance = $remainingPrincipal;
            $interest = match ($interestType) {
                JenisBungaPeminjaman::Flat => round($principal * $monthlyRate, 2),
                JenisBungaPeminjaman::Effective, JenisBungaPeminjaman::Annuity => round($openingBalance * $monthlyRate, 2),
            };

            $principalPayment = match ($interestType) {
                JenisBungaPeminjaman::Flat, JenisBungaPeminjaman::Effective => round($principal / $termMonths, 2),
                JenisBungaPeminjaman::Annuity => round($annuityPayment - $interest, 2),
            };

            if ($month === $termMonths) {
                $principalPayment = $openingBalance;
            }

            $principalPayment = min($principalPayment, $openingBalance);
            $payment = round($principalPayment + $interest, 2);
            $remainingPrincipal = max(0, round($openingBalance - $principalPayment, 2));

            $schedule[] = [
                'month' => $month,
                'opening_balance' => $openingBalance,
                'principal' => $principalPayment,
                'interest' => $interest,
                'payment' => $payment,
                'ending_balance' => $remainingPrincipal,
            ];
        }

        $totalInterest = round(array_sum(array_column($schedule, 'interest')), 2);
        $totalRepayment = round(array_sum(array_column($schedule, 'payment')), 2);
        $monthlyPayment = $interestType === JenisBungaPeminjaman::Annuity
            ? round($annuityPayment, 2)
            : ($schedule[0]['payment'] ?? 0);

        return [
            'monthly_payment' => round($monthlyPayment, 2),
            'total_interest' => $totalInterest,
            'total_repayment' => $totalRepayment,
            'schedule' => $schedule,
        ];
    }

    private function nextLoanNumber(): string
    {
        $nextId = ((int) Peminjaman::withTrashed()->max('id')) + 1;

        return 'PJM-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }

    public function startApprovalWorkflow(Peminjaman $peminjaman, string $type): ApprovalWorkflow
    {
        return $this->activeWorkflow($peminjaman, $type, true);
    }

    public function currentWorkflowStep(Peminjaman $peminjaman, string $type = 'loan'): ?ApprovalWorkflowStep
    {
        return $this->activeWorkflow($peminjaman, $type)?->currentStep();
    }

    public function processCurrentWorkflowStep(Peminjaman $peminjaman, string $type = 'loan'): Peminjaman
    {
        $step = $this->currentWorkflowStep($peminjaman, $type);

        if ($step === null) {
            throw new InvalidArgumentException('Workflow aktif tidak ditemukan.');
        }

        if ($type === 'cancellation') {
            return $this->approveCancellation($peminjaman);
        }

        return match ($step->step_key) {
            'verification' => $peminjaman->status === StatusPeminjaman::Submitted
                ? $this->review($peminjaman)
                : $this->completeReview($peminjaman),
            'ketua_approval' => $this->approve($peminjaman),
            'disbursement' => $this->disburse($peminjaman),
            default => $this->completeAdditionalApprovalStep($peminjaman),
        };
    }

    public function rejectCurrentWorkflowStep(Peminjaman $peminjaman, string $reason, string $type = 'loan'): Peminjaman
    {
        if ($type === 'cancellation') {
            return $this->rejectCancellation($peminjaman);
        }

        $step = $this->currentWorkflowStep($peminjaman, $type);

        if ($step?->step_key === 'verification') {
            return $this->rejectDuringReview($peminjaman, $reason);
        }

        return $this->rejectByApproval($peminjaman, $reason);
    }

    private function activeWorkflow(Peminjaman $peminjaman, string $type, bool $create = false): ?ApprovalWorkflow
    {
        $workflow = $peminjaman->approvalWorkflows()
            ->where('type', $type)
            ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
            ->latest('id')
            ->first();

        if ($workflow !== null || ! $create) {
            return $workflow;
        }

        $definitions = Arr::get(config('peminjaman.approval_workflows', []), $type, []);

        if ($definitions === []) {
            throw new RuntimeException("Workflow approval [{$type}] belum dikonfigurasi.");
        }

        $workflow = $peminjaman->approvalWorkflows()->create([
            'type' => $type,
            'status' => 'PENDING',
            'current_step' => 1,
            'requested_by' => auth()->id(),
        ]);

        foreach ($definitions as $index => $definition) {
            $workflow->steps()->create([
                'step_order' => $index + 1,
                'step_key' => $definition['key'],
                'step_name' => $definition['name'],
                'permission' => $definition['permission'],
            ]);
        }

        return $workflow->refresh();
    }

    private function ensureWorkflowStep(ApprovalWorkflow $workflow, string $stepKey): ApprovalWorkflowStep
    {
        $step = $workflow->currentStep();

        if ($step?->step_key !== $stepKey) {
            throw new InvalidArgumentException("Tahap workflow harus {$stepKey}.");
        }

        return $step;
    }

    private function completeWorkflowStep(Peminjaman $peminjaman, string $type, ?string $expectedStepKey): void
    {
        if ($expectedStepKey === null) {
            throw new InvalidArgumentException('Tahap workflow aktif tidak ditemukan.');
        }

        $workflow = $this->activeWorkflow($peminjaman, $type, true);
        $step = $this->ensureWorkflowStep($workflow, $expectedStepKey);

        $step->update([
            'status' => 'APPROVED',
            'acted_by' => auth()->id(),
            'acted_at' => now(),
        ]);

        $nextStep = $workflow->steps()->where('status', 'PENDING')->orderBy('step_order')->first();

        if ($nextStep === null) {
            $workflow->update([
                'status' => 'COMPLETED',
                'completed_at' => now(),
            ]);

            return;
        }

        $workflow->update([
            'status' => 'IN_PROGRESS',
            'current_step' => $nextStep->step_order,
        ]);
    }

    private function completeAdditionalApprovalStep(Peminjaman $peminjaman): Peminjaman
    {
        $this->completeWorkflowStep($peminjaman, 'loan', $this->currentWorkflowStep($peminjaman)?->step_key);
        $nextStep = $this->currentWorkflowStep($peminjaman);

        $peminjaman->update([
            'status' => $nextStep?->step_key === 'disbursement' || $nextStep === null
                ? StatusPeminjaman::Approved
                : StatusPeminjaman::ReadyForApproval,
        ]);

        return $peminjaman->refresh();
    }

    private function ensureStatus(Peminjaman $peminjaman, StatusPeminjaman $expected): void
    {
        if ($peminjaman->status !== $expected) {
            throw new InvalidArgumentException("Status pinjaman harus {$expected->value}.");
        }
    }

    private function coaAccountId(PeminjamanCOAConfigEnum $key): int
    {
        $accountId = CoaConfig::query()->where('key', $key->value)->value('coa_account_id');

        if (blank($accountId)) {
            throw new RuntimeException("Konfigurasi COA {$key->value} wajib diisi sebelum pencairan.");
        }

        return (int) $accountId;
    }
}

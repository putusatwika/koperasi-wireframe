<?php

namespace Modules\Peminjaman\Models;

use App\Models\User;
use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Peminjaman\Database\Factories\PeminjamanFactory;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Enums\TahapPenolakanPeminjaman;
use Modules\Peminjaman\Enums\TipeSimulasiPeminjaman;

#[Fillable([
    'loan_number', 'anggota_id', 'jenis_pinjaman_id', 'account_number', 'simulation_type', 'interest_type',
    'interest_rate', 'term_months', 'loan_amount', 'monthly_income', 'dependents_count', 'purpose',
    'urgency', 'purpose_description', 'need_description', 'funds_needed_at', 'repayment_source',
    'guarantee_required',
    'penalty_rate', 'late_fee', 'biaya_administrasi', 'biaya_provisi_persen',
    'denda_keterlambatan_persen', 'penalti_pelunasan_persen', 'rekomendasi', 'rekomendasi_by',
    'rekomendasi_at', 'statement_document', 'identity_document',
    'income_document', 'guarantee_document', 'supporting_documents', 'approval_document',
    'status', 'rejection_stage', 'rejection_reason', 'submitted_at', 'reviewed_at', 'approved_at',
    'rejected_at', 'disbursed_at', 'submitted_by', 'reviewed_by', 'approved_by',
    'rejected_by', 'disbursed_by', 'lunas_at', 'lunas_by', 'cancellation_previous_status', 'cancellation_reason',
    'cancellation_requested_at', 'cancellation_requested_by', 'cancellation_approved_at',
    'cancellation_approved_by',
])]
class Peminjaman extends Model
{
    use HasFactory, HasModuleTable, SoftDeletes;
    use UseLogger;

    protected $table = 'peminjaman';

    protected $attributes = [
        'status' => StatusPeminjaman::Submitted->value,
        'simulation_type' => TipeSimulasiPeminjaman::Default->value,
        'guarantee_required' => false,
        'penalty_rate' => 0,
        'late_fee' => 0,
        'biaya_administrasi' => 0,
        'biaya_provisi_persen' => 0,
        'denda_keterlambatan_persen' => 0.1,
        'penalti_pelunasan_persen' => 0,
    ];

    protected static function newFactory(): PeminjamanFactory
    {
        return PeminjamanFactory::new();
    }

    protected function casts(): array
    {
        return [
            'simulation_type' => TipeSimulasiPeminjaman::class,
            'interest_type' => JenisBungaPeminjaman::class,
            'status' => StatusPeminjaman::class,
            'rejection_stage' => TahapPenolakanPeminjaman::class,
            'interest_rate' => 'decimal:4',
            'loan_amount' => 'decimal:2',
            'monthly_income' => 'decimal:2',
            'dependents_count' => 'integer',
            'funds_needed_at' => 'date',
            'penalty_rate' => 'decimal:4',
            'late_fee' => 'decimal:2',
            'biaya_administrasi' => 'decimal:2',
            'biaya_provisi_persen' => 'decimal:4',
            'denda_keterlambatan_persen' => 'decimal:4',
            'penalti_pelunasan_persen' => 'decimal:4',
            'guarantee_required' => 'boolean',
            'supporting_documents' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'lunas_at' => 'datetime',
            'rekomendasi_at' => 'datetime',
            'cancellation_previous_status' => StatusPeminjaman::class,
            'cancellation_requested_at' => 'datetime',
            'cancellation_approved_at' => 'datetime',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function jenisPinjaman(): BelongsTo
    {
        return $this->belongsTo(JenisPinjaman::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function lunasBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lunas_by');
    }

    public function rekomendasiBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rekomendasi_by');
    }

    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }

    public function jadwalAngsurans(): HasMany
    {
        return $this->hasMany(JadwalAngsuran::class);
    }
}

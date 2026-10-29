<?php

namespace Modules\Pembayaran\Models;

use App\Models\User;
use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Pembayaran\Enums\JenisPembayaran;
use Modules\Peminjaman\Models\Peminjaman;

#[Fillable([
    'payment_number', 'peminjaman_id', 'anggota_id', 'payment_type', 'installment_number',
    'payment_date', 'amount', 'principal_amount', 'interest_amount', 'penalty_amount',
    'reference_number', 'receipt_document', 'notes', 'created_by',
])]
class Pembayaran extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'pembayaran';

    protected $attributes = [
        'payment_type' => JenisPembayaran::Manual->value,
        'principal_amount' => 0,
        'interest_amount' => 0,
        'penalty_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'payment_type' => JenisPembayaran::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'principal_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

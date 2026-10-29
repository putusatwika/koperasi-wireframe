<?php

namespace Modules\Simpanan\Models;

use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Simpanan\Enums\StatusKewajiban;

#[Fillable([
    'anggota_id', 'jenis_simpanan_id', 'periode', 'nominal', 'jatuh_tempo', 'status',
    'paid_at', 'simpanan_transaksi_id',
])]
class KewajibanSimpanan extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'kewajiban';

    protected $attributes = [
        'status' => StatusKewajiban::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'jatuh_tempo' => 'date',
            'status' => StatusKewajiban::class,
            'paid_at' => 'datetime',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function jenisSimpanan(): BelongsTo
    {
        return $this->belongsTo(JenisSimpanan::class, 'jenis_simpanan_id');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiSimpanan::class, 'simpanan_transaksi_id');
    }
}

<?php

namespace Modules\Peminjaman\Models;

use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;

#[Fillable([
    'anggota_id', 'jenis_pinjaman_id', 'jumlah_pinjaman', 'metode_bunga', 'persentase_bunga_per_bulan', 'tenor_bulan', 'peruntukan',
    'rencana_mulai_cicilan',
])]
class SimulasiPeminjaman extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'simulasi_peminjaman';

    protected function casts(): array
    {
        return [
            'metode_bunga' => JenisBungaPeminjaman::class,
            'jumlah_pinjaman' => 'decimal:2',
            'persentase_bunga_per_bulan' => 'decimal:4',
            'rencana_mulai_cicilan' => 'date',
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
}

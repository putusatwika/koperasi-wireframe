<?php

namespace Modules\Peminjaman\Models;

use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Peminjaman\Enums\StatusJadwalAngsuran;

#[Fillable([
    'peminjaman_id', 'angsuran_ke', 'jatuh_tempo', 'pokok', 'bunga', 'total',
    'dibayar_pokok', 'dibayar_bunga', 'denda_dibayar', 'status', 'dibayar_pada',
])]
class JadwalAngsuran extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'jadwal_angsuran';

    protected $attributes = [
        'dibayar_pokok' => 0,
        'dibayar_bunga' => 0,
        'denda_dibayar' => 0,
        'status' => StatusJadwalAngsuran::Belum->value,
    ];

    protected function casts(): array
    {
        return [
            'angsuran_ke' => 'integer',
            'jatuh_tempo' => 'date',
            'pokok' => 'decimal:2',
            'bunga' => 'decimal:2',
            'total' => 'decimal:2',
            'dibayar_pokok' => 'decimal:2',
            'dibayar_bunga' => 'decimal:2',
            'denda_dibayar' => 'decimal:2',
            'status' => StatusJadwalAngsuran::class,
            'dibayar_pada' => 'date',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }
}

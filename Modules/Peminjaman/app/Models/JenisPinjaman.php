<?php

namespace Modules\Peminjaman\Models;

use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;

#[Fillable([
    'kode', 'nama', 'metode_bunga', 'persentase_bunga_per_bulan', 'biaya_administrasi',
    'biaya_provisi_persen', 'denda_keterlambatan_persen', 'penalti_pelunasan_persen',
])]
class JenisPinjaman extends Model
{
    use HasModuleTable, HasUuids;
    use UseLogger;

    protected $table = 'jenis_pinjaman';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $attributes = [
        'biaya_administrasi' => 0,
        'biaya_provisi_persen' => 0,
        'denda_keterlambatan_persen' => 0.1,
        'penalti_pelunasan_persen' => 0,
    ];

    protected function casts(): array
    {
        return [
            'metode_bunga' => JenisBungaPeminjaman::class,
            'persentase_bunga_per_bulan' => 'decimal:4',
            'biaya_administrasi' => 'decimal:2',
            'biaya_provisi_persen' => 'decimal:4',
            'denda_keterlambatan_persen' => 'decimal:4',
            'penalti_pelunasan_persen' => 'decimal:4',
        ];
    }
}

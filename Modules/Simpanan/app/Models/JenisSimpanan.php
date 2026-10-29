<?php

namespace Modules\Simpanan\Models;

use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Simpanan\Enums\KategoriSimpanan;
use Modules\Simpanan\Enums\PeriodikSimpanan;

#[Fillable([
    'kode', 'nama', 'kategori', 'nominal_default', 'wajib', 'periodik', 'berbunga',
    'bunga_per_bulan', 'periode_bunga_bulan', 'tanggal_jatuh_tempo', 'tenor_bulan',
    'is_active', 'deskripsi',
])]
class JenisSimpanan extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'jenis';

    protected $attributes = [
        'nominal_default' => 0,
        'wajib' => false,
        'periodik' => PeriodikSimpanan::None->value,
        'berbunga' => false,
        'bunga_per_bulan' => 0,
        'periode_bunga_bulan' => 1,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'kategori' => KategoriSimpanan::class,
            'periodik' => PeriodikSimpanan::class,
            'nominal_default' => 'decimal:2',
            'wajib' => 'boolean',
            'berbunga' => 'boolean',
            'bunga_per_bulan' => 'decimal:4',
            'periode_bunga_bulan' => 'integer',
            'tanggal_jatuh_tempo' => 'integer',
            'tenor_bulan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function akuns(): HasMany
    {
        return $this->hasMany(SimpananAkun::class, 'jenis_simpanan_id');
    }

    public function kewajibans(): HasMany
    {
        return $this->hasMany(KewajibanSimpanan::class, 'jenis_simpanan_id');
    }
}

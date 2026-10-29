<?php

namespace Modules\Simpanan\Models;

use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Keanggotaan\Models\Anggota;

#[Fillable(['anggota_id', 'jenis_simpanan_id', 'nomor_rekening', 'saldo', 'status', 'dibuka_pada', 'jatuh_tempo'])]
class SimpananAkun extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'akun';

    protected $attributes = [
        'saldo' => 0,
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'saldo' => 'decimal:2',
            'dibuka_pada' => 'date',
            'jatuh_tempo' => 'date',
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

    public function transaksis(): HasMany
    {
        return $this->hasMany(TransaksiSimpanan::class, 'simpanan_akun_id');
    }
}

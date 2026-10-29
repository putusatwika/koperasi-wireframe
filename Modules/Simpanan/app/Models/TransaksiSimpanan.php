<?php

namespace Modules\Simpanan\Models;

use App\Models\User;
use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Simpanan\Enums\TipeTransaksiSimpanan;

#[Fillable(['simpanan_akun_id', 'tipe', 'tanggal', 'nominal', 'referensi', 'keterangan', 'created_by'])]
class TransaksiSimpanan extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'transaksi';

    protected function casts(): array
    {
        return [
            'tipe' => TipeTransaksiSimpanan::class,
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
        ];
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(SimpananAkun::class, 'simpanan_akun_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

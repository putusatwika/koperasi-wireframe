<?php

namespace Modules\Keuangan\Models;

use App\Models\User;
use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Keuangan\Enums\TipeTransaksiKeuangan;

#[Fillable(['nomor', 'tipe', 'kategori_id', 'tanggal', 'nominal', 'keterangan', 'referensi', 'lampiran', 'created_by'])]
class TransaksiKeuangan extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'transaksi';

    protected function casts(): array
    {
        return [
            'tipe' => TipeTransaksiKeuangan::class,
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriKeuangan::class, 'kategori_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

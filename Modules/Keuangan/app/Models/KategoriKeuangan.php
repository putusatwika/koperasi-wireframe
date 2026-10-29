<?php

namespace Modules\Keuangan\Models;

use App\Models\CoaAccount;
use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Keuangan\Enums\TipeKategoriKeuangan;

#[Fillable(['kode', 'nama', 'tipe', 'coa_account_id', 'is_active', 'deskripsi'])]
class KategoriKeuangan extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'kategori';

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'tipe' => TipeKategoriKeuangan::class,
            'is_active' => 'boolean',
        ];
    }

    public function coaAccount(): BelongsTo
    {
        return $this->belongsTo(CoaAccount::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(TransaksiKeuangan::class, 'kategori_id');
    }
}

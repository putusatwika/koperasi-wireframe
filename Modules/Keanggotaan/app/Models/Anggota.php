<?php

namespace Modules\Keanggotaan\Models;

use App\Models\User;
use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Keanggotaan\Database\Factories\AnggotaFactory;
use Modules\Pembayaran\Models\Pembayaran;

#[Fillable(['user_id', 'member_number', 'account_number', 'identity_number', 'phone', 'address', 'joined_at', 'status'])]
class Anggota extends Model
{
    use HasFactory, HasModuleTable, SoftDeletes;
    use UseLogger;

    protected $attributes = [
        'status' => 'active',
    ];

    protected static function newFactory(): AnggotaFactory
    {
        return AnggotaFactory::new();
    }

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }
}

<?php

namespace Modules\Pembayaran\Models;

use App\Models\User;
use App\Support\Concerns\HasModuleTable;
use App\Support\Concerns\UseLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['opening_balance', 'opening_date', 'created_by', 'updated_by'])]
class CashSetting extends Model
{
    use HasModuleTable;
    use UseLogger;

    protected $table = 'cash_settings';

    protected $attributes = [
        'opening_balance' => 0,
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'opening_date' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

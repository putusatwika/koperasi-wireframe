<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['jurnal_number', 'jurnal_date', 'source_module', 'source_id', 'description', 'line_description', 'status', 'reversal_of_id', 'posted_at', 'posted_by', 'created_by', 'coa_account_id', 'position', 'amount'])]
class Jurnal extends Model
{
    protected $table = 'core_jurnals';

    protected $attributes = [
        'status' => 'DRAFT',
    ];

    protected function casts(): array
    {
        return [
            'jurnal_date' => 'date',
            'source_id' => 'integer',
            'posted_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function coaAccount(): BelongsTo
    {
        return $this->belongsTo(CoaAccount::class);
    }
}

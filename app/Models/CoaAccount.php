<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

#[Fillable(['code', 'key_map', 'name', 'account_type_id', 'normal_balance', 'parent_id', 'level', 'is_postable', 'is_active', 'description'])]
class CoaAccount extends Model
{
    protected $table = 'core_coa_accounts';

    protected $attributes = [
        'level' => 1,
        'is_postable' => true,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $account): void {
            if (! is_string($account->key_map) || preg_match('/^[A-Z0-9_]+$/', $account->key_map) !== 1) {
                throw new InvalidArgumentException('The COA key map must contain only uppercase letters, numbers, and underscores.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_postable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function jurnals(): HasMany
    {
        return $this->hasMany(Jurnal::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_group_id', 'account_type_id', 'display_order'])]
class ReportGroupLine extends Model
{
    protected $table = 'core_report_group_lines';

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    public function reportGroup(): BelongsTo
    {
        return $this->belongsTo(ReportGroup::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }
}

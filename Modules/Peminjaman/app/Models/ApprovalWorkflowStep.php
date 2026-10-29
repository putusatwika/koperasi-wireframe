<?php

namespace Modules\Peminjaman\Models;

use App\Models\User;
use App\Support\Concerns\HasModuleTable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workflow_id', 'step_order', 'step_key', 'step_name', 'permission', 'status',
    'acted_by', 'acted_at', 'reason', 'metadata',
])]
class ApprovalWorkflowStep extends Model
{
    use HasModuleTable;

    protected $table = 'approval_workflow_steps';

    protected $attributes = [
        'status' => 'PENDING',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'acted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}

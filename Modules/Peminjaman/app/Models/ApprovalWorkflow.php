<?php

namespace Modules\Peminjaman\Models;

use App\Models\User;
use App\Support\Concerns\HasModuleTable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['peminjaman_id', 'type', 'status', 'current_step', 'requested_by', 'completed_at'])]
class ApprovalWorkflow extends Model
{
    use HasModuleTable;

    protected $table = 'approval_workflows';

    protected function casts(): array
    {
        return [
            'current_step' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStep::class, 'workflow_id')->orderBy('step_order');
    }

    public function currentStep(): ?ApprovalWorkflowStep
    {
        return $this->steps()->where('step_order', $this->current_step)->first();
    }
}

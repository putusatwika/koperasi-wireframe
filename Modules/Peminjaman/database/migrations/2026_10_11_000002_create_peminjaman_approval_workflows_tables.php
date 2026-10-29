<?php

use App\Support\Concerns\UsesModuleTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use UsesModuleTable;

    public function up(): void
    {
        $workflowTable = $this->moduleTable('Peminjaman', 'approval_workflows');
        $stepTable = $this->moduleTable('Peminjaman', 'approval_workflow_steps');

        Schema::create($workflowTable, function (Blueprint $table): void {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman_peminjaman')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('status', 30)->default('PENDING')->index();
            $table->unsignedInteger('current_step')->default(1);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['peminjaman_id', 'type', 'status']);
        });

        Schema::create($stepTable, function (Blueprint $table) use ($workflowTable): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained($workflowTable)->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('step_key', 60);
            $table->string('step_name', 120);
            $table->string('permission', 120);
            $table->string('status', 30)->default('PENDING')->index();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['workflow_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Peminjaman', 'approval_workflow_steps'));
        Schema::dropIfExists($this->moduleTable('Peminjaman', 'approval_workflows'));
    }
};

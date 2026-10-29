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
        Schema::create($this->moduleTable('Peminjaman', 'peminjaman'), function (Blueprint $table) {
            $table->id();
            $table->string('loan_number', 50)->unique();
            $table->foreignId('anggota_id')->constrained('keanggotaan_anggotas');
            $table->string('account_number', 50);
            $table->string('simulation_type', 30)->default('default');
            $table->string('interest_type', 30);
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->unsignedInteger('term_months');
            $table->decimal('loan_amount', 30, 2);
            $table->string('purpose', 30);
            $table->boolean('guarantee_required')->default(false);
            $table->decimal('penalty_rate', 8, 4)->default(0);
            $table->decimal('late_fee', 30, 2)->default(0);
            $table->string('statement_document')->nullable();
            $table->string('identity_document')->nullable();
            $table->string('income_document')->nullable();
            $table->string('guarantee_document')->nullable();
            $table->json('supporting_documents')->nullable();
            $table->string('approval_document')->nullable();
            $table->string('status', 30)->default('SUBMITTED')->index();
            $table->string('rejection_stage', 30)->nullable();
            $table->string('cancellation_previous_status', 30)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('cancellation_requested_at')->nullable();
            $table->timestamp('cancellation_approved_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancellation_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancellation_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Peminjaman', 'peminjaman'));
    }
};

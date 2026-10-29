<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_jurnals', function (Blueprint $table): void {
            $table->id();
            $table->string('jurnal_number', 50)->index();
            $table->date('jurnal_date')->index();
            $table->string('source_module', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('DRAFT')->index();
            $table->foreignId('reversal_of_id')->nullable()->constrained('core_jurnals')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('coa_account_id')->constrained('core_coa_accounts')->restrictOnDelete();
            $table->string('position', 10);
            $table->decimal('amount', 20, 2);
            $table->text('line_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_jurnals');
    }
};

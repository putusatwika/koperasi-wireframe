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
        Schema::create($this->moduleTable('Pembayaran', 'pembayaran'), function (Blueprint $table): void {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('peminjaman_id')->constrained('peminjaman_peminjaman');
            $table->foreignId('anggota_id')->constrained('keanggotaan_anggotas');
            $table->string('payment_type', 20)->index();
            $table->unsignedInteger('installment_number')->nullable();
            $table->date('payment_date')->index();
            $table->decimal('amount', 30, 2);
            $table->decimal('principal_amount', 30, 2)->default(0);
            $table->decimal('interest_amount', 30, 2)->default(0);
            $table->decimal('penalty_amount', 30, 2)->default(0);
            $table->string('reference_number', 100)->nullable();
            $table->string('receipt_document')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['anggota_id', 'payment_date']);
            $table->index(['peminjaman_id', 'installment_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Pembayaran', 'pembayaran'));
    }
};

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
        Schema::create($this->moduleTable('Simpanan', 'transaksi'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('simpanan_akun_id')->constrained($this->moduleTable('Simpanan', 'akun'))->cascadeOnDelete();
            $table->string('tipe', 20)->index();
            $table->date('tanggal')->index();
            $table->decimal('nominal', 30, 2);
            $table->string('referensi', 100)->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Simpanan', 'transaksi'));
    }
};

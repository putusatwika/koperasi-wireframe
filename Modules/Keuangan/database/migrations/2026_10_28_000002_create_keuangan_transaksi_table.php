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
        Schema::create($this->moduleTable('Keuangan', 'transaksi'), function (Blueprint $table): void {
            $table->id();
            $table->string('nomor', 50)->unique();
            $table->string('tipe', 20)->index();
            $table->foreignId('kategori_id')->constrained($this->moduleTable('Keuangan', 'kategori'))->restrictOnDelete();
            $table->date('tanggal')->index();
            $table->decimal('nominal', 30, 2);
            $table->text('keterangan')->nullable();
            $table->string('referensi', 100)->nullable();
            $table->string('lampiran')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kategori_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Keuangan', 'transaksi'));
    }
};

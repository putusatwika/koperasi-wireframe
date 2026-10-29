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
        Schema::create($this->moduleTable('Simpanan', 'kewajiban'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('anggota_id')->constrained('keanggotaan_anggotas')->cascadeOnDelete();
            $table->foreignId('jenis_simpanan_id')->constrained($this->moduleTable('Simpanan', 'jenis'))->cascadeOnDelete();
            $table->string('periode', 10);
            $table->decimal('nominal', 30, 2);
            $table->date('jatuh_tempo');
            $table->string('status', 20)->default('PENDING')->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('simpanan_transaksi_id')->nullable()->constrained($this->moduleTable('Simpanan', 'transaksi'))->nullOnDelete();
            $table->timestamps();

            $table->unique(['anggota_id', 'jenis_simpanan_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Simpanan', 'kewajiban'));
    }
};

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
        Schema::create($this->moduleTable('Simpanan', 'akun'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('anggota_id')->constrained('keanggotaan_anggotas')->cascadeOnDelete();
            $table->foreignId('jenis_simpanan_id')->constrained($this->moduleTable('Simpanan', 'jenis'))->cascadeOnDelete();
            $table->string('nomor_rekening', 50)->unique();
            $table->decimal('saldo', 30, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->date('dibuka_pada');
            $table->date('jatuh_tempo')->nullable();
            $table->timestamps();

            $table->unique(['anggota_id', 'jenis_simpanan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Simpanan', 'akun'));
    }
};

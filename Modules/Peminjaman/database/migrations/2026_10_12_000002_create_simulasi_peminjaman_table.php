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
        Schema::create($this->moduleTable('Peminjaman', 'simulasi_peminjaman'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('anggota_id')->nullable()->constrained('keanggotaan_anggotas')->nullOnDelete();
            $table->foreignUuid('jenis_pinjaman_id')->nullable()->constrained('peminjaman_jenis_pinjaman')->nullOnDelete();
            $table->decimal('jumlah_pinjaman', 30, 2);
            $table->string('metode_bunga', 30);
            $table->decimal('persentase_bunga_per_bulan', 8, 4);
            $table->unsignedInteger('tenor_bulan');
            $table->string('peruntukan', 30);
            $table->date('rencana_mulai_cicilan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Peminjaman', 'simulasi_peminjaman'));
    }
};

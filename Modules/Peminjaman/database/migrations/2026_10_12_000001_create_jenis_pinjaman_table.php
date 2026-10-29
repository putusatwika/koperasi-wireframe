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
        Schema::create($this->moduleTable('Peminjaman', 'jenis_pinjaman'), function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('kode', 50)->unique();
            $table->string('nama', 150);
            $table->string('metode_bunga', 30);
            $table->decimal('persentase_bunga_per_bulan', 8, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Peminjaman', 'jenis_pinjaman'));
    }
};

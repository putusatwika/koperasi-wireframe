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
        Schema::table($this->moduleTable('Peminjaman', 'jadwal_angsuran'), function (Blueprint $table): void {
            $table->decimal('denda_dibayar', 30, 2)->default(0)->after('dibayar_bunga');
        });
    }

    public function down(): void
    {
        Schema::table($this->moduleTable('Peminjaman', 'jadwal_angsuran'), function (Blueprint $table): void {
            $table->dropColumn('denda_dibayar');
        });
    }
};

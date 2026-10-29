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
        Schema::table($this->moduleTable('Peminjaman', 'jenis_pinjaman'), function (Blueprint $table): void {
            $table->decimal('biaya_administrasi', 15, 2)->default(0)->after('persentase_bunga_per_bulan');
            $table->decimal('biaya_provisi_persen', 8, 4)->default(0)->after('biaya_administrasi');
            $table->decimal('denda_keterlambatan_persen', 8, 4)->default(0.1)->after('biaya_provisi_persen');
            $table->decimal('penalti_pelunasan_persen', 8, 4)->default(0)->after('denda_keterlambatan_persen');
        });
    }

    public function down(): void
    {
        Schema::table($this->moduleTable('Peminjaman', 'jenis_pinjaman'), function (Blueprint $table): void {
            $table->dropColumn([
                'biaya_administrasi',
                'biaya_provisi_persen',
                'denda_keterlambatan_persen',
                'penalti_pelunasan_persen',
            ]);
        });
    }
};

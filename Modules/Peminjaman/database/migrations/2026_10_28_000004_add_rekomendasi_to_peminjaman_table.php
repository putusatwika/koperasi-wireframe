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
        Schema::table($this->moduleTable('Peminjaman', 'peminjaman'), function (Blueprint $table): void {
            $table->text('rekomendasi')->nullable()->after('approval_document');
            $table->foreignId('rekomendasi_by')->nullable()->after('rekomendasi')->constrained('users')->nullOnDelete();
            $table->timestamp('rekomendasi_at')->nullable()->after('rekomendasi_by');
        });
    }

    public function down(): void
    {
        Schema::table($this->moduleTable('Peminjaman', 'peminjaman'), function (Blueprint $table): void {
            $table->dropForeign(['rekomendasi_by']);
            $table->dropColumn(['rekomendasi', 'rekomendasi_by', 'rekomendasi_at']);
        });
    }
};

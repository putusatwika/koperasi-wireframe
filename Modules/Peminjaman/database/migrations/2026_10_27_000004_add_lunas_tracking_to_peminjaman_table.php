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
            $table->timestamp('lunas_at')->nullable()->after('disbursed_at');
            $table->foreignId('lunas_by')->nullable()->after('lunas_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table($this->moduleTable('Peminjaman', 'peminjaman'), function (Blueprint $table): void {
            $table->dropForeign(['lunas_by']);
            $table->dropColumn(['lunas_at', 'lunas_by']);
        });
    }
};

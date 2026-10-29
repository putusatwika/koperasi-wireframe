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
        Schema::create($this->moduleTable('Simpanan', 'jenis'), function (Blueprint $table): void {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100);
            $table->string('kategori', 20)->index();
            $table->decimal('nominal_default', 30, 2)->default(0);
            $table->boolean('wajib')->default(false);
            $table->string('periodik', 20)->default('NONE');
            $table->boolean('berbunga')->default(false);
            $table->decimal('bunga_per_bulan', 8, 4)->default(0);
            $table->unsignedSmallInteger('periode_bunga_bulan')->default(1);
            $table->unsignedTinyInteger('tanggal_jatuh_tempo')->nullable();
            $table->unsignedInteger('tenor_bulan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Simpanan', 'jenis'));
    }
};

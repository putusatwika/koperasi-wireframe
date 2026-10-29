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
        Schema::create($this->moduleTable('Peminjaman', 'jadwal_angsuran'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained($this->moduleTable('Peminjaman', 'peminjaman'))->cascadeOnDelete();
            $table->unsignedInteger('angsuran_ke');
            $table->date('jatuh_tempo')->index();
            $table->decimal('pokok', 30, 2);
            $table->decimal('bunga', 30, 2);
            $table->decimal('total', 30, 2);
            $table->decimal('dibayar_pokok', 30, 2)->default(0);
            $table->decimal('dibayar_bunga', 30, 2)->default(0);
            $table->string('status', 20)->default('BELUM')->index();
            $table->date('dibayar_pada')->nullable();
            $table->timestamps();

            $table->unique(['peminjaman_id', 'angsuran_ke']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Peminjaman', 'jadwal_angsuran'));
    }
};

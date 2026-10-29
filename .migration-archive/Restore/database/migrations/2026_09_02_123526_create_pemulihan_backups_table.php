<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemulihan_backups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('arsip_backup_id')->index();
            $table->foreignId('safety_backup_id')->nullable()->index();
            $table->string('status')->default('requested')->index();
            $table->json('hasil_verifikasi')->nullable();
            $table->text('alasan');
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemulihan_backups');
    }
};

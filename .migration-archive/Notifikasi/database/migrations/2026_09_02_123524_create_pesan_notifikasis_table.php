<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesan_notifikasis', function (Blueprint $table) {
            $table->id();
            $table->uuid('deduplication_key')->unique();
            $table->nullableMorphs('sumber');
            $table->foreignId('anggota_id')->nullable()->index();
            $table->string('channel')->default('whatsapp');
            $table->string('tujuan');
            $table->string('template');
            $table->json('payload');
            $table->string('status')->default('queued')->index();
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->text('hasil')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesan_notifikasis');
    }
};

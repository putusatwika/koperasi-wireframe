<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('core_coa_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('account_type_id')->constrained('core_account_types')->restrictOnDelete();
            $table->string('normal_balance', 10);
            $table->foreignId('parent_id')->nullable()->constrained('core_coa_accounts')->nullOnDelete();
            $table->unsignedSmallInteger('level')->default(1);
            $table->boolean('is_postable')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->string('key_map', 100)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_coa_accounts');
    }
};

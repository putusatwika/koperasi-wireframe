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
        Schema::create('core_report_group_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_group_id')->constrained('core_report_groups')->cascadeOnDelete();
            $table->foreignId('account_type_id')->constrained('core_account_types')->restrictOnDelete();
            $table->unsignedSmallInteger('display_order')->nullable();
            $table->unique(['report_group_id', 'account_type_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_report_group_lines');
    }
};

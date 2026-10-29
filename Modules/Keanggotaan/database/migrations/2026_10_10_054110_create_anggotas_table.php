<?php

use App\Support\Concerns\UsesModuleTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use UsesModuleTable;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->moduleTable('Keanggotaan', 'anggotas'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('member_number', 50)->unique();
            $table->string('account_number', 50)->unique();
            $table->string('identity_number', 50)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->date('joined_at');
            $table->string('status', 30)->default('active')->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Keanggotaan', 'anggotas'));
    }
};

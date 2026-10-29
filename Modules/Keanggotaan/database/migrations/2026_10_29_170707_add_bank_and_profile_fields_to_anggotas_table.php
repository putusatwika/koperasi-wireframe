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
        Schema::table($this->moduleTable('Keanggotaan', 'anggotas'), function (Blueprint $table) {
            $table->string('bank_name', 50)->nullable()->after('account_number');
            $table->string('occupation', 100)->nullable()->after('bank_name');
            $table->decimal('income', 15, 2)->nullable()->after('occupation');
            $table->text('notes')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->moduleTable('Keanggotaan', 'anggotas'), function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'occupation', 'income', 'notes']);
        });
    }
};

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
        Schema::create($this->moduleTable('ExampleModule', 'coa_config'), function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->foreignId('coa_account_id')
                ->nullable()
                ->constrained('core_coa_accounts')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('ExampleModule', 'coa_config'));
    }
};

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
        Schema::create($this->moduleTable('Peminjaman', 'coa_config'), function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->foreignId('coa_account_id')
                ->nullable()
                ->constrained('core_coa_accounts')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->moduleTable('Peminjaman', 'coa_config'));
    }
};

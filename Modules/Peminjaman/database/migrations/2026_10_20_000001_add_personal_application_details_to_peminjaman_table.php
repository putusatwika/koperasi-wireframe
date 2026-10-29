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
        Schema::table($this->moduleTable('Peminjaman', 'peminjaman'), function (Blueprint $table): void {
            $table->decimal('monthly_income', 30, 2)->nullable()->after('loan_amount');
            $table->unsignedSmallInteger('dependents_count')->nullable()->after('monthly_income');
            $table->string('urgency', 30)->nullable()->after('purpose');
            $table->text('purpose_description')->nullable()->after('urgency');
            $table->text('need_description')->nullable()->after('purpose_description');
            $table->date('funds_needed_at')->nullable()->after('need_description');
            $table->text('repayment_source')->nullable()->after('funds_needed_at');
        });
    }

    public function down(): void
    {
        Schema::table($this->moduleTable('Peminjaman', 'peminjaman'), function (Blueprint $table): void {
            $table->dropColumn([
                'monthly_income',
                'dependents_count',
                'urgency',
                'purpose_description',
                'need_description',
                'funds_needed_at',
                'repayment_source',
            ]);
        });
    }
};

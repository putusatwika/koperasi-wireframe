<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('permission.table_names.permissions');

        throw_unless(is_string($tableName), 'Permission table configuration is missing.');

        DB::table($tableName)
            ->whereIn('name', ['backup.view', 'backup.create', 'backup.download', 'backup.delete'])
            ->delete();
    }

    /** Removed module permissions cannot be reconstructed safely. */
    public function down(): void {}
};

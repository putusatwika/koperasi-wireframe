<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CoreAccountingSeeder::class,
            ModuleStatusSeeder::class,
            SystemPermissionSeeder::class,
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
            // ReportGroupSeeder::class,
            // TransactionSeeder::class,
        ]);
    }
}

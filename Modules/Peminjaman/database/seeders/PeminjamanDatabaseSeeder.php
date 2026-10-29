<?php

namespace Modules\Peminjaman\Database\Seeders;

use Illuminate\Database\Seeder;

class PeminjamanDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(PeminjamanCOASeeder::class);
        $this->call(PeminjamanRoleSeeder::class);
        $this->call(PeminjamanUserSeeder::class);
        $this->call(JenisPinjamanSeeder::class);
        $this->call(PeminjamanSeeder::class);
    }
}

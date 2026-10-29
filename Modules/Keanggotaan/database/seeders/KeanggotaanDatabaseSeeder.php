<?php

namespace Modules\Keanggotaan\Database\Seeders;

use Illuminate\Database\Seeder;

class KeanggotaanDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(AnggotaSeeder::class);
    }
}

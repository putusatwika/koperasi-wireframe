<?php

namespace Modules\Keuangan\Database\Seeders;

use Illuminate\Database\Seeder;

class KeuanganDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(KeuanganCOASeeder::class);
        $this->call(KategoriKeuanganSeeder::class);
    }
}

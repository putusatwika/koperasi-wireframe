<?php

namespace Modules\Pembayaran\Database\Seeders;

use Illuminate\Database\Seeder;

class PembayaranDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PembayaranCOASeeder::class);
        $this->call(PembayaranSeeder::class);
    }
}

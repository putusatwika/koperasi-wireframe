<?php

namespace Modules\Simpanan\Database\Seeders;

use Illuminate\Database\Seeder;

class SimpananDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SimpananCOASeeder::class);
        $this->call(JenisSimpananSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CoreAccountingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccountTypeSeeder::class,
            CoaAccountSeeder::class,
        ]);
    }
}

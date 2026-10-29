<?php

namespace Database\Seeders;

use App\Support\ModuleStatusSynchronizer;
use Illuminate\Database\Seeder;

class ModuleStatusSeeder extends Seeder
{
    public function __construct(private readonly ModuleStatusSynchronizer $synchronizer) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->synchronizer->synchronize();
    }
}

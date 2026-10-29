<?php

namespace Database\Seeders;

use App\Support\RolePermissionSynchronizer;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function __construct(private readonly RolePermissionSynchronizer $synchronizer) {}

    public function run(): void
    {
        $this->synchronizer->synchronize();
    }
}

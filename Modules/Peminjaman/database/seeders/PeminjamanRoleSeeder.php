<?php

namespace Modules\Peminjaman\Database\Seeders;

use App\Support\RolePermissionSynchronizer;
use Illuminate\Database\Seeder;

class PeminjamanRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(RolePermissionSynchronizer::class)->synchronize();
    }
}

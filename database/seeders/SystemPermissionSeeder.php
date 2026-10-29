<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SystemPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = array_merge(config('notifications.permissions', []), config('restore.permissions', []));

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}

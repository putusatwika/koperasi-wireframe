<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);

        $user = User::query()->firstOrCreate([
            'email' => 'super@koperasi.id',
        ], [
            'name' => 'Super Admin',
            'password' => 'password',
        ]);

        $user->assignRole($role);
    }
}

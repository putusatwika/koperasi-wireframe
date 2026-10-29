<?php

namespace Modules\Peminjaman\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PeminjamanUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Bendahara Koperasi',
                'email' => 'bendahara@koperasi.id',
                'role' => 'bendahara',
            ],
            [
                'name' => 'Ketua Koperasi',
                'email' => 'ketua@koperasi.id',
                'role' => 'ketua',
            ],
            [
                'name' => 'Pengawas Koperasi',
                'email' => 'pengawas@koperasi.id',
                'role' => 'pengawas',
            ],
            [
                'name' => 'Anggota Koperasi',
                'email' => 'anggota@koperasi.id',
                'role' => 'anggota',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => 'password',
                ],
            );

            $user->assignRole(Role::findByName($userData['role']));
        }
    }
}

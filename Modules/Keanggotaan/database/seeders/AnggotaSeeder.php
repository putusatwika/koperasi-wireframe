<?php

namespace Modules\Keanggotaan\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Keanggotaan\Models\Anggota;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AnggotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        collect(config('keanggotaan.permissions', []))
            ->each(fn (string $permission): Permission => Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]));

        Permission::query()
            ->where('name', 'keanggotaan.portal_anggota.view')
            ->delete();

        $user = User::query()->firstOrCreate(
            ['email' => 'anggota@example.com'],
            [
                'name' => 'Anggota Contoh',
                'password' => 'password',
            ],
        );

        Anggota::withTrashed()->firstOrCreate(
            ['user_id' => $user->getKey()],
            [
                'member_number' => 'AGT-00001',
                'account_number' => '1234567890',
                'identity_number' => '1234567890123456',
                'phone' => '081234567890',
                'address' => 'Alamat anggota contoh',
                'joined_at' => now()->toDateString(),
                'status' => 'active',
            ],
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

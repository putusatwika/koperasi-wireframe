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

        User::query()
            ->role('super_admin')
            ->get()
            ->each(function (User $superAdmin): void {
                $anggota = Anggota::withTrashed()->firstOrCreate(
                    ['user_id' => $superAdmin->getKey()],
                    [
                        'member_number' => 'AGT-SA-'.str_pad((string) $superAdmin->getKey(), 5, '0', STR_PAD_LEFT),
                        'account_number' => 'SA'.str_pad((string) $superAdmin->getKey(), 10, '0', STR_PAD_LEFT),
                        'joined_at' => now()->toDateString(),
                        'status' => 'active',
                    ],
                );

                if ($anggota->trashed()) {
                    $anggota->restore();
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSynchronizer
{
    private const PERMISSION_CONFIG_KEYS = [
        'peminjaman',
        'pembayaran',
        'keanggotaan',
        'simpanan',
        'keuangan',
        'dashboard',
        'notifications',
        'restore',
        'report',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    public function rolePermissions(): array
    {
        return config('roles.roles', []);
    }

    public function synchronize(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $rolePermissions = $this->rolePermissions();

        foreach ($this->permissionNames($rolePermissions) as $permissionName) {
            $this->permission($permissionName, $guard);
        }

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);

            $role->syncPermissions(
                array_map(
                    fn (string $permissionName): Permission => $this->permission($permissionName, $guard),
                    $permissionNames,
                ),
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<string, array<int, string>>  $rolePermissions
     * @return array<int, string>
     */
    private function permissionNames(array $rolePermissions): array
    {
        $fromRoles = array_merge([], ...array_values($rolePermissions));
        $fromConfig = [];

        foreach (self::PERMISSION_CONFIG_KEYS as $key) {
            $permissions = config("{$key}.permissions", []);

            if (is_array($permissions)) {
                $fromConfig = array_merge($fromConfig, $permissions);
            }
        }

        return array_values(array_unique(array_merge($fromConfig, $fromRoles)));
    }

    private function permission(string $name, string $guard): Permission
    {
        return Permission::query()->firstOrCreate([
            'name' => $name,
            'guard_name' => $guard,
        ]);
    }
}

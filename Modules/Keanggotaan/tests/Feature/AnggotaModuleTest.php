<?php

namespace Modules\Keanggotaan\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Keanggotaan\Database\Seeders\AnggotaSeeder;
use Modules\Keanggotaan\Filament\Resources\Anggotas\AnggotaResource;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Keanggotaan\Services\AnggotaService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnggotaModuleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_anggota_uses_the_module_table_and_user_relationship(): void
    {
        $anggota = new Anggota;

        $this->assertSame('keanggotaan_anggotas', $anggota->getTable());
        $this->assertInstanceOf(User::class, $anggota->user()->getRelated());
        $this->assertContains('account_number', $anggota->getFillable());
        $this->assertSame('active', $anggota->status);
    }

    public function test_anggota_resource_requires_permissions(): void
    {
        $user = User::factory()->create();
        $record = new Anggota;

        $this->actingAs($user);

        $this->assertFalse(AnggotaResource::canViewAny());
        $this->assertFalse(AnggotaResource::canView($record));
        $this->assertFalse(AnggotaResource::canCreate());
        $this->assertFalse(AnggotaResource::canEdit($record));
        $this->assertFalse(AnggotaResource::canDelete($record));

        $user->givePermissionTo([
            Permission::findOrCreate('keanggotaan.anggota.viewAny'),
            Permission::findOrCreate('keanggotaan.anggota.view'),
            Permission::findOrCreate('keanggotaan.anggota.create'),
            Permission::findOrCreate('keanggotaan.anggota.update'),
            Permission::findOrCreate('keanggotaan.anggota.delete'),
        ]);

        $this->assertTrue(AnggotaResource::canViewAny());
        $this->assertTrue(AnggotaResource::canView($record));
        $this->assertTrue(AnggotaResource::canCreate());
        $this->assertTrue(AnggotaResource::canEdit($record));
        $this->assertTrue(AnggotaResource::canDelete($record));
    }

    public function test_anggota_module_no_longer_registers_portal_permission(): void
    {
        $this->assertNotContains('keanggotaan.portal_anggota.view', config('keanggotaan.permissions', []));
    }

    public function test_anggota_seeder_creates_member_data_for_super_admin(): void
    {
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        $superAdmin = User::factory()->create(['email' => 'super@koperasi.id']);
        $superAdmin->assignRole(Role::findOrCreate('super_admin'));

        (new AnggotaSeeder)->run();

        $this->assertSame($superAdmin->getKey(), $superAdmin->fresh()->anggota?->user_id);
        $this->assertSame('AGT-SA-'.str_pad((string) $superAdmin->getKey(), 5, '0', STR_PAD_LEFT), $superAdmin->fresh()->anggota?->member_number);
    }

    public function test_anggota_registration_creates_user_and_member_data_together(): void
    {
        User::query()->count();
        $migration = require base_path('Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php');
        $migration->up();

        $anggota = app(AnggotaService::class)->create([
            'user' => [
                'name' => 'Anggota Satu',
                'email' => 'anggota@example.test',
                'password' => 'password123',
            ],
            'member_number' => 'AGT-00001',
            'account_number' => '1234567890',
            'identity_number' => '1234567890123456',
            'phone' => '081234567890',
            'address' => 'Alamat anggota',
            'joined_at' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->assertModelExists($anggota);
        $this->assertModelExists($anggota->user);
        $this->assertSame('anggota@example.test', $anggota->user->email);
        $this->assertTrue(Hash::check('password123', $anggota->user->password));
    }
}

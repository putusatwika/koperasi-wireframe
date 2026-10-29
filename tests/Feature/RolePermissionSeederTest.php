<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('peminjaman', require base_path('Modules/Peminjaman/config/config.php'));
        config()->set('pembayaran', require base_path('Modules/Pembayaran/config/config.php'));
        config()->set('keanggotaan', require base_path('Modules/Keanggotaan/config/config.php'));
    }

    public function test_it_creates_the_cooperative_roles_idempotently(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        foreach (['bendahara', 'ketua', 'pengawas', 'anggota'] as $roleName) {
            $this->assertSame(1, Role::query()->where('name', $roleName)->count());
        }
    }

    public function test_bendahara_manages_cash_while_ketua_and_pengawas_may_only_view_it(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $bendahara = Role::findByName('bendahara');
        $ketua = Role::findByName('ketua');
        $pengawas = Role::findByName('pengawas');

        $this->assertTrue($bendahara->hasPermissionTo('pembayaran.kas.update'));
        $this->assertTrue($ketua->hasPermissionTo('pembayaran.kas.viewAny'));
        $this->assertFalse($ketua->hasPermissionTo('pembayaran.kas.update'));
        $this->assertTrue($pengawas->hasPermissionTo('pembayaran.kas.viewAny'));
        $this->assertFalse($pengawas->hasPermissionTo('pembayaran.kas.update'));
    }

    public function test_pengawas_is_read_only_for_the_loan_workflow(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $pengawas = Role::findByName('pengawas');

        $this->assertTrue($pengawas->hasPermissionTo('peminjaman.persetujuan.viewAny'));
        $this->assertTrue($pengawas->hasPermissionTo('peminjaman.daftar_peminjaman.viewAny'));
        $this->assertFalse($pengawas->hasPermissionTo('peminjaman.persetujuan.approve'));
        $this->assertFalse($pengawas->hasPermissionTo('peminjaman.persetujuan.reject'));
        $this->assertFalse($pengawas->hasPermissionTo('peminjaman.persetujuan.disburse'));
        $this->assertFalse($pengawas->hasPermissionTo('peminjaman.pemeriksaan.process'));
    }

    public function test_anggota_has_member_self_service_permissions_only(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $anggota = Role::findByName('anggota');

        $this->assertTrue($anggota->hasPermissionTo('peminjaman.simulasi.view'));
        $this->assertTrue($anggota->hasPermissionTo('peminjaman.pengajuan.create'));
        $this->assertTrue($anggota->hasPermissionTo('pembayaran.history.view'));
        $this->assertFalse($anggota->hasPermissionTo('peminjaman.pengajuan.viewAny'));
        $this->assertFalse($anggota->hasPermissionTo('pembayaran.kas.viewAny'));
        $this->assertFalse($anggota->hasPermissionTo('peminjaman.persetujuan.approve'));
    }
}

<?php

namespace Modules\Simpanan\Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Simpanan\Database\Seeders\JenisSimpananSeeder;
use Modules\Simpanan\Enums\KategoriSimpanan;
use Modules\Simpanan\Enums\PeriodikSimpanan;
use Modules\Simpanan\Enums\SimpananCOAConfigEnum;
use Modules\Simpanan\Enums\StatusKewajiban;
use Modules\Simpanan\Enums\TipeTransaksiSimpanan;
use Modules\Simpanan\Filament\Pages\InputSimpanan;
use Modules\Simpanan\Filament\Pages\SimpananSaya;
use Modules\Simpanan\Filament\Resources\JenisSimpanan\JenisSimpananResource;
use Modules\Simpanan\Models\CoaConfig;
use Modules\Simpanan\Models\JenisSimpanan;
use Modules\Simpanan\Models\KewajibanSimpanan;
use Modules\Simpanan\Models\SimpananAkun;
use Modules\Simpanan\Services\SimpananService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SimpananModuleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('simpanan', require base_path('Modules/Simpanan/config/config.php'));
        config()->set('keanggotaan', require base_path('Modules/Keanggotaan/config/config.php'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->runModuleMigrations();
    }

    public function test_it_uses_module_tables_and_casts_enums(): void
    {
        $this->assertSame('simpanan_jenis', (new JenisSimpanan)->getTable());
        $this->assertSame('simpanan_akun', (new SimpananAkun)->getTable());
        $this->assertSame('simpanan_kewajiban', (new KewajibanSimpanan)->getTable());
    }

    public function test_registration_opens_accounts_and_first_obligations_idempotently(): void
    {
        $this->seed(JenisSimpananSeeder::class);
        $anggota = Anggota::factory()->create();
        $service = app(SimpananService::class);

        $service->registerInitialSavings($anggota);
        $service->registerInitialSavings($anggota);

        $this->assertSame(2, SimpananAkun::query()->where('anggota_id', $anggota->getKey())->count());

        $pokok = JenisSimpanan::query()->where('kode', 'POKOK')->firstOrFail();
        $wajib = JenisSimpanan::query()->where('kode', 'WAJIB')->firstOrFail();

        $this->assertSame(1, KewajibanSimpanan::query()
            ->where('anggota_id', $anggota->getKey())
            ->where('jenis_simpanan_id', $pokok->getKey())
            ->where('periode', 'AWAL')
            ->count());

        $this->assertSame(1, KewajibanSimpanan::query()
            ->where('anggota_id', $anggota->getKey())
            ->where('jenis_simpanan_id', $wajib->getKey())
            ->where('periode', now()->format('Y-m'))
            ->count());
    }

    public function test_registration_initial_savings_payment_can_activate_the_member(): void
    {
        $this->actingUser(['simpanan.kewajiban.update']);
        $this->configureCoa();
        $this->seed(JenisSimpananSeeder::class);

        $anggota = Anggota::factory()->create(['status' => 'pending']);

        app(SimpananService::class)->registerInitialSavings($anggota, [
            'pokok' => 100000,
            'wajib' => 50000,
        ]);

        $this->assertSame('active', $anggota->refresh()->status);

        $this->assertSame(2, KewajibanSimpanan::query()
            ->where('anggota_id', $anggota->getKey())
            ->where('status', StatusKewajiban::Paid->value)
            ->count());

        $this->assertSame(2, SimpananAkun::query()
            ->where('anggota_id', $anggota->getKey())
            ->where('saldo', '>', 0)
            ->count());

        $saldo = SimpananAkun::query()
            ->where('anggota_id', $anggota->getKey())
            ->orderBy('id')
            ->pluck('saldo')
            ->all();

        $this->assertSame(['100000.00', '50000.00'], $saldo);
    }

    public function test_deposit_and_withdrawal_update_balance_and_post_journals(): void
    {
        $this->actingUser(['simpanan.transaksi.create']);
        $this->configureCoa();

        $jenis = $this->sukarelaProduct();
        $anggota = Anggota::factory()->create();
        $akun = app(SimpananService::class)->openAccount($anggota, $jenis);

        app(SimpananService::class)->setor($akun, 1000000);
        $this->assertSame('1000000.00', $akun->refresh()->saldo);
        $this->assertSame(2, Jurnal::query()->where('source_module', 'Simpanan')->count());

        app(SimpananService::class)->tarik($akun, 200000);
        $this->assertSame('800000.00', $akun->refresh()->saldo);
        $this->assertSame(4, Jurnal::query()->where('source_module', 'Simpanan')->count());

        $this->expectException(InvalidArgumentException::class);
        app(SimpananService::class)->tarik($akun, 900000);
    }

    public function test_deposit_requires_transaction_permission(): void
    {
        $this->actingUser();

        $jenis = $this->sukarelaProduct();
        $anggota = Anggota::factory()->create();
        $akun = app(SimpananService::class)->openAccount($anggota, $jenis);

        $this->expectException(HttpException::class);
        app(SimpananService::class)->setor($akun, 100000);
    }

    public function test_monthly_obligations_are_idempotent_and_mark_paid(): void
    {
        $this->actingUser(['simpanan.kewajiban.update']);
        $this->configureCoa();

        $jenis = $this->wajibProduct();
        $anggota = Anggota::factory()->create();
        app(SimpananService::class)->openAccount($anggota, $jenis);

        $this->assertSame(1, app(SimpananService::class)->generateMonthlyObligations('2026-11'));
        $this->assertSame(0, app(SimpananService::class)->generateMonthlyObligations('2026-11'));

        $kewajiban = KewajibanSimpanan::query()->firstOrFail();
        $this->assertSame('2026-11-10', $kewajiban->jatuh_tempo->toDateString());

        app(SimpananService::class)->bayarKewajiban($kewajiban);

        $this->assertSame(StatusKewajiban::Paid, $kewajiban->refresh()->status);
        $this->assertNotNull($kewajiban->simpanan_transaksi_id);
        $this->assertSame('50000.00', SimpananAkun::query()->firstOrFail()->saldo);
    }

    public function test_interest_uses_average_daily_balance_for_the_month(): void
    {
        $this->actingUser(['simpanan.transaksi.create']);
        $this->configureCoa();

        $jenis = $this->sukarelaProduct(['berbunga' => true, 'bunga_per_bulan' => 1, 'periode_bunga_bulan' => 1]);
        $anggota = Anggota::factory()->create();
        $akun = app(SimpananService::class)->openAccount($anggota, $jenis);
        $akun->update(['dibuka_pada' => '2026-10-01']);
        app(SimpananService::class)->setor($akun, 1000000, ['tanggal' => '2026-10-01']);

        $this->assertSame(1, app(SimpananService::class)->prosesBunga('2026-10'));
        $this->assertSame('1010000.00', $akun->refresh()->saldo);
        $this->assertSame(1, $akun->transaksis()->where('tipe', TipeTransaksiSimpanan::Bunga->value)->count());
    }

    public function test_interest_is_lower_when_the_account_starts_mid_month(): void
    {
        $this->actingUser(['simpanan.transaksi.create']);
        $this->configureCoa();

        $jenis = $this->sukarelaProduct(['berbunga' => true, 'bunga_per_bulan' => 1, 'periode_bunga_bulan' => 1]);
        $anggota = Anggota::factory()->create();
        $akun = app(SimpananService::class)->openAccount($anggota, $jenis);
        $akun->update(['dibuka_pada' => '2026-10-20']);
        app(SimpananService::class)->setor($akun, 1000000, ['tanggal' => '2026-10-20']);

        $this->assertSame(1, app(SimpananService::class)->prosesBunga('2026-10'));
        $this->assertSame('1003870.97', $akun->refresh()->saldo);
    }

    public function test_member_is_pending_until_pokok_and_wajib_are_paid(): void
    {
        $this->seed(JenisSimpananSeeder::class);
        $anggota = Anggota::factory()->create(['status' => 'active']);
        $service = app(SimpananService::class);

        $service->registerInitialSavings($anggota);

        $this->assertSame('pending', $anggota->refresh()->status);

        $this->actingUser(['simpanan.kewajiban.update']);
        $this->configureCoa();

        foreach (KewajibanSimpanan::query()->where('anggota_id', $anggota->getKey())->get() as $kewajiban) {
            $service->bayarKewajiban($kewajiban);
        }

        $this->assertSame('active', $anggota->refresh()->status);
    }

    public function test_member_becomes_pending_after_three_unpaid_wajib_months(): void
    {
        $jenis = $this->wajibProduct();
        $anggota = Anggota::factory()->create(['status' => 'active']);
        $service = app(SimpananService::class);
        $service->openAccount($anggota, $jenis);

        foreach (['2026-08', '2026-09', '2026-10'] as $periode) {
            $service->generateMonthlyObligations($periode);
        }

        $this->assertSame(1, $service->markOverdueMembersPending(CarbonImmutable::parse('2026-10-15')));
        $this->assertSame('pending', $anggota->refresh()->status);
    }

    public function test_term_deposit_cannot_be_withdrawn_before_maturity(): void
    {
        $this->actingUser(['simpanan.transaksi.create']);
        $this->configureCoa();

        $jenis = $this->sukarelaProduct([
            'kategori' => KategoriSimpanan::Berjangka,
            'tenor_bulan' => 12,
        ]);
        $anggota = Anggota::factory()->create();
        $akun = app(SimpananService::class)->openAccount($anggota, $jenis);
        app(SimpananService::class)->setor($akun, 500000);

        $this->expectException(InvalidArgumentException::class);
        app(SimpananService::class)->tarik($akun, 100000);
    }

    public function test_filament_screens_are_permission_gated(): void
    {
        $user = $this->actingUser();
        Anggota::factory()->create(['user_id' => $user->getKey()]);

        $this->assertFalse(JenisSimpananResource::canViewAny());
        $this->assertFalse(InputSimpanan::canAccess());
        $this->assertFalse(SimpananSaya::canAccess());

        Permission::findOrCreate('simpanan.jenis_simpanan.viewAny', 'web');
        Permission::findOrCreate('simpanan.transaksi.create', 'web');
        Permission::findOrCreate('simpanan.portal_anggota.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user->givePermissionTo([
            'simpanan.jenis_simpanan.viewAny',
            'simpanan.transaksi.create',
            'simpanan.portal_anggota.view',
        ]);

        $this->assertTrue(JenisSimpananResource::canViewAny());
        $this->assertTrue(InputSimpanan::canAccess());
        $this->assertTrue(SimpananSaya::canAccess());
    }

    private function actingUser(array $permissions = []): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $this->actingAs($user);

        return $user;
    }

    private function sukarelaProduct(array $overrides = []): JenisSimpanan
    {
        return JenisSimpanan::query()->create([
            'kode' => 'SUKARELA',
            'nama' => 'Simpanan Sukarela',
            'kategori' => KategoriSimpanan::Sukarela,
            'nominal_default' => 0,
            'wajib' => false,
            'periodik' => PeriodikSimpanan::None,
            'berbunga' => false,
            ...$overrides,
        ]);
    }

    private function wajibProduct(): JenisSimpanan
    {
        return JenisSimpanan::query()->create([
            'kode' => 'WAJIB',
            'nama' => 'Simpanan Wajib',
            'kategori' => KategoriSimpanan::Wajib,
            'nominal_default' => 50000,
            'wajib' => true,
            'periodik' => PeriodikSimpanan::Monthly,
            'berbunga' => false,
            'tanggal_jatuh_tempo' => 10,
        ]);
    }

    private function configureCoa(): void
    {
        $asset = AccountType::query()->create(['code' => 'ASSET', 'name' => 'Asset']);
        $liability = AccountType::query()->create(['code' => 'LIABILITY', 'name' => 'Liabilitas']);
        $expense = AccountType::query()->create(['code' => 'EXPENSE', 'name' => 'Beban']);

        $cash = CoaAccount::query()->create([
            'code' => '1100', 'key_map' => 'KAS', 'name' => 'Kas',
            'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT',
        ]);
        $savings = CoaAccount::query()->create([
            'code' => '2100', 'key_map' => 'SIMPANAN', 'name' => 'Simpanan Anggota',
            'account_type_id' => $liability->getKey(), 'normal_balance' => 'CREDIT',
        ]);
        $interest = CoaAccount::query()->create([
            'code' => '5100', 'key_map' => 'BUNGA_SIMPANAN', 'name' => 'Bunga Simpanan',
            'account_type_id' => $expense->getKey(), 'normal_balance' => 'DEBIT',
        ]);

        CoaConfig::query()->create(['key' => SimpananCOAConfigEnum::SavingsCash->value, 'coa_account_id' => $cash->getKey()]);
        CoaConfig::query()->create(['key' => SimpananCOAConfigEnum::SavingsLiability->value, 'coa_account_id' => $savings->getKey()]);
        CoaConfig::query()->create(['key' => SimpananCOAConfigEnum::SavingsInterestExpense->value, 'coa_account_id' => $interest->getKey()]);
    }

    private function runModuleMigrations(): void
    {
        Schema::hasTable('users');

        $migrations = [
            'Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php',
            'Modules/Simpanan/database/migrations/2026_10_26_000001_create_simpanan_jenis_table.php',
            'Modules/Simpanan/database/migrations/2026_10_26_000002_create_simpanan_akun_table.php',
            'Modules/Simpanan/database/migrations/2026_10_26_000003_create_simpanan_transaksi_table.php',
            'Modules/Simpanan/database/migrations/2026_10_26_000004_create_simpanan_kewajiban_table.php',
            'Modules/Simpanan/database/migrations/2026_10_26_000005_create_simpanan_coa_config_table.php',
        ];

        foreach ($migrations as $migration) {
            (require base_path($migration))->up();
        }
    }
}

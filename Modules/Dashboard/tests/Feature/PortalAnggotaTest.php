<?php

namespace Modules\Dashboard\Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Dashboard\Filament\Pages\PortalAnggota;
use Modules\Dashboard\Services\PortalAnggotaService;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Pembayaran\Services\PembayaranService;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use Modules\Simpanan\Enums\KategoriSimpanan;
use Modules\Simpanan\Enums\PeriodikSimpanan;
use Modules\Simpanan\Models\CoaConfig;
use Modules\Simpanan\Models\JenisSimpanan;
use Modules\Simpanan\Services\SimpananService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PortalAnggotaTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('peminjaman', require base_path('Modules/Peminjaman/config/config.php'));
        config()->set('pembayaran', require base_path('Modules/Pembayaran/config/config.php'));
        config()->set('simpanan', require base_path('Modules/Simpanan/config/config.php'));
        config()->set('keanggotaan', require base_path('Modules/Keanggotaan/config/config.php'));
        config()->set('dashboard', require base_path('Modules/Dashboard/config/config.php'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->runMigrations();
    }

    public function test_portal_is_permission_gated(): void
    {
        $user = $this->actingUser();
        Anggota::factory()->create(['user_id' => $user->getKey()]);

        $this->assertFalse(PortalAnggota::canAccess());

        Permission::findOrCreate('dashboard.portal_anggota.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo('dashboard.portal_anggota.view');

        $this->assertTrue(PortalAnggota::canAccess());
    }

    public function test_portal_requires_an_anggota_profile(): void
    {
        $this->actingUser(['dashboard.portal_anggota.view']);

        $this->assertFalse(PortalAnggota::canAccess());

        $page = new PortalAnggota;
        $method = new \ReflectionMethod($page, 'getViewData');

        $this->assertNull($method->invoke($page)['data']);
    }

    public function test_portal_summarizes_the_member_data(): void
    {
        $user = $this->actingUser(['dashboard.portal_anggota.view', 'pembayaran.pembayaran.create', 'simpanan.transaksi.create', 'simpanan.kewajiban.update']);
        $anggota = Anggota::factory()->create(['user_id' => $user->getKey()]);
        $this->configureSimpananCoa();
        $this->configurePembayaranCoa();
        $service = app(SimpananService::class);

        $jenis = $service->openAccount($anggota, $this->sukarelaProduk());
        $service->setor($jenis, 500000, ['tanggal' => '2026-10-05']);

        $wajib = JenisSimpanan::query()->create([
            'kode' => 'WAJIB',
            'nama' => 'Simpanan Wajib',
            'kategori' => KategoriSimpanan::Wajib,
            'nominal_default' => 50000,
            'wajib' => true,
            'periodik' => PeriodikSimpanan::Monthly,
            'berbunga' => false,
            'tanggal_jatuh_tempo' => 10,
        ]);
        $service->openAccount($anggota, $wajib);
        $service->generateMonthlyObligations('2026-10');

        $pinjamanService = app(PeminjamanService::class);
        $loan = $this->disbursedLoan($anggota, $user);
        $pinjamanService->generateSchedule($loan, CarbonImmutable::parse('2026-10-15'));

        $pengajuan = Peminjaman::query()->create([
            'loan_number' => 'PJM-PENDING-1',
            'anggota_id' => $anggota->getKey(),
            'account_number' => $anggota->account_number,
            'simulation_type' => 'default',
            'interest_type' => JenisBungaPeminjaman::Flat,
            'interest_rate' => 12,
            'term_months' => 12,
            'loan_amount' => 500000,
            'purpose' => TujuanPeminjaman::Consumption,
            'status' => StatusPeminjaman::Submitted,
            'submitted_at' => '2026-10-20',
        ]);

        app(PembayaranService::class)->createManualPayment([
            // Pembayaran sebagian: angsuran 1 tetap SEBAGIAN sehingga denda
            // terus terutang pada sisa yang belum dibayar.
            'peminjaman_id' => $loan->getKey(),
            'payment_date' => '2026-11-15',
            'amount' => 62000,
            'principal_amount' => 50000,
            'interest_amount' => 12000,
        ]);

        $ringkasan = app(PortalAnggotaService::class)->ringkasan($anggota->refresh());
        $peminjamanService = app(PeminjamanService::class);

        $this->assertEquals(500000.0, $ringkasan['simpanan']['total']);
        $this->assertSame(1, $ringkasan['kredit_aktif']->count());
        $this->assertEquals(1150000.0, $ringkasan['total_sisa_pokok']);
        $this->assertEquals(
            round((float) $ringkasan['kredit_aktif']->sum('denda'), 2),
            $ringkasan['total_denda'],
        );
        $this->assertGreaterThan(0.0, $ringkasan['total_denda']);
        $this->assertSame(1, $ringkasan['pengajuan']->count());
        $this->assertSame($pengajuan->getKey(), $ringkasan['pengajuan'][0]['peminjaman']->getKey());
        $this->assertSame(1, $ringkasan['kewajiban']['jumlah']);
        $this->assertNotNull($ringkasan['pembayaran_terdekat']);
        $this->assertSame($loan->loan_number, $ringkasan['pembayaran_terdekat']['nomor_pinjaman']);
        $this->assertSame(1, count($ringkasan['pembayaran']));
    }

    public function test_portal_scopes_data_to_the_owning_member_only(): void
    {
        $user = $this->actingUser(['dashboard.portal_anggota.view']);
        $anggota = Anggota::factory()->create(['user_id' => $user->getKey()]);

        $anggotaLain = Anggota::factory()->create(['user_id' => User::factory()->create()->getKey()]);
        $this->disbursedLoan($anggotaLain, User::factory()->create());

        $ringkasan = app(PortalAnggotaService::class)->ringkasan($anggota);

        $this->assertSame(0, $ringkasan['kredit_aktif']->count());
        $this->assertSame(0, $ringkasan['pengajuan']->count());
    }

    private function actingUser(array $permissions = []): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $this->actingAs($user);

        return $user;
    }

    private function sukarelaProduk()
    {
        return JenisSimpanan::query()->create([
            'kode' => 'SUKARELA',
            'nama' => 'Simpanan Sukarela',
            'kategori' => KategoriSimpanan::Sukarela,
            'nominal_default' => 0,
            'wajib' => false,
            'periodik' => PeriodikSimpanan::None,
            'berbunga' => false,
        ]);
    }

    private function disbursedLoan(Anggota $anggota, User $user): Peminjaman
    {
        return Peminjaman::query()->create([
            'loan_number' => 'PJM-PORTAL-'.strtoupper(uniqid()),
            'anggota_id' => $anggota->getKey(),
            'account_number' => $anggota->account_number,
            'simulation_type' => 'default',
            'interest_type' => JenisBungaPeminjaman::Flat,
            'interest_rate' => 12,
            'term_months' => 12,
            'loan_amount' => 1200000,
            'purpose' => TujuanPeminjaman::Productive,
            'guarantee_required' => false,
            'penalty_rate' => 0,
            'late_fee' => 0,
            'biaya_administrasi' => 0,
            'biaya_provisi_persen' => 0,
            'denda_keterlambatan_persen' => 0.1,
            'penalti_pelunasan_persen' => 0,
            'status' => StatusPeminjaman::Disbursed,
            'disbursed_at' => '2026-10-15',
            'disbursed_by' => $user->getKey(),
        ]);
    }

    private function configureSimpananCoa(): void
    {
        $asset = AccountType::query()->firstOrCreate(['code' => 'ASSET'], ['name' => 'Asset']);
        $liability = AccountType::query()->firstOrCreate(['code' => 'LIABILITY'], ['name' => 'Liabilitas']);
        $expense = AccountType::query()->firstOrCreate(['code' => 'EXPENSE'], ['name' => 'Beban']);

        $kas = CoaAccount::query()->firstOrCreate(
            ['code' => '1100'],
            ['key_map' => 'KAS', 'name' => 'Kas', 'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT'],
        );
        $liab = CoaAccount::query()->firstOrCreate(
            ['code' => '2200'],
            ['key_map' => 'SIMPANAN', 'name' => 'Simpanan Anggota', 'account_type_id' => $liability->getKey(), 'normal_balance' => 'CREDIT'],
        );
        $beban = CoaAccount::query()->firstOrCreate(
            ['code' => '5000'],
            ['key_map' => 'BEBAN', 'name' => 'Bunga Simpanan', 'account_type_id' => $expense->getKey(), 'normal_balance' => 'DEBIT'],
        );

        CoaConfig::query()->firstOrCreate(['key' => 'SAVINGS_CASH'], ['coa_account_id' => $kas->getKey()]);
        CoaConfig::query()->firstOrCreate(['key' => 'SAVINGS_LIABILITY'], ['coa_account_id' => $liab->getKey()]);
        CoaConfig::query()->firstOrCreate(['key' => 'SAVINGS_INTEREST_EXPENSE'], ['coa_account_id' => $beban->getKey()]);
    }

    private function configurePembayaranCoa(): void
    {
        $asset = AccountType::query()->firstOrCreate(['code' => 'ASSET'], ['name' => 'Asset']);
        $revenue = AccountType::query()->firstOrCreate(['code' => 'REVENUE'], ['name' => 'Pendapatan']);

        $cash = CoaAccount::query()->firstOrCreate(
            ['code' => '1100'],
            ['key_map' => 'KAS', 'name' => 'Kas', 'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT'],
        );
        $receivable = CoaAccount::query()->create([
            'code' => '1200', 'key_map' => 'PIUTANG', 'name' => 'Piutang Pinjaman',
            'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT',
        ]);
        $interest = CoaAccount::query()->create([
            'code' => '4100', 'key_map' => 'PENDAPATAN_BUNGA', 'name' => 'Pendapatan Bunga',
            'account_type_id' => $revenue->getKey(), 'normal_balance' => 'CREDIT',
        ]);

        \Modules\Pembayaran\Models\CoaConfig::query()->firstOrCreate(['key' => 'PAYMENT_CASH'], ['coa_account_id' => $cash->getKey()]);
        \Modules\Pembayaran\Models\CoaConfig::query()->create(['key' => 'LOAN_RECEIVABLE', 'coa_account_id' => $receivable->getKey()]);
        \Modules\Pembayaran\Models\CoaConfig::query()->create(['key' => 'INTEREST_INCOME', 'coa_account_id' => $interest->getKey()]);
        \Modules\Pembayaran\Models\CoaConfig::query()->create(['key' => 'PENALTY_INCOME', 'coa_account_id' => null]);
    }

    private function runMigrations(): void
    {
        Schema::hasTable('users');

        $migrations = [
            'Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_10_060258_create_peminjamen_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_20_000001_add_personal_application_details_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_12_000001_create_jenis_pinjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000001_create_peminjaman_jadwal_angsuran_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000002_add_fee_and_penalty_to_jenis_pinjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000003_add_fee_and_penalty_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000004_add_lunas_tracking_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000005_add_denda_dibayar_to_jadwal_angsuran_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_28_000004_add_rekomendasi_to_peminjaman_table.php',
            'Modules/Pembayaran/database/migrations/2026_10_10_100000_create_pembayarans_table.php',
            'Modules/Pembayaran/database/migrations/2026_10_10_100010_create_coa_config_table.php',
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

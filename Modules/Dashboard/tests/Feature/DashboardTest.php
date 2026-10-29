<?php

namespace Modules\Dashboard\Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Dashboard\Filament\Pages\DashboardKoperasi;
use Modules\Dashboard\Services\DashboardService;
use Modules\Keanggotaan\Models\Anggota;
use Modules\Keuangan\Enums\KeuanganCOAConfigEnum;
use Modules\Keuangan\Enums\TipeKategoriKeuangan;
use Modules\Keuangan\Models\CoaConfig as KeuanganCoaConfig;
use Modules\Keuangan\Models\KategoriKeuangan;
use Modules\Keuangan\Services\KeuanganService;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CoaConfig as PembayaranCoaConfig;
use Modules\Peminjaman\Enums\JenisBungaPeminjaman;
use Modules\Peminjaman\Enums\StatusPeminjaman;
use Modules\Peminjaman\Enums\TujuanPeminjaman;
use Modules\Peminjaman\Models\Peminjaman;
use Modules\Peminjaman\Services\PeminjamanService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('peminjaman', require base_path('Modules/Peminjaman/config/config.php'));
        config()->set('pembayaran', require base_path('Modules/Pembayaran/config/config.php'));
        config()->set('simpanan', require base_path('Modules/Simpanan/config/config.php'));
        config()->set('keuangan', require base_path('Modules/Keuangan/config/config.php'));
        config()->set('dashboard', require base_path('Modules/Dashboard/config/config.php'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->runMigrations();
    }

    public function test_dashboard_is_permission_gated(): void
    {
        $user = $this->actingUser();

        $this->assertFalse(DashboardKoperasi::canAccess());

        Permission::findOrCreate('dashboard.dashboard.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo('dashboard.dashboard.view');

        $this->assertTrue(DashboardKoperasi::canAccess());
    }

    public function test_cash_breakdown_sums_configured_cash_accounts(): void
    {
        $user = User::factory()->create();
        [$kasA, $kasB] = $this->configureCashAccounts($user);

        $this->jurnal($kasA, 'DEBIT', '500.00', $user);
        $this->jurnal($kasA, 'CREDIT', '100.00', $user);
        $this->jurnal($kasB, 'CREDIT', '50.00', $user);
        $this->jurnal($kasA, 'DEBIT', '999.00', $user, 'DRAFT');

        $kas = app(DashboardService::class)->kas();

        $this->assertEquals(350.0, $kas['total']);
        $this->assertCount(2, $kas['accounts']);
        $this->assertEquals(400.0, (float) $kas['accounts'][0]['saldo']);
        $this->assertEquals(-50.0, (float) $kas['accounts'][1]['saldo']);
    }

    public function test_piutang_tunggakan_and_denda_are_computed(): void
    {
        $user = User::factory()->create();
        $peminjaman = $this->disbursedLoan(Anggota::factory()->create(), $user);
        app(PeminjamanService::class)->generateSchedule($peminjaman, CarbonImmutable::parse('2026-10-15'));
        $this->configurePembayaranCoa();

        $service = app(DashboardService::class);
        $asOf = CarbonImmutable::parse('2026-10-25');

        $piutang = $service->piutang();
        $tunggakan = $service->tunggakan($asOf);

        $this->assertEquals(1200000.0, $piutang['sisa_pokok']);
        $this->assertSame(1, $piutang['pinjaman']);
        $this->assertSame(1, $tunggakan['angsuran']);
        $this->assertEquals(112000.0, $tunggakan['jumlah']);
        $this->assertEquals(1120.0, $service->dendaBelumDicairkan($asOf));
    }

    public function test_keuangan_summary_uses_the_selected_period(): void
    {
        $this->actingUser(['keuangan.transaksi.create']);
        $user = User::factory()->create();
        $this->configureKeuangan($user);

        $pendapatan = $this->kategori(TipeKategoriKeuangan::Pendapatan, '4100');
        $pengeluaran = $this->kategori(TipeKategoriKeuangan::Pengeluaran, '5100');
        $service = app(KeuanganService::class);

        $service->createTransaksi(['kategori_id' => $pendapatan->getKey(), 'tanggal' => '2026-10-05', 'nominal' => 300000]);
        $service->createTransaksi(['kategori_id' => $pengeluaran->getKey(), 'tanggal' => '2026-10-07', 'nominal' => 100000]);
        $service->createTransaksi(['kategori_id' => $pendapatan->getKey(), 'tanggal' => '2026-11-01', 'nominal' => 500000]);

        $oktober = app(DashboardService::class)->keuangan('2026-10-01', '2026-10-31');

        $this->assertEquals(300000.0, $oktober['pendapatan']);
        $this->assertEquals(100000.0, $oktober['pengeluaran']);
        $this->assertEquals(200000.0, $oktober['selisih']);

        $this->assertCount(1, $oktober['pendapatan_detail']);
        $this->assertEquals(300000.0, (float) $oktober['pendapatan_detail'][0]['nominal']);
        $this->assertCount(1, $oktober['pengeluaran_detail']);
        $this->assertEquals(100000.0, (float) $oktober['pengeluaran_detail'][0]['nominal']);
    }

    public function test_periode_helpers_return_expected_ranges(): void
    {
        $service = app(DashboardService::class);
        $today = CarbonImmutable::today();

        $hari = $service->periode('hari');
        $this->assertSame($today->toDateString(), $hari['dari']);
        $this->assertSame($today->toDateString(), $hari['sampai']);

        $bulan = $service->periode('bulan');
        $this->assertSame($today->startOfMonth()->toDateString(), $bulan['dari']);
        $this->assertSame($today->endOfMonth()->toDateString(), $bulan['sampai']);

        $rentang = $service->periode('rentang', '2026-10-01', '2026-10-31');
        $this->assertSame('2026-10-01', $rentang['dari']);
        $this->assertSame('2026-10-31', $rentang['sampai']);
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

    private function loanData(Anggota $anggota, User $user, array $overrides = []): array
    {
        return [
            'loan_number' => 'PJM-TEST-'.strtoupper(uniqid()),
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
            'biaya_administrasi' => 50000,
            'biaya_provisi_persen' => 1,
            'denda_keterlambatan_persen' => 0.1,
            'penalti_pelunasan_persen' => 2,
            ...$overrides,
        ];
    }

    private function disbursedLoan(Anggota $anggota, User $user): Peminjaman
    {
        return Peminjaman::query()->create($this->loanData($anggota, $user, [
            'status' => StatusPeminjaman::Disbursed,
            'disbursed_at' => '2026-10-15',
            'disbursed_by' => $user->getKey(),
        ]));
    }

    private function jurnal(CoaAccount $account, string $position, string $amount, User $user, string $status = 'POSTED'): void
    {
        Jurnal::query()->create([
            'jurnal_number' => 'JRN-'.uniqid(),
            'jurnal_date' => '2026-10-20',
            'coa_account_id' => $account->getKey(),
            'position' => $position,
            'amount' => $amount,
            'status' => $status,
            'created_by' => $user->getKey(),
        ]);
    }

    private function configureCashAccounts(User $user): array
    {
        $asset = AccountType::query()->create(['code' => 'ASSET', 'name' => 'Asset']);

        $kasA = CoaAccount::query()->create([
            'code' => '1100', 'key_map' => 'KAS_A', 'name' => 'Kas Operasional',
            'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT',
        ]);
        $kasB = CoaAccount::query()->create([
            'code' => '1101', 'key_map' => 'KAS_B', 'name' => 'Kas Pembayaran',
            'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT',
        ]);

        PembayaranCoaConfig::query()->create(['key' => PembayaranCOAConfigEnum::PaymentCash->value, 'coa_account_id' => $kasA->getKey()]);
        KeuanganCoaConfig::query()->create(['key' => KeuanganCOAConfigEnum::Kas->value, 'coa_account_id' => $kasB->getKey()]);

        return [$kasA, $kasB];
    }

    private function configurePembayaranCoa(): void
    {
        $asset = AccountType::query()->create(['code' => 'ASSET', 'name' => 'Asset']);
        $revenue = AccountType::query()->create(['code' => 'REVENUE', 'name' => 'Pendapatan']);

        $cash = CoaAccount::query()->create([
            'code' => '1100', 'key_map' => 'KAS', 'name' => 'Kas',
            'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT',
        ]);
        $receivable = CoaAccount::query()->create([
            'code' => '1200', 'key_map' => 'PIUTANG', 'name' => 'Piutang Pinjaman',
            'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT',
        ]);
        $interest = CoaAccount::query()->create([
            'code' => '4100', 'key_map' => 'PENDAPATAN_BUNGA', 'name' => 'Pendapatan Bunga',
            'account_type_id' => $revenue->getKey(), 'normal_balance' => 'CREDIT',
        ]);
        $penalty = CoaAccount::query()->create([
            'code' => '4200', 'key_map' => 'PENDAPATAN_DENDA', 'name' => 'Pendapatan Denda',
            'account_type_id' => $revenue->getKey(), 'normal_balance' => 'CREDIT',
        ]);

        PembayaranCoaConfig::query()->create(['key' => PembayaranCOAConfigEnum::PaymentCash->value, 'coa_account_id' => $cash->getKey()]);
        PembayaranCoaConfig::query()->create(['key' => PembayaranCOAConfigEnum::LoanReceivable->value, 'coa_account_id' => $receivable->getKey()]);
        PembayaranCoaConfig::query()->create(['key' => PembayaranCOAConfigEnum::InterestIncome->value, 'coa_account_id' => $interest->getKey()]);
        PembayaranCoaConfig::query()->create(['key' => PembayaranCOAConfigEnum::PenaltyIncome->value, 'coa_account_id' => $penalty->getKey()]);
    }

    private function configureKeuangan(User $user): void
    {
        $asset = AccountType::query()->create(['code' => 'ASSET', 'name' => 'Asset']);
        $revenue = AccountType::query()->create(['code' => 'REVENUE', 'name' => 'Pendapatan']);
        $expense = AccountType::query()->create(['code' => 'EXPENSE', 'name' => 'Beban']);

        $kas = CoaAccount::query()->create([
            'code' => '1101', 'key_map' => 'KAS_KEU', 'name' => 'Kas Keuangan',
            'account_type_id' => $asset->getKey(), 'normal_balance' => 'DEBIT',
        ]);
        CoaAccount::query()->create([
            'code' => '4100', 'key_map' => 'PENDAPATAN', 'name' => 'Pendapatan Lain',
            'account_type_id' => $revenue->getKey(), 'normal_balance' => 'CREDIT',
        ]);
        CoaAccount::query()->create([
            'code' => '5100', 'key_map' => 'BEBAN', 'name' => 'Beban Operasional',
            'account_type_id' => $expense->getKey(), 'normal_balance' => 'DEBIT',
        ]);

        KeuanganCoaConfig::query()->create(['key' => KeuanganCOAConfigEnum::Kas->value, 'coa_account_id' => $kas->getKey()]);
    }

    private function kategori(TipeKategoriKeuangan $tipe, string $coaCode): KategoriKeuangan
    {
        $coaAccountId = CoaAccount::query()->where('code', $coaCode)->value('id');

        return KategoriKeuangan::query()->create([
            'kode' => 'KGT-'.strtoupper(uniqid()),
            'nama' => "Kategori {$tipe->name}",
            'tipe' => $tipe,
            'coa_account_id' => $coaAccountId,
        ]);
    }

    private function runMigrations(): void
    {
        Schema::hasTable('users');

        $migrations = [
            'Modules/Keanggotaan/database/migrations/2026_10_10_054110_create_anggotas_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_10_060258_create_peminjamen_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_10_060340_create_coa_config_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_20_000001_add_personal_application_details_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_12_000001_create_jenis_pinjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000001_create_peminjaman_jadwal_angsuran_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000002_add_fee_and_penalty_to_jenis_pinjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000003_add_fee_and_penalty_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000004_add_lunas_tracking_to_peminjaman_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_27_000005_add_denda_dibayar_to_jadwal_angsuran_table.php',
            'Modules/Pembayaran/database/migrations/2026_10_10_100000_create_pembayarans_table.php',
            'Modules/Pembayaran/database/migrations/2026_10_10_100010_create_coa_config_table.php',
            'Modules/Simpanan/database/migrations/2026_10_26_000005_create_simpanan_coa_config_table.php',
            'Modules/Keuangan/database/migrations/2026_10_28_000001_create_keuangan_kategori_table.php',
            'Modules/Keuangan/database/migrations/2026_10_28_000002_create_keuangan_transaksi_table.php',
            'Modules/Keuangan/database/migrations/2026_10_28_000003_create_keuangan_coa_config_table.php',
        ];

        foreach ($migrations as $migration) {
            (require base_path($migration))->up();
        }
    }
}

<?php

namespace Modules\Keuangan\Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Keuangan\Database\Seeders\KategoriKeuanganSeeder;
use Modules\Keuangan\Enums\KeuanganCOAConfigEnum;
use Modules\Keuangan\Enums\TipeKategoriKeuangan;
use Modules\Keuangan\Models\CoaConfig;
use Modules\Keuangan\Models\KategoriKeuangan;
use Modules\Keuangan\Models\TransaksiKeuangan;
use Modules\Keuangan\Services\KeuanganService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class KeuanganModuleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('keuangan', require base_path('Modules/Keuangan/config/config.php'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->runMigrations();
    }

    public function test_it_uses_module_tables_and_casts_enums(): void
    {
        $this->assertSame('keuangan_kategori', (new KategoriKeuangan)->getTable());
        $this->assertSame('keuangan_transaksi', (new TransaksiKeuangan)->getTable());
    }

    public function test_income_transaction_debits_cash_and_credits_revenue(): void
    {
        $this->actingUser(['keuangan.transaksi.create']);
        $this->configureCoa();

        $kategori = $this->kategori(TipeKategoriKeuangan::Pendapatan, '4100');

        $transaksi = app(KeuanganService::class)->createTransaksi([
            'kategori_id' => $kategori->getKey(),
            'tanggal' => '2026-10-05',
            'nominal' => 250000,
        ]);

        $this->assertSame('keuangan_transaksi', $transaksi->getTable());

        $debit = (float) Jurnal::query()
            ->where('source_module', 'Keuangan')
            ->where('source_id', $transaksi->getKey())
            ->where('position', 'DEBIT')
            ->sum('amount');
        $credit = (float) Jurnal::query()
            ->where('source_module', 'Keuangan')
            ->where('source_id', $transaksi->getKey())
            ->where('position', 'CREDIT')
            ->sum('amount');

        $this->assertEquals(250000.0, $debit);
        $this->assertEquals($debit, $credit);
    }

    public function test_expense_transaction_debits_expense_and_credits_cash(): void
    {
        $this->actingUser(['keuangan.transaksi.create']);
        $this->configureCoa();

        $kategori = $this->kategori(TipeKategoriKeuangan::Pengeluaran, '5100');

        $transaksi = app(KeuanganService::class)->createTransaksi([
            'kategori_id' => $kategori->getKey(),
            'tanggal' => '2026-10-06',
            'nominal' => 75000,
        ]);

        $debitAccount = Jurnal::query()
            ->where('source_module', 'Keuangan')
            ->where('source_id', $transaksi->getKey())
            ->where('position', 'DEBIT')
            ->firstOrFail();

        $this->assertSame($kategori->coa_account_id, $debitAccount->coa_account_id);

        $debit = (float) Jurnal::query()
            ->where('source_module', 'Keuangan')
            ->where('source_id', $transaksi->getKey())
            ->where('position', 'DEBIT')
            ->sum('amount');
        $credit = (float) Jurnal::query()
            ->where('source_module', 'Keuangan')
            ->where('source_id', $transaksi->getKey())
            ->where('position', 'CREDIT')
            ->sum('amount');

        $this->assertEquals(75000.0, $debit);
        $this->assertEquals($debit, $credit);
    }

    public function test_transaction_fails_when_category_has_no_coa_account(): void
    {
        $this->actingUser(['keuangan.transaksi.create']);
        $this->configureCoa();

        $kategori = KategoriKeuangan::query()->create([
            'kode' => 'TNCOA',
            'nama' => 'Tanpa COA',
            'tipe' => TipeKategoriKeuangan::Pendapatan,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(KeuanganService::class)->createTransaksi([
            'kategori_id' => $kategori->getKey(),
            'nominal' => 1000,
        ]);
    }

    public function test_transaction_requires_permission(): void
    {
        $this->actingUser();
        $this->configureCoa();

        $kategori = $this->kategori(TipeKategoriKeuangan::Pendapatan, '4100');

        $this->expectException(HttpException::class);

        app(KeuanganService::class)->createTransaksi([
            'kategori_id' => $kategori->getKey(),
            'nominal' => 1000,
        ]);
    }

    public function test_summary_respects_the_selected_period(): void
    {
        $this->actingUser(['keuangan.transaksi.create']);
        $this->configureCoa();

        $pendapatan = $this->kategori(TipeKategoriKeuangan::Pendapatan, '4100');
        $pengeluaran = $this->kategori(TipeKategoriKeuangan::Pengeluaran, '5100');
        $service = app(KeuanganService::class);

        $service->createTransaksi(['kategori_id' => $pendapatan->getKey(), 'tanggal' => '2026-10-05', 'nominal' => 300000]);
        $service->createTransaksi(['kategori_id' => $pengeluaran->getKey(), 'tanggal' => '2026-10-07', 'nominal' => 100000]);
        $service->createTransaksi(['kategori_id' => $pendapatan->getKey(), 'tanggal' => '2026-11-01', 'nominal' => 500000]);

        $oktober = $service->ringkasan('2026-10-01', '2026-10-31');
        $november = $service->ringkasan('2026-11-01', '2026-11-30');

        $this->assertEquals(300000.0, $oktober['pendapatan']);
        $this->assertEquals(100000.0, $oktober['pengeluaran']);
        $this->assertEquals(200000.0, $oktober['selisih']);
        $this->assertEquals(500000.0, $november['pendapatan']);
        $this->assertEquals(0.0, $november['pengeluaran']);
    }

    public function test_kategori_seeder_is_idempotent(): void
    {
        $this->seed(KategoriKeuanganSeeder::class);
        $this->seed(KategoriKeuanganSeeder::class);

        $this->assertSame(6, KategoriKeuangan::query()->count());
        $this->assertSame(
            3,
            KategoriKeuangan::query()->where('tipe', TipeKategoriKeuangan::Pendapatan->value)->count(),
        );
        $this->assertSame(
            3,
            KategoriKeuangan::query()->where('tipe', TipeKategoriKeuangan::Pengeluaran->value)->count(),
        );
    }

    public function test_kategori_seeder_fills_missing_coa_mapping_when_available(): void
    {
        $this->seed(KategoriKeuanganSeeder::class);
        $this->assertSame(0, KategoriKeuangan::query()->whereNotNull('coa_account_id')->count());

        $revenue = AccountType::query()->create(['code' => 'REVENUE', 'name' => 'Pendapatan']);
        $expense = AccountType::query()->create(['code' => 'EXPENSE', 'name' => 'Beban']);

        $pendapatan = CoaAccount::query()->create([
            'code' => '4000', 'key_map' => 'PENDAPATAN_OPERASIONAL', 'name' => 'Pendapatan Operasional',
            'account_type_id' => $revenue->getKey(), 'normal_balance' => 'CREDIT',
        ]);
        $beban = CoaAccount::query()->create([
            'code' => '5000', 'key_map' => 'BEBAN_OPERASIONAL', 'name' => 'Beban Operasional',
            'account_type_id' => $expense->getKey(), 'normal_balance' => 'DEBIT',
        ]);

        $this->seed(KategoriKeuanganSeeder::class);

        $this->assertSame(6, KategoriKeuangan::query()->whereNotNull('coa_account_id')->count());
        $this->assertSame(
            3,
            KategoriKeuangan::query()->where('coa_account_id', $pendapatan->getKey())->count(),
        );
        $this->assertSame(
            3,
            KategoriKeuangan::query()->where('coa_account_id', $beban->getKey())->count(),
        );
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

    private function configureCoa(): void
    {
        $asset = AccountType::query()->create(['code' => 'ASSET', 'name' => 'Asset']);
        $revenue = AccountType::query()->create(['code' => 'REVENUE', 'name' => 'Pendapatan']);
        $expense = AccountType::query()->create(['code' => 'EXPENSE', 'name' => 'Beban']);

        $kas = CoaAccount::query()->create([
            'code' => '1100', 'key_map' => 'KAS', 'name' => 'Kas',
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

        CoaConfig::query()->create(['key' => KeuanganCOAConfigEnum::Kas->value, 'coa_account_id' => $kas->getKey()]);
    }

    private function runMigrations(): void
    {
        Schema::hasTable('users');

        $migrations = [
            'Modules/Keuangan/database/migrations/2026_10_28_000001_create_keuangan_kategori_table.php',
            'Modules/Keuangan/database/migrations/2026_10_28_000002_create_keuangan_transaksi_table.php',
            'Modules/Keuangan/database/migrations/2026_10_28_000003_create_keuangan_coa_config_table.php',
        ];

        foreach ($migrations as $migration) {
            (require base_path($migration))->up();
        }
    }
}

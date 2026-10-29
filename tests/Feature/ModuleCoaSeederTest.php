<?php

namespace Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Keuangan\Database\Seeders\KeuanganCOASeeder;
use Modules\Keuangan\Enums\KeuanganCOAConfigEnum;
use Modules\Keuangan\Models\CoaConfig as KeuanganCoaConfig;
use Modules\Pembayaran\Database\Seeders\PembayaranCOASeeder;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CoaConfig as PembayaranCoaConfig;
use Modules\Peminjaman\Database\Seeders\PeminjamanCOASeeder;
use Modules\Peminjaman\Enums\PeminjamanCOAConfigEnum;
use Modules\Peminjaman\Models\CoaConfig as PeminjamanCoaConfig;
use Modules\Simpanan\Database\Seeders\SimpananCOASeeder;
use Modules\Simpanan\Enums\SimpananCOAConfigEnum;
use Modules\Simpanan\Models\CoaConfig as SimpananCoaConfig;
use Tests\TestCase;

class ModuleCoaSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('peminjaman', require base_path('Modules/Peminjaman/config/config.php'));
        config()->set('pembayaran', require base_path('Modules/Pembayaran/config/config.php'));
        config()->set('simpanan', require base_path('Modules/Simpanan/config/config.php'));
        config()->set('keuangan', require base_path('Modules/Keuangan/config/config.php'));
    }

    public function test_coa_seeders_fill_missing_module_configs_without_overwriting_manual_settings(): void
    {
        $this->runConfigMigrations();

        // Fase 1: chart of accounts core belum ada — seeder tidak boleh gagal.
        (new KeuanganCOASeeder)->run();
        (new PembayaranCOASeeder)->run();
        (new PeminjamanCOASeeder)->run();
        (new SimpananCOASeeder)->run();

        $this->assertSame(0, KeuanganCoaConfig::query()->whereNotNull('coa_account_id')->count());

        // Fase 2: tipe akun tersedia — seeder membuat akun & memetakan config.
        $this->createAccountTypes();

        (new KeuanganCOASeeder)->run();
        (new PembayaranCOASeeder)->run();
        (new PeminjamanCOASeeder)->run();
        (new SimpananCOASeeder)->run();

        $this->assertNotNull(KeuanganCoaConfig::query()->where('key', KeuanganCOAConfigEnum::Kas->value)->value('coa_account_id'));
        $this->assertNotNull(PembayaranCoaConfig::query()->where('key', PembayaranCOAConfigEnum::PaymentCash->value)->value('coa_account_id'));
        $this->assertNotNull(PeminjamanCoaConfig::query()->where('key', PeminjamanCOAConfigEnum::DisbursementCash->value)->value('coa_account_id'));
        $this->assertNotNull(SimpananCoaConfig::query()->where('key', SimpananCOAConfigEnum::SavingsLiability->value)->value('coa_account_id'));

        // Semua kunci kas menunjuk akun kas operasional yang sama (kode 1100).
        $kas = CoaAccount::query()->where('code', '1100')->firstOrFail();
        $this->assertSame($kas->getKey(), PembayaranCoaConfig::query()->where('key', PembayaranCOAConfigEnum::PaymentCash->value)->value('coa_account_id'));
        $this->assertSame($kas->getKey(), PeminjamanCoaConfig::query()->where('key', PeminjamanCOAConfigEnum::DisbursementCash->value)->value('coa_account_id'));
        $this->assertSame($kas->getKey(), SimpananCoaConfig::query()->where('key', SimpananCOAConfigEnum::SavingsCash->value)->value('coa_account_id'));

        // Pengaturan manual tidak ditimpa oleh seeder.
        $manual = CoaAccount::query()->create([
            'code' => '1101', 'key_map' => 'KAS_BANK', 'name' => 'Kas Bank',
            'account_type_id' => $kas->account_type_id, 'normal_balance' => 'DEBIT',
        ]);
        PembayaranCoaConfig::query()->updateOrCreate(
            ['key' => PembayaranCOAConfigEnum::PaymentCash->value],
            ['coa_account_id' => $manual->getKey()],
        );

        (new PembayaranCOASeeder)->run();

        $this->assertSame(
            $manual->getKey(),
            PembayaranCoaConfig::query()->where('key', PembayaranCOAConfigEnum::PaymentCash->value)->value('coa_account_id'),
        );
    }

    private function createAccountTypes(): void
    {
        AccountType::query()->firstOrCreate(['code' => 'ASSET'], ['name' => 'Asset']);
        AccountType::query()->firstOrCreate(['code' => 'LIABILITY'], ['name' => 'Liabilitas']);
        AccountType::query()->firstOrCreate(['code' => 'REVENUE'], ['name' => 'Pendapatan']);
        AccountType::query()->firstOrCreate(['code' => 'EXPENSE'], ['name' => 'Beban']);
    }

    private function runConfigMigrations(): void
    {
        Schema::hasTable('users');

        $migrations = [
            'Modules/Pembayaran/database/migrations/2026_10_10_100010_create_coa_config_table.php',
            'Modules/Peminjaman/database/migrations/2026_10_10_060340_create_coa_config_table.php',
            'Modules/Simpanan/database/migrations/2026_10_26_000005_create_simpanan_coa_config_table.php',
            'Modules/Keuangan/database/migrations/2026_10_28_000003_create_keuangan_coa_config_table.php',
        ];

        foreach ($migrations as $migration) {
            (require base_path($migration))->up();
        }
    }
}

<?php

namespace Modules\Pembayaran\Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\CashSetting;
use Modules\Pembayaran\Models\CoaConfig;
use Modules\Pembayaran\Services\CashService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CashServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('pembayaran', require base_path('Modules/Pembayaran/config/config.php'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (! Schema::hasTable('pembayaran_coa_config')) {
            (require base_path('Modules/Pembayaran/database/migrations/2026_10_10_100010_create_coa_config_table.php'))->up();
        }

        if (! Schema::hasTable('pembayaran_cash_settings')) {
            (require base_path('Modules/Pembayaran/database/migrations/2026_10_25_000001_create_pembayaran_cash_settings_table.php'))->up();
        }
    }

    public function test_it_stores_a_single_opening_cash_balance_and_updates_it(): void
    {
        $user = $this->authorizedUser();
        $service = app(CashService::class);

        $service->setOpeningBalance(['opening_balance' => 5000000, 'opening_date' => '2026-10-01']);
        $service->setOpeningBalance(['opening_balance' => 7500000, 'opening_date' => '2026-10-05']);

        $this->assertSame(1, CashSetting::query()->count());

        $setting = CashSetting::query()->firstOrFail();
        $this->assertSame('7500000.00', $setting->opening_balance);
        $this->assertSame('2026-10-05', $setting->opening_date->toDateString());
        $this->assertSame($user->getKey(), $setting->updated_by);
    }

    public function test_it_requires_permission_to_set_the_opening_cash_balance(): void
    {
        $this->actingAs(User::factory()->create());

        $this->expectException(HttpException::class);

        app(CashService::class)->setOpeningBalance(['opening_balance' => 1000]);
    }

    public function test_it_summarizes_remaining_cash_from_opening_and_posted_movements(): void
    {
        $user = $this->authorizedUser();
        $cashAccount = $this->cashAccount();

        CoaConfig::query()->create([
            'key' => PembayaranCOAConfigEnum::PaymentCash->value,
            'coa_account_id' => $cashAccount->getKey(),
        ]);

        app(CashService::class)->setOpeningBalance(['opening_balance' => 1000000]);

        $this->jurnal($cashAccount, 'DEBIT', '250000.00', $user);
        $this->jurnal($cashAccount, 'CREDIT', '100000.00', $user);
        $this->jurnal($cashAccount, 'DEBIT', '999999.00', $user, 'DRAFT');

        $summary = app(CashService::class)->summary();

        $this->assertTrue($summary['cash_account_configured']);
        $this->assertSame('1000000.00', $summary['opening_balance']);
        $this->assertSame('250000.00', $summary['total_in']);
        $this->assertSame('100000.00', $summary['total_out']);
        $this->assertSame('1150000.00', $summary['remaining']);
    }

    public function test_it_reports_remaining_cash_without_a_configured_cash_account(): void
    {
        $this->authorizedUser();

        app(CashService::class)->setOpeningBalance(['opening_balance' => 200000]);

        $summary = app(CashService::class)->summary();

        $this->assertFalse($summary['cash_account_configured']);
        $this->assertSame('200000.00', $summary['remaining']);
    }

    private function authorizedUser(): User
    {
        Permission::findOrCreate('pembayaran.kas.update', 'web');
        Permission::findOrCreate('pembayaran.kas.viewAny', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('pembayaran.kas.update');
        $this->actingAs($user);

        return $user;
    }

    private function cashAccount(): CoaAccount
    {
        $accountType = AccountType::create(['code' => 'ASSET', 'name' => 'Asset']);

        return CoaAccount::create([
            'code' => '1100',
            'key_map' => 'KAS_OPERASIONAL',
            'name' => 'Kas Operasional',
            'account_type_id' => $accountType->getKey(),
            'normal_balance' => 'DEBIT',
        ]);
    }

    private function jurnal(CoaAccount $account, string $position, string $amount, User $user, string $status = 'POSTED'): void
    {
        Jurnal::query()->create([
            'jurnal_number' => 'JRN-'.uniqid(),
            'jurnal_date' => now()->toDateString(),
            'coa_account_id' => $account->getKey(),
            'position' => $position,
            'amount' => $amount,
            'status' => $status,
            'created_by' => $user->getKey(),
        ]);
    }
}

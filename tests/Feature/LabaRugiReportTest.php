<?php

namespace Tests\Feature;

use App\Exports\LabaRugiExport;
use App\Filament\Pages\Reports\LaporanLabaRugi;
use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\User;
use App\Services\Accounting\AccountingReportService;
use Carbon\CarbonImmutable;
use Database\Seeders\CoreAccountingSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LabaRugiReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_aggregates_revenue_and_expense_accounts_by_two_digit_parent(): void
    {
        $this->seed(CoreAccountingSeeder::class);

        $accounts = $this->prepareAccounts();
        $user = User::factory()->create();

        $this->postJournal($accounts['4000'], 'CREDIT', '200000.00', '2026-10-05', $user);
        $this->postJournal($accounts['4110'], 'CREDIT', '100000.00', '2026-10-06', $user);
        $this->postJournal($accounts['4120'], 'CREDIT', '50000.00', '2026-10-07', $user);
        $this->postJournal($accounts['4200'], 'CREDIT', '25000.00', '2026-10-08', $user);
        $this->postJournal($accounts['5000'], 'DEBIT', '120000.00', '2026-10-09', $user);

        $this->postJournal($accounts['4100'], 'CREDIT', '999999.00', '2026-10-10', $user, 'DRAFT');
        $this->postJournal($accounts['4200'], 'CREDIT', '888888.00', '2026-10-11', $user, 'REVERSED');
        $this->postJournal($accounts['4000'], 'CREDIT', '777777.00', '2026-09-30', $user);

        $report = app(AccountingReportService::class)->labaRugi(
            CarbonImmutable::parse('2026-10-01'),
            CarbonImmutable::parse('2026-10-31'),
        );

        $pendapatan = collect($report['pendapatan'])->keyBy('code');
        $beban = collect($report['beban'])->keyBy('code');

        $this->assertCount(3, $report['pendapatan']);
        $this->assertSame('200000.00', $pendapatan->get('4000')['amount']);
        $this->assertSame('150000.00', $pendapatan->get('4100')['amount']);
        $this->assertSame('25000.00', $pendapatan->get('4200')['amount']);
        $this->assertCount(1, $report['beban']);
        $this->assertSame('120000.00', $beban->get('5000')['amount']);
        $this->assertSame('375000.00', $report['total_pendapatan']);
        $this->assertSame('120000.00', $report['total_beban']);
        $this->assertSame('255000.00', $report['laba_rugi']);
    }

    public function test_it_reports_a_loss_when_expenses_exceed_revenue(): void
    {
        $this->seed(CoreAccountingSeeder::class);

        $accounts = $this->prepareAccounts();
        $user = User::factory()->create();

        $this->postJournal($accounts['4100'], 'CREDIT', '50000.00', '2026-10-05', $user);
        $this->postJournal($accounts['5000'], 'DEBIT', '80000.00', '2026-10-06', $user);

        $report = app(AccountingReportService::class)->labaRugi(
            CarbonImmutable::parse('2026-10-01'),
            CarbonImmutable::parse('2026-10-31'),
        );

        $this->assertSame('50000.00', $report['total_pendapatan']);
        $this->assertSame('80000.00', $report['total_beban']);
        $this->assertSame('-30000.00', $report['laba_rugi']);
    }

    public function test_it_hides_parent_accounts_without_movement(): void
    {
        $this->seed(CoreAccountingSeeder::class);

        $accounts = $this->prepareAccounts();
        $user = User::factory()->create();

        $this->postJournal($accounts['4100'], 'CREDIT', '50000.00', '2026-10-05', $user);

        $report = app(AccountingReportService::class)->labaRugi(
            CarbonImmutable::parse('2026-10-01'),
            CarbonImmutable::parse('2026-10-31'),
        );

        $this->assertCount(1, $report['pendapatan']);
        $this->assertSame('4100', $report['pendapatan'][0]['code']);
        $this->assertSame([], $report['beban']);
    }

    public function test_the_page_is_permission_gated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertFalse(LaporanLabaRugi::canAccess());

        Permission::findOrCreate('report.laba_rugi.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo('report.laba_rugi.view');

        $this->assertTrue(LaporanLabaRugi::canAccess());
    }

    public function test_the_excel_export_contains_the_grouped_report(): void
    {
        $this->seed(CoreAccountingSeeder::class);

        $accounts = $this->prepareAccounts();
        $user = User::factory()->create();

        $this->postJournal($accounts['4110'], 'CREDIT', '100000.00', '2026-10-06', $user);
        $this->postJournal($accounts['4120'], 'CREDIT', '50000.00', '2026-10-07', $user);
        $this->postJournal($accounts['5000'], 'DEBIT', '120000.00', '2026-10-09', $user);

        $report = app(AccountingReportService::class)->labaRugi(
            CarbonImmutable::parse('2026-10-01'),
            CarbonImmutable::parse('2026-10-31'),
        );

        $rows = (new LabaRugiExport($report))->array();

        $this->assertTrue(collect($rows)->contains(
            fn (array $row): bool => ($row[0] ?? null) === '4100'
                && ($row[1] ?? null) === 'Pendapatan Bunga'
                && ($row[2] ?? null) === 150000.0,
        ));
        $this->assertTrue(collect($rows)->contains(
            fn (array $row): bool => ($row[1] ?? null) === 'Total Pendapatan' && ($row[2] ?? null) === 150000.0,
        ));
        $this->assertTrue(collect($rows)->contains(
            fn (array $row): bool => ($row[1] ?? null) === 'Total Beban' && ($row[2] ?? null) === 120000.0,
        ));
        $this->assertTrue(collect($rows)->contains(
            fn (array $row): bool => ($row[1] ?? null) === 'Laba/(Rugi) Bersih' && ($row[2] ?? null) === 30000.0,
        ));
    }

    public function test_the_excel_download_route_is_permission_gated_and_returns_a_file(): void
    {
        $this->seed(CoreAccountingSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.laba-rugi.excel', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertForbidden();

        Permission::findOrCreate('report.laba_rugi.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo('report.laba_rugi.view');

        $this->actingAs($user)
            ->get(route('reports.laba-rugi.excel', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertDownload('laporan-laba-rugi-2026-10-01_2026-10-31.xlsx');
    }

    public function test_the_page_renders_and_generates_a_report(): void
    {
        $this->seed(CoreAccountingSeeder::class);

        $accounts = $this->prepareAccounts();
        $user = User::factory()->create();

        Permission::findOrCreate('report.laba_rugi.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo('report.laba_rugi.view');

        $this->actingAs($user);

        $this->postJournal($accounts['4100'], 'CREDIT', '50000.00', '2026-10-05', $user);

        Filament::setCurrentPanel(Filament::getPanel('user'));

        Livewire::test(LaporanLabaRugi::class)
            ->fillForm([
                'from' => '2026-10-01',
                'to' => '2026-10-31',
            ])
            ->call('generateReport')
            ->assertHasNoFormErrors()
            ->assertSee('Laporan Laba Rugi')
            ->assertSee('Rp 50.000,00');
    }

    /**
     * @return array<string, CoaAccount>
     */
    private function prepareAccounts(): array
    {
        $revenueType = AccountType::query()->where('code', 'REVENUE')->sole();

        $accounts = [
            '4000' => CoaAccount::query()->where('code', '4000')->sole(),
            '5000' => CoaAccount::query()->where('code', '5000')->sole(),
        ];

        foreach ([
            '4100' => 'Pendapatan Bunga',
            '4110' => 'Pendapatan Administrasi',
            '4120' => 'Pendapatan Provisi',
            '4200' => 'Pendapatan Denda',
        ] as $code => $name) {
            $accounts[$code] = CoaAccount::query()->create([
                'code' => $code,
                'key_map' => strtoupper(str_replace(' ', '_', $name)),
                'name' => $name,
                'account_type_id' => $revenueType->getKey(),
                'normal_balance' => 'CREDIT',
                'level' => 1,
                'is_postable' => true,
                'is_active' => true,
            ]);
        }

        return $accounts;
    }

    private function postJournal(
        CoaAccount $account,
        string $position,
        string $amount,
        string $date,
        User $user,
        string $status = 'POSTED',
    ): Jurnal {
        return Jurnal::query()->create([
            'jurnal_number' => 'TEST-'.uniqid(),
            'jurnal_date' => $date,
            'status' => $status,
            'created_by' => $user->getKey(),
            'coa_account_id' => $account->getKey(),
            'position' => $position,
            'amount' => $amount,
        ]);
    }
}

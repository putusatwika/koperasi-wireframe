<?php

namespace Tests\Feature;

use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\ReportGroup;
use App\Models\User;
use App\Services\Accounting\AccountingReportService;
use Carbon\CarbonImmutable;
use Database\Seeders\CoreAccountingSeeder;
use Database\Seeders\ReportGroupSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountingReportServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_calculates_posted_balances_for_the_selected_report_group_and_period(): void
    {
        $this->seed(CoreAccountingSeeder::class);
        $this->seed(ReportGroupSeeder::class);

        $user = User::factory()->create();
        $cashAccount = CoaAccount::query()->where('code', '1100')->firstOrFail();

        Jurnal::create([
            'jurnal_number' => 'JRN-REPORT-001',
            'jurnal_date' => '2026-09-15',
            'status' => 'POSTED',
            'created_by' => $user->getKey(),
            'coa_account_id' => $cashAccount->getKey(),
            'position' => 'DEBIT',
            'amount' => '100.00',
        ]);
        Jurnal::create([
            'jurnal_number' => 'JRN-REPORT-001',
            'jurnal_date' => '2026-09-15',
            'status' => 'POSTED',
            'created_by' => $user->getKey(),
            'coa_account_id' => $cashAccount->getKey(),
            'position' => 'CREDIT',
            'amount' => '20.00',
        ]);

        Jurnal::create([
            'jurnal_number' => 'JRN-REPORT-002',
            'jurnal_date' => '2026-09-15',
            'status' => 'DRAFT',
            'created_by' => $user->getKey(),
            'coa_account_id' => $cashAccount->getKey(),
            'position' => 'DEBIT',
            'amount' => '1000.00',
        ]);

        $report = app(AccountingReportService::class)->generate(
            ReportGroup::query()->where('name', 'Neraca')->firstOrFail(),
            CarbonImmutable::parse('2026-09-01'),
            CarbonImmutable::parse('2026-09-30'),
        );

        $cashRow = collect($report['rows'])->firstWhere('code', '1100');

        $this->assertSame('100.00', $cashRow['debit']);
        $this->assertSame('20.00', $cashRow['credit']);
        $this->assertSame('80.00', $cashRow['balance']);
        $this->assertSame('100.00', $report['totals']['debit']);
        $this->assertSame('20.00', $report['totals']['credit']);
        $this->assertSame('80.00', $report['totals']['balance']);
    }
}

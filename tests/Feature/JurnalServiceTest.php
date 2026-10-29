<?php

namespace Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\Jurnal;
use App\Models\User;
use App\Services\Accounting\JurnalService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class JurnalServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_creates_one_jurnal_row_in_the_jurnals_table(): void
    {
        $accountType = AccountType::create(['code' => 'ASSET', 'name' => 'Asset']);
        $debitAccount = CoaAccount::create([
            'code' => '1010', 'key_map' => 'CASH', 'name' => 'Cash', 'account_type_id' => $accountType->getKey(), 'normal_balance' => 'DEBIT',
        ]);
        $creditAccount = CoaAccount::create([
            'code' => '2010', 'key_map' => 'PAYABLE', 'name' => 'Payable', 'account_type_id' => $accountType->getKey(), 'normal_balance' => 'CREDIT',
        ]);

        app(JurnalService::class)->create([
            'jurnal_number' => 'JRN-001',
            'jurnal_date' => now()->toDateString(),
            'created_by' => User::factory()->create()->getKey(),
            'coa_account_id' => $debitAccount->getKey(),
            'position' => 'DEBIT',
            'amount' => '100.00',
        ]);

        app(JurnalService::class)->create([
            'jurnal_number' => 'JRN-001',
            'jurnal_date' => now()->toDateString(),
            'created_by' => User::factory()->create()->getKey(),
            'coa_account_id' => $creditAccount->getKey(),
            'position' => 'CREDIT',
            'amount' => '100.00',
        ]);

        $this->assertSame(2, Jurnal::query()->where('jurnal_number', 'JRN-001')->count());
        $this->assertFalse(Schema::hasTable('core_jurnal_lines'));
    }

    public function test_it_rolls_back_all_jurnal_rows_when_a_line_is_invalid(): void
    {
        $user = User::factory()->create();
        $accountType = AccountType::create(['code' => 'ASSET', 'name' => 'Asset']);
        $account = CoaAccount::create([
            'code' => '1010', 'key_map' => 'CASH', 'name' => 'Cash', 'account_type_id' => $accountType->getKey(), 'normal_balance' => 'DEBIT',
        ]);

        $this->expectException(InvalidArgumentException::class);

        try {
            app(JurnalService::class)->create([
                'jurnal_number' => 'JRN-002',
                'jurnal_date' => now()->toDateString(),
                'created_by' => $user->getKey(),
                'coa_account_id' => $account->getKey(),
                'position' => 'INVALID',
                'amount' => '100.00',
            ]);
        } finally {
            $this->assertDatabaseMissing('core_jurnals', ['jurnal_number' => 'JRN-002']);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use Database\Seeders\CoreAccountingSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CoreAccountingSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_core_accounting_seeders_are_idempotent_and_create_relations(): void
    {
        $this->seed(CoreAccountingSeeder::class);
        $this->seed(CoreAccountingSeeder::class);

        $this->assertSame(5, AccountType::query()->count());
        $this->assertSame(8, CoaAccount::query()->count());
        $this->assertSame(
            '1000',
            CoaAccount::query()->where('code', '1100')->firstOrFail()->parent->code,
        );
        $this->assertSame(5, CoaAccount::query()->where('is_postable', true)->whereHas('accountType')->distinct('account_type_id')->count('account_type_id'));
    }
}

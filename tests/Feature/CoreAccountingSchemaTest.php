<?php

namespace Tests\Feature;

use App\Models\AccountType;
use App\Models\CoaAccount;
use App\Models\Jurnal;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreAccountingSchemaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_core_accounting_tables_use_the_core_prefix(): void
    {
        foreach ([
            'core_account_types',
            'core_coa_accounts',
            'core_jurnals',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
    }

    public function test_normal_balance_belongs_to_coa_accounts(): void
    {
        $this->assertFalse(Schema::hasColumn('core_account_types', 'normal_balance'));
        $this->assertTrue(Schema::hasColumn('core_coa_accounts', 'normal_balance'));
        $this->assertTrue(Schema::hasColumn('core_coa_accounts', 'key_map'));
    }

    public function test_accounting_models_use_the_core_tables(): void
    {
        $this->assertSame('core_account_types', (new AccountType)->getTable());
        $this->assertSame('core_coa_accounts', (new CoaAccount)->getTable());
        $this->assertSame('core_jurnals', (new Jurnal)->getTable());
        $this->assertFalse(Schema::hasTable('core_accounting_events'));
        $this->assertFalse(Schema::hasTable('core_accounting_event_lines'));
        $this->assertFalse(Schema::hasTable('core_jurnal_lines'));
        $this->assertFalse(Schema::hasColumn('core_jurnals', 'accounting_event_id'));
        $this->assertFalse(Schema::hasColumn('core_jurnals', 'line_order'));
    }
}

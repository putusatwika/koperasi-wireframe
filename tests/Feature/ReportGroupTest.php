<?php

namespace Tests\Feature;

use App\Models\ReportGroup;
use Database\Seeders\CoreAccountingSeeder;
use Database\Seeders\ReportGroupSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportGroupTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_report_group_tables_have_the_expected_relationship_structure(): void
    {
        $this->assertTrue(Schema::hasTable('core_report_groups'));
        $this->assertTrue(Schema::hasTable('core_report_group_lines'));
        $this->assertTrue(Schema::hasColumn('core_report_group_lines', 'report_group_id'));
        $this->assertTrue(Schema::hasColumn('core_report_group_lines', 'account_type_id'));
        $this->assertTrue(Schema::hasColumn('core_report_group_lines', 'display_order'));
    }

    public function test_report_group_seeder_is_idempotent_and_creates_lines(): void
    {
        $this->seed(CoreAccountingSeeder::class);
        $this->seed(ReportGroupSeeder::class);
        $this->seed(ReportGroupSeeder::class);

        $this->assertSame(2, ReportGroup::query()->count());
        $this->assertSame(3, ReportGroup::query()->where('name', 'Neraca')->firstOrFail()->reportGroupLines()->count());
        $this->assertSame(2, ReportGroup::query()->where('name', 'Laporan Laba Rugi')->firstOrFail()->reportGroupLines()->count());
    }
}

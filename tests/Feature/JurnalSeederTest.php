<?php

namespace Tests\Feature;

use App\Models\CoaAccount;
use App\Models\Jurnal;
use Database\Seeders\CoreAccountingSeeder;
use Database\Seeders\JurnalSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JurnalSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_jurnal_seeder_creates_posted_jurnals_and_is_idempotent(): void
    {
        $this->seed(CoreAccountingSeeder::class);
        $this->seed(SuperAdminSeeder::class);
        $this->seed(JurnalSeeder::class);
        $this->seed(JurnalSeeder::class);

        $this->assertSame(10, Jurnal::query()->count());
        $this->assertSame(10, Jurnal::query()->where('status', 'POSTED')->count());
        $this->assertSame(5, CoaAccount::query()->where('is_postable', true)->whereHas('jurnals')->count());
        $this->assertSame(1500000.0, (float) Jurnal::query()
            ->where('jurnal_number', 'JRN-SEED-001')
            ->where('position', 'DEBIT')
            ->firstOrFail()
            ->amount);
    }
}

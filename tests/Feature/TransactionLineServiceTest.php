<?php

namespace Tests\Feature;

use App\Models\JurnalLine;
use App\Services\Accounting\JurnalLineService;
use Tests\TestCase;

class TransactionLineServiceTest extends TestCase
{
    public function test_transaction_line_service_was_removed_after_merging_jurnal_lines(): void
    {
        $this->assertFalse(class_exists(JurnalLineService::class));
        $this->assertFalse(class_exists(JurnalLine::class));
    }
}

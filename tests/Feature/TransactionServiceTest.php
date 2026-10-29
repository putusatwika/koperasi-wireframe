<?php

namespace Tests\Feature;

use App\Models\Jurnal;
use App\Services\Accounting\JurnalService;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    public function test_transaction_service_was_replaced_by_jurnal_service(): void
    {
        $this->assertTrue(class_exists(JurnalService::class));
        $this->assertTrue(class_exists(Jurnal::class));
    }
}

<?php

namespace Modules\Pembayaran\Tests\Feature;

use Modules\Pembayaran\Enums\JenisPembayaran;
use Modules\Pembayaran\Enums\PembayaranCOAConfigEnum;
use Modules\Pembayaran\Models\Pembayaran;
use Tests\TestCase;

class PembayaranModuleTest extends TestCase
{
    public function test_pembayaran_uses_the_module_table_and_casts_payment_type(): void
    {
        $pembayaran = (new Pembayaran)->fill([
            'payment_type' => JenisPembayaran::Autodebet->value,
        ]);

        $this->assertSame('pembayaran_pembayaran', $pembayaran->getTable());
        $this->assertSame(JenisPembayaran::Autodebet, $pembayaran->payment_type);
    }

    public function test_payment_coa_configuration_contains_required_journal_accounts(): void
    {
        $this->assertSame('PAYMENT_CASH', PembayaranCOAConfigEnum::PaymentCash->value);
        $this->assertSame('LOAN_RECEIVABLE', PembayaranCOAConfigEnum::LoanReceivable->value);
        $this->assertSame('INTEREST_INCOME', PembayaranCOAConfigEnum::InterestIncome->value);
        $this->assertSame('PENALTY_INCOME', PembayaranCOAConfigEnum::PenaltyIncome->value);
    }
}

<?php

namespace Modules\Pembayaran\Listeners;

use Modules\Pembayaran\Services\PembayaranService;
use Modules\Peminjaman\Events\PinjamanDicairkan;

class RecordFirstInstallmentDeduction
{
    public function __construct(private readonly PembayaranService $service) {}

    public function handle(PinjamanDicairkan $event): void
    {
        $this->service->recordFirstInstallmentDeduction(
            $event->peminjaman,
            $event->principal,
            $event->interest,
            $event->paymentDate,
            $event->disbursedBy,
        );
    }
}

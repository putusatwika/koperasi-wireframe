<?php

namespace Modules\Peminjaman\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Peminjaman\Models\Peminjaman;

class PinjamanDicairkan
{
    use Dispatchable;

    public function __construct(
        public readonly Peminjaman $peminjaman,
        public readonly float $principal,
        public readonly float $interest,
        public readonly string $paymentDate,
        public readonly int $disbursedBy,
    ) {}
}

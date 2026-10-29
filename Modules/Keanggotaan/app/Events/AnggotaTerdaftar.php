<?php

namespace Modules\Keanggotaan\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Keanggotaan\Models\Anggota;

class AnggotaTerdaftar
{
    use Dispatchable;

    /**
     * @param  array{pokok?: float, wajib?: float}  $initialSavings
     */
    public function __construct(
        public readonly Anggota $anggota,
        public readonly array $initialSavings = [],
    ) {}
}

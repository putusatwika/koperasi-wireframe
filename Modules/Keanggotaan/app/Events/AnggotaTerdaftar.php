<?php

namespace Modules\Keanggotaan\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Keanggotaan\Models\Anggota;

class AnggotaTerdaftar
{
    use Dispatchable;

    public function __construct(public readonly Anggota $anggota) {}
}

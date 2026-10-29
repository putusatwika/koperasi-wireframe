<?php

namespace Modules\Peminjaman\Enums;

enum JenisBungaPeminjaman: string
{
    case Flat = 'flat';
    case Effective = 'effective';
    case Annuity = 'annuity';
}

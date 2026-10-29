<?php

namespace Modules\Peminjaman\Enums;

enum StatusJadwalAngsuran: string
{
    case Belum = 'BELUM';
    case Sebagian = 'SEBAGIAN';
    case Lunas = 'LUNAS';
}

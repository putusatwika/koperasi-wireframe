<?php

namespace Modules\Keuangan\Enums;

enum TipeTransaksiKeuangan: string
{
    case Masuk = 'MASUK';
    case Keluar = 'KELUAR';
}

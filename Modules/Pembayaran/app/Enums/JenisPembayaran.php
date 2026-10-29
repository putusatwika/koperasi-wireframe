<?php

namespace Modules\Pembayaran\Enums;

enum JenisPembayaran: string
{
    case Manual = 'MANUAL';
    case Autodebet = 'AUTODEBET';
    case Potongan = 'POTONGAN';
}

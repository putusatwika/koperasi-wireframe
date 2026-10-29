<?php

namespace Modules\Simpanan\Enums;

enum TipeTransaksiSimpanan: string
{
    case Setor = 'SETOR';
    case Tarik = 'TARIK';
    case Bunga = 'BUNGA';
}

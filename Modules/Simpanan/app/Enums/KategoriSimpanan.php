<?php

namespace Modules\Simpanan\Enums;

enum KategoriSimpanan: string
{
    case Pokok = 'POKOK';
    case Wajib = 'WAJIB';
    case Sukarela = 'SUKARELA';
    case Berjangka = 'BERJANGKA';
    case Lainnya = 'LAINNYA';
}

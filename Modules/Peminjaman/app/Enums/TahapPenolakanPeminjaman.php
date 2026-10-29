<?php

namespace Modules\Peminjaman\Enums;

enum TahapPenolakanPeminjaman: string
{
    case Pemeriksaan = 'PEMERIKSAAN';
    case Persetujuan = 'PERSETUJUAN';
}

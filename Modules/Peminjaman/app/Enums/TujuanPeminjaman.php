<?php

namespace Modules\Peminjaman\Enums;

enum TujuanPeminjaman: string
{
    case Consumption = 'consumption';
    case Vehicle = 'vehicle';
    case Education = 'education';
    case Productive = 'productive';
    case Other = 'other';
}

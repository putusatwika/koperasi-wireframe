<?php

namespace App\Support;

use DomainException;

final class Money
{
    public static function toCents(string $amount): int
    {
        if (preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) !== 1) {
            throw new DomainException('Format nilai uang tidak valid.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function fromCents(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), abs($amount % 100));
    }
}

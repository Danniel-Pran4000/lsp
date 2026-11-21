<?php

namespace App\Support;

use Stringable;

class Rupiah
{
    public static function format(string|int|float|Stringable $amount): string
    {
        [$integer, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '');
        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integer);
        $fraction = rtrim($fraction, '0');

        return 'Rp '.$integer.($fraction === '' ? '' : ','.$fraction);
    }
}

<?php

namespace App\Support;

use Stringable;

class Rupiah
{
    /**
     * Format nominal untuk tampilan rupiah dengan mempertahankan pecahan yang masih bernilai.
     */
    public static function formatAmount(string|int|float|Stringable $amount): string
    {
        [$integer, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '');
        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integer);
        $fraction = rtrim($fraction, '0');

        return 'Rp '.$integer.($fraction === '' ? '' : ','.$fraction);
    }
}

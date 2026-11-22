<?php

namespace App\Support;

use Stringable;

class Rupiah
{
    /**
     * Beri pemisah ribuan pada nominal rupiah bulat tanpa konversi ke float.
     */
    public static function formatAmount(string|int|Stringable $amount): string
    {
        return 'Rp '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $amount);
    }
}

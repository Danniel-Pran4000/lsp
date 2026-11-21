<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class RentalCalculator
{
    public function endDate(CarbonImmutable $start, int $months, int $days): CarbonImmutable
    {
        $nextStart = $start;

        // Jika tanggal yang sama tidak ada, periode berakhir pada akhir bulan tujuan.
        while ($months > 0 && $nextStart->day > 28) {
            $nextMonth = $nextStart->addMonthNoOverflow();
            $nextStart = $nextMonth->day === $nextStart->day ? $nextMonth : $nextMonth->addDay();
            $months--;
        }

        // Tanggal 1–28 selalu tersedia di setiap bulan.
        return $nextStart->addMonthsNoOverflow($months)->addDays($days)->subDay();
    }
}

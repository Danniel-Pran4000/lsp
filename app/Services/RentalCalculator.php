<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Rental;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RentalCalculator
{
    private const BILLABLE_DAYS_PER_MONTH = 25;

    /**
     * Tentukan hari terakhir unit ditempati. Hari mulai sudah termasuk masa sewa,
     * sehingga sewa satu hari berakhir pada tanggal yang sama.
     */
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

    /**
     * Satu bulan mengikuti kalender untuk masa pakai, tetapi biayanya setara 25 hari.
     * Untuk garasi listrik, tagihkan energi yang diperlukan sampai baterai penuh.
     * Bulatkan total setelah biaya sewa dan listrik dijumlahkan agar pecahan rupiah tetap terhitung.
     */
    public function initialCharge(Garage $garage, array $details): array
    {
        if ($details['vehicle_category'] === 'electric_car' && ! isset($details['remaining_percent'])) {
            throw ValidationException::withMessages(['remaining_percent' => 'Masukkan persentase sisa baterai.']);
        }

        $dailyRate = BigDecimal::of((string) $garage->daily_rate)->toScale(2);
        $billableDays = (int) ($details['months'] ?? 0) * self::BILLABLE_DAYS_PER_MONTH + (int) $details['days'];
        $rentalAmount = $dailyRate->multipliedBy($billableDays);
        $kwhRate = null;
        $energy = BigDecimal::zero();
        $electricityAmount = BigDecimal::zero();

        if ($garage->type === 'E') {
            if ($garage->kwh_rate === null) {
                throw ValidationException::withMessages(['garage_id' => 'Tarif listrik untuk unit ini belum tersedia.']);
            }

            $kwhRate = BigDecimal::of((string) $garage->kwh_rate)->toScale(2);
            $missingPercent = BigDecimal::of(100)->minus((string) $details['remaining_percent']);
            $energy = BigDecimal::of((string) $details['battery_kwh'])
                ->multipliedBy($missingPercent)->dividedByExact(100);
            $electricityAmount = $energy->multipliedBy($kwhRate);
        }

        $total = $rentalAmount->plus($electricityAmount)->toScale(0, RoundingMode::HalfUp);

        if ($electricityAmount->compareTo('1000000000000000') >= 0 || $total->compareTo(PHP_INT_MAX) > 0) {
            throw ValidationException::withMessages(['garage_id' => 'Biaya melebihi batas yang didukung. Periksa durasi atau pilih unit lain.']);
        }

        return [
            'daily_rate' => (string) $dailyRate,
            'kwh_rate' => $kwhRate === null ? null : (string) $kwhRate,
            'energy_kwh' => (string) $energy->toScale(7),
            'rental_amount' => (string) $rentalAmount,
            'electricity_amount' => (string) $electricityAmount->toScale(9),
            'initial_total' => (string) $total,
        ];
    }

    /**
     * Hari terakhir sewa lama sudah dibayar. Memindahkan akhir sewa dari 2 ke 3 November
     * menambah satu hari dengan tarif harian yang tersimpan pada transaksi awal.
     */
    public function extensionCharge(Rental $rental, CarbonImmutable $newEnd): array
    {
        $oldEnd = CarbonImmutable::parse($rental->ends_on);
        if ($newEnd->lessThanOrEqualTo($oldEnd)) {
            throw ValidationException::withMessages(['ends_on' => 'Tanggal selesai baru harus setelah tanggal selesai saat ini.']);
        }

        $days = (int) $oldEnd->diffInDays($newEnd);
        $amount = BigDecimal::of((string) $rental->daily_rate)
            ->multipliedBy($days)
            ->toScale(0, RoundingMode::HalfUp);

        return ['days' => $days, 'amount' => (string) $amount];
    }
}

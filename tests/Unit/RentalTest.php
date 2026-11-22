<?php

namespace Tests\Unit;

use App\Models\Rental;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RentalTest extends TestCase
{
    /**
     * Pastikan kedua batas periode termasuk hari aktif sewa.
     */
    #[DataProvider('datesAroundRental')]
    public function testTheRentalIsActiveOnBothPeriodBoundaries(string $date, bool $expected): void
    {
        $rental = (new Rental)->forceFill(['starts_on' => '2026-11-21', 'ends_on' => '2026-11-23']);

        $this->assertSame($expected, $rental->coversDate(CarbonImmutable::parse($date)));
    }

    /**
     * Sediakan tanggal di dalam dan di luar periode untuk memeriksa status aktif.
     */
    public static function datesAroundRental(): array
    {
        return [
            'UT-14 before start' => ['2026-11-20', false],
            'UT-14 start date' => ['2026-11-21', true],
            'UT-14 between start and end' => ['2026-11-22', true],
            'UT-14 end date' => ['2026-11-23', true],
            'UT-14 after end' => ['2026-11-24', false],
        ];
    }
}

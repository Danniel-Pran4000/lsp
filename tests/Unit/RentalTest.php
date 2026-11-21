<?php

namespace Tests\Unit;

use App\Models\Rental;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RentalTest extends TestCase
{
    #[DataProvider('datesAroundRental')]
    public function test_the_rental_is_active_on_both_period_boundaries(string $date, bool $expected): void
    {
        $rental = (new Rental)->forceFill(['starts_on' => '2026-11-21', 'ends_on' => '2026-11-23']);

        $this->assertSame($expected, $rental->isActiveOn(CarbonImmutable::parse($date)));
    }

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

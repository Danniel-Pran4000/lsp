<?php

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\Rental;
use App\Services\RentalCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RentalCalculatorTest extends TestCase
{
    #[DataProvider('rentalPeriods')]
    public function test_it_calculates_an_inclusive_end_date(string $start, int $months, int $days, string $end): void
    {
        $actual = (new RentalCalculator)->endDate(CarbonImmutable::parse($start), $months, $days);

        $this->assertSame($end, $actual->toDateString());
    }

    public static function rentalPeriods(): array
    {
        return [
            'UT-01 one day' => ['2026-11-21', 0, 1, '2026-11-21'],
            'UT-01 three days' => ['2026-11-21', 0, 3, '2026-11-23'],
            'UT-02 first day of the month' => ['2026-11-01', 1, 0, '2026-11-30'],
            'UT-02 short February' => ['2027-01-31', 1, 0, '2027-02-28'],
            'UT-02 leap February' => ['2028-01-31', 1, 0, '2028-02-29'],
            'UT-03 two months and three days' => ['2028-01-31', 2, 3, '2028-04-03'],
        ];
    }

    #[DataProvider('bicycleCharges')]
    public function test_it_prices_days_and_months_at_the_daily_rate(int $months, int $days, string $total): void
    {
        $garage = new Garage(['type' => 'S', 'daily_rate' => '50000.00']);
        $charge = (new RentalCalculator)->initialCharge($garage, [
            'vehicle_category' => 'bicycle',
            'months' => $months,
            'days' => $days,
        ]);

        $this->assertSame($total, $charge['initial_total']);
    }

    public static function bicycleCharges(): array
    {
        return [
            'UT-04 daily' => [0, 3, '150000'],
            'UT-04 monthly' => [1, 0, '1250000'],
            'UT-04 monthly plus days' => [1, 3, '1400000'],
        ];
    }

    #[DataProvider('electricCharges')]
    public function test_it_charges_for_the_missing_battery_capacity(
        string $garageType,
        ?string $kwhRate,
        string $remainingPercent,
        string $energy,
        string $electricity,
        string $total,
    ): void {
        $garage = new Garage(['type' => $garageType, 'daily_rate' => '75000.00', 'kwh_rate' => $kwhRate]);
        $charge = (new RentalCalculator)->initialCharge($garage, [
            'vehicle_category' => 'electric_car',
            'days' => 3,
            'battery_kwh' => '60',
            'remaining_percent' => $remainingPercent,
        ]);

        $this->assertSame('225000.00', $charge['rental_amount']);
        $this->assertSame($kwhRate, $charge['kwh_rate']);
        $this->assertSame($energy, $charge['energy_kwh']);
        $this->assertSame($electricity, $charge['electricity_amount']);
        $this->assertSame($total, $charge['initial_total']);
    }

    public static function electricCharges(): array
    {
        return [
            'UT-05 partially charged' => ['E', '2000.00', '30', '42.0000000', '84000.000000000', '309000'],
            'UT-06 empty battery' => ['E', '2000.00', '0', '60.0000000', '120000.000000000', '345000'],
            'UT-06 full battery' => ['E', '2000.00', '100', '0.0000000', '0.000000000', '225000'],
            'UT-07 electric car in a C garage' => ['C', null, '30', '0.0000000', '0.000000000', '225000'],
        ];
    }

    public function test_it_rounds_the_combined_total_once(): void
    {
        $garage = new Garage(['type' => 'E', 'daily_rate' => '100.25', 'kwh_rate' => '0.50']);
        $charge = (new RentalCalculator)->initialCharge($garage, [
            'vehicle_category' => 'electric_car',
            'days' => 1,
            'battery_kwh' => '1',
            'remaining_percent' => '50',
        ]);

        $this->assertSame('100.25', $charge['rental_amount']);
        $this->assertSame('0.250000000', $charge['electricity_amount']);
        $this->assertSame('101', $charge['initial_total']);
    }

    #[DataProvider('incompleteCharges')]
    public function test_it_rejects_missing_charge_inputs(?string $kwhRate, array $details, string $errorField): void
    {
        $garage = new Garage(['type' => 'E', 'daily_rate' => '75000.00', 'kwh_rate' => $kwhRate]);

        try {
            (new RentalCalculator)->initialCharge($garage, [
                'vehicle_category' => 'electric_car',
                'days' => 1,
                'battery_kwh' => '60',
                ...$details,
            ]);
            $this->fail('The calculator accepted missing charge inputs.');
        } catch (ValidationException $exception) {
            $this->assertSame([$errorField], array_keys($exception->errors()));
        }
    }

    public static function incompleteCharges(): array
    {
        return [
            'UT-09 battery percentage missing' => ['2000.00', [], 'remaining_percent'],
            'UT-09 electricity tariff missing' => [null, ['remaining_percent' => '30'], 'garage_id'],
        ];
    }

    public function test_successive_extensions_charge_only_the_added_days(): void
    {
        $calculator = new RentalCalculator;
        $rental = (new Rental)->forceFill([
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-02',
            'daily_rate' => '75000.00',
        ]);

        $firstEnd = CarbonImmutable::parse('2026-11-03');
        $this->assertSame(['days' => 1, 'amount' => '75000'], $calculator->extensionCharge($rental, $firstEnd));

        $rental->ends_on = $firstEnd->toDateString();
        $secondEnd = CarbonImmutable::parse('2026-11-05');
        $this->assertSame(['days' => 2, 'amount' => '150000'], $calculator->extensionCharge($rental, $secondEnd));
    }

    #[DataProvider('invalidExtensionDates')]
    public function test_it_rejects_an_end_date_that_does_not_extend_the_rental(string $newEnd): void
    {
        $rental = (new Rental)->forceFill(['ends_on' => '2026-11-03', 'daily_rate' => '75000.00']);

        try {
            (new RentalCalculator)->extensionCharge($rental, CarbonImmutable::parse($newEnd));
            $this->fail('The calculator accepted a date without additional rental days.');
        } catch (ValidationException $exception) {
            $this->assertSame(['ends_on'], array_keys($exception->errors()));
        }
    }

    public static function invalidExtensionDates(): array
    {
        return [
            'UT-11 same day' => ['2026-11-03'],
            'UT-11 earlier day' => ['2026-11-02'],
        ];
    }

    public function test_it_uses_the_saved_daily_rate_to_extend_a_monthly_rental(): void
    {
        $rental = (new Rental)->forceFill([
            'rental_type' => 'monthly',
            'ends_on' => '2026-11-30',
            'daily_rate' => '40000.00',
        ]);

        $charge = (new RentalCalculator)->extensionCharge($rental, CarbonImmutable::parse('2026-12-02'));

        $this->assertSame(['days' => 2, 'amount' => '80000'], $charge);
    }
}

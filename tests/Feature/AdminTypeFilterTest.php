<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Periksa bahwa hari pertama dan terakhir sewa ikut menentukan unit yang kosong hari ini.
     */
    public function testGarageListFiltersFreeUnitsUsingInclusiveRentalDates(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $this->get(route('garages.index'))->assertRedirect(route('login'));
        $renter = User::factory()->create(['role' => 'user']);
        $this->actingAs($renter)->get(route('garages.index'))->assertForbidden();

        $endingToday = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 50000]);
        $startingToday = Garage::create(['code' => 'S002', 'type' => 'S', 'daily_rate' => 50000]);
        $past = Garage::create(['code' => 'C001', 'type' => 'C', 'daily_rate' => 75000]);
        $future = Garage::create(['code' => 'E001', 'type' => 'E', 'daily_rate' => 75000, 'kwh_rate' => 2000]);
        $past->status = 'used';
        $past->save();
        $this->rental($renter, $endingToday, 'bicycle', '2026-11-20', '2026-11-21');
        $this->rental($renter, $startingToday, 'motorcycle', '2026-11-21', '2026-11-22');
        $this->rental($renter, $past, 'gas_car', '2026-11-19', '2026-11-20');
        $this->rental($renter, $future, 'electric_car', '2026-11-22', '2026-11-23');

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('garages.index'))
            ->assertOk()->assertSee('C001')->assertSee('E001')->assertDontSee('S001')->assertDontSee('S002');
        $this->get(route('garages.index', ['type' => 'E']))
            ->assertOk()->assertSee('E001')->assertDontSee('C001');
        $this->get(route('garages.index', ['type' => 'S']))
            ->assertOk()->assertSee('Tidak ada unit garasi kosong untuk tipe ini.');
        $this->get(route('garages.index', ['type' => ['E']]))
            ->assertOk()->assertSee('C001')->assertSee('E001');
        $this->assertSame('used', $past->refresh()->status);
    }

    /**
     * Pastikan filter memakai tipe garasi transaksi dan tetap berlaku pada halaman berikutnya.
     */
    public function testRentalsFilterUsesTheAssignedGarageTypeAndKeepsTheSelectionOnPageTwo(): void
    {
        $renter = User::factory()->create(['role' => 'user']);
        $garage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 50000]);
        $bike = $this->rental($renter, $garage, 'bicycle', '2026-11-24', '2026-11-25');
        $petrolGarage = Garage::create(['code' => 'C001', 'type' => 'C', 'daily_rate' => 75000]);
        $electricCar = $this->rental($renter, $petrolGarage, 'electric_car', '2026-11-26', '2026-11-26');
        for ($day = 3; $day <= 23; $day++) {
            $date = sprintf('2026-11-%02d', $day);
            $latestMotorcycle = $this->rental($renter, $garage, 'motorcycle', $date, $date);
        }

        $bikeLink = 'href="'.route('extensions.preview', $bike).'"';
        $motorcycleLink = 'href="'.route('extensions.preview', $latestMotorcycle).'"';
        $carLink = 'href="'.route('extensions.preview', $electricCar).'"';
        $pageTwo = route('admin.rentals', ['type' => 'S', 'page' => 2]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.rentals', ['type' => 'S']))
            ->assertOk()->assertSee('Tipe garasi')->assertSee($pageTwo)
            ->assertSee($motorcycleLink, false)->assertSee($bikeLink, false)->assertDontSee($carLink, false);
        $this->get($pageTwo)
            ->assertOk()->assertSee('2026-11-03')->assertDontSee($carLink, false);
        $this->get(route('admin.rentals', ['type' => 'C']))
            ->assertOk()->assertSee($carLink, false)->assertDontSee($bikeLink, false);
    }

    /**
     * Siapkan transaksi dengan kategori dan periode pilihan untuk membandingkan hasil filter.
     */
    private function rental(User $renter, Garage $garage, string $category, string $start, string $end): Rental
    {
        return Rental::forceCreate([
            'user_id' => $renter->id, 'garage_id' => $garage->id, 'vehicle_category' => $category,
            'brand' => 'Test brand', 'vehicle_model' => 'Test model', 'rental_type' => 'daily',
            'months' => 0, 'days' => $start === $end ? 1 : 2, 'starts_on' => $start, 'ends_on' => $end,
            'daily_rate' => 50000, 'rental_amount' => 50000, 'initial_total' => 50000,
            'proof_path' => 'proof.png',
        ]);
    }
}

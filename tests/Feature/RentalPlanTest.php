<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Periksa pilihan durasi dan pemulihan isian saat formulir sewa ditampilkan kembali.
     */
    public function testSingleFormOffersBothDurationTypesAndPreservesEnteredValues(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $this->actingAs($user)->withSession(['_old_input' => ['rental_type' => 'monthly', 'brand' => 'Sample brand']])
            ->get(route('rentals.create'))->assertOk()
            ->assertSee('value="monthly" selected', false)->assertSee('value="daily"', false)
            ->assertSee('name="months"', false)->assertSee('name="days"', false)
            ->assertSee('value="Sample brand"', false);
    }

    /**
     * Pastikan pilihan garasi pada formulir sesuai dengan setiap kategori kendaraan.
     */
    public function testFormSuppliesCompatibleGarageTypesForEachVehicleCategory(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $this->actingAs($user)->get(route('rentals.create'))->assertOk()
            ->assertViewHas('compatibleGarages', [
                'bicycle' => ['S'], 'motorcycle' => ['S'], 'gas_car' => ['C'], 'electric_car' => ['C', 'E'],
            ])->assertSee('data-plate', false)->assertSee('data-battery', false);
    }

    /**
     * Periksa batas harian berdasarkan bulan mulai saat pemesanan dibuat untuk bulan mendatang.
     */
    public function testFutureStartUsesItsOwnMonthForTheDailyLimit(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $payload = [
            'garage_type' => 'S', 'vehicle_category' => 'bicycle', 'brand' => 'Merek uji', 'vehicle_model' => 'Model uji',
            'rental_type' => 'daily', 'starts_on' => '2026-12-01', 'days' => 30,
        ];

        $this->actingAs($user)->post(route('rentals.available'), $payload)
            ->assertOk()->assertSee('30/12/2026');
        $this->post(route('rentals.available'), array_replace($payload, ['starts_on' => '2026-11-28']))
            ->assertSessionHasErrors('days');
        $this->post(route('rentals.available'), array_replace($payload, ['starts_on' => '2026-11-20', 'days' => 1]))
            ->assertSessionHasErrors('starts_on');
    }

    /**
     * Uji durasi maksimum dan tanggal akhir inklusif pada bulan mulai yang dipilih.
     */
    public function testDailyLimitUsesTheStartMonthAndEndDateIsInclusive(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $payload = [
            'garage_type' => 'S', 'vehicle_category' => 'bicycle',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'rental_type' => 'daily',
            'days' => 29,
        ];

        $this->actingAs($user)->get(route('rentals.create'))
            ->assertOk()->assertSee('value="2026-11-21"', false)
            ->assertSee('Pilih unit');
        $this->post(route('rentals.available'), $payload)
            ->assertOk()->assertSee('19/12/2026')->assertViewHas('garageTypes', ['S']);
        $this->post(route('rentals.available'), array_replace($payload, ['days' => 30]))
            ->assertRedirect(route('rentals.create'))->assertSessionHasErrors('days');

        $this->assertDatabaseCount('rentals', 0);
    }

    /**
     * Periksa sewa bulanan yang melewati Februari dan memiliki tambahan hari.
     */
    public function testMonthlyRentalsCrossShortMonthsAndAcceptExtraDays(): void
    {
        $this->travelTo(now()->setDate(2028, 1, 31)->startOfDay());
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $payload = [
            'garage_type' => 'E', 'vehicle_category' => 'electric_car',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'plate' => 'B 1234 ABC',
            'battery_kwh' => '60',
            'remaining_percent' => '0',
            'rental_type' => 'monthly',
            'months' => 1,
        ];

        $this->actingAs($user)->post(route('rentals.available'), $payload)
            ->assertOk()->assertSee('29/02/2028')->assertViewHas('garageTypes', ['C', 'E'])
            ->assertViewHas('details.days', 0);
        $this->post(route('rentals.available'), [...$payload, 'days' => ''])
            ->assertOk()->assertSee('29/02/2028')->assertViewHas('details.days', 0);
        $this->post(route('rentals.available'), array_replace($payload, ['months' => 2, 'days' => 3]))
            ->assertOk()->assertSee('03/04/2028');
    }

    /**
     * Pastikan pratinjau mobil listrik menolak data plat dan baterai yang belum lengkap.
     */
    public function testPlateAndBatteryValuesAreRequiredForAnElectricCar(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);

        $this->actingAs($user)->post(route('rentals.preview'), [
            'garage_id' => 1, 'garage_type' => 'E', 'vehicle_category' => 'electric_car',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'rental_type' => 'daily',
            'days' => 1,
        ])->assertSessionHasErrors(['plate', 'battery_kwh', 'remaining_percent']);
    }

    /**
     * Periksa bahwa hanya penyewa yang disetujui dapat membuka dan mengirim rencana sewa.
     */
    public function testOnlyApprovedRentersCanOpenOrSubmitTheForm(): void
    {
        $this->get(route('rentals.create'))->assertRedirect(route('login'));

        foreach ([
            ['role' => 'user', 'approval_status' => 'pending'],
            ['role' => 'user', 'approval_status' => 'rejected'],
            ['role' => 'admin', 'approval_status' => 'approved'],
        ] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->get(route('rentals.create'))->assertForbidden();
            $this->post(route('rentals.available'))->assertForbidden();
        }
    }
}

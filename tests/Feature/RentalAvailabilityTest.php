<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RentalAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Periksa tipe unit dan benturan pada kedua batas tanggal pencarian.
     */
    public function testSearchFiltersByTypeAndChecksBothEndsOfThePeriod(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $expired = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 10000]);
        $endsToday = Garage::create(['code' => 'S002', 'type' => 'S', 'daily_rate' => 10000]);
        $overlapsEnd = Garage::create(['code' => 'S003', 'type' => 'S', 'daily_rate' => 10000]);
        $startsLater = Garage::create(['code' => 'S004', 'type' => 'S', 'daily_rate' => 10000]);
        Garage::create(['code' => 'C001', 'type' => 'C', 'daily_rate' => 40000]);

        $expired->status = 'used';
        $expired->save();
        $this->recordRental($user, $expired, '2026-11-19', '2026-11-20');
        $this->recordRental($user, $endsToday, '2026-11-20', '2026-11-21');
        $this->recordRental($user, $overlapsEnd, '2026-11-23', '2026-11-24');
        $this->recordRental($user, $startsLater, '2026-11-24', '2026-11-25');

        $this->actingAs($user)->post(route('rentals.available'), $this->searchInput())
            ->assertOk()
            ->assertSee('S001')
            ->assertSee('S004')
            ->assertDontSee('S002')
            ->assertDontSee('S003')
            ->assertDontSee('C001');

        $this->assertSame('available', $expired->refresh()->status);
        $this->assertSame('used', $endsToday->refresh()->status);
        $this->assertSame('available', $overlapsEnd->refresh()->status);
        $this->assertDatabaseCount('rentals', 4);

        $this->post(route('rentals.available'), array_replace($this->searchInput(), ['starts_on' => '2026-11-24']))
            ->assertOk()->assertSee('S001')->assertDontSee('S002')
            ->assertDontSee('S003')->assertDontSee('S004');
    }

    /**
     * Pastikan formulir menjelaskan hasil kosong dan menolak pasangan kendaraan serta garasi yang tidak cocok.
     */
    public function testSearchOffersACompatibleTypeAndExplainsEmptyResults(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);

        $this->actingAs($user)->get(route('rentals.create'))
            ->assertOk()->assertSee('Pilih unit');
        $this->post(route('rentals.available'), $this->searchInput())
            ->assertOk()->assertSee('Tidak ada unit kosong pada periode ini.');
        $this->post(route('rentals.available'), array_replace($this->searchInput(), ['garage_type' => 'C']))
            ->assertRedirect(route('rentals.create'))->assertSessionHasErrors('garage_type');
    }

    /**
     * Periksa agar penyewa yang masih menunggu persetujuan tidak dapat mencari unit.
     */
    public function testPendingRenterCannotSearchForUnits(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'pending']);

        $this->actingAs($user)->post(route('rentals.available'), $this->searchInput())->assertForbidden();
    }

    /**
     * Sediakan rencana sewa sepeda untuk menguji pencarian pada beberapa tanggal.
     */
    private function searchInput(): array
    {
        return [
            'vehicle_category' => 'bicycle',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'rental_type' => 'daily',
            'days' => 3,
            'garage_type' => 'S',
        ];
    }

    /**
     * Isi jadwal unit agar batas benturan dapat diuji tanpa melalui formulir pemesanan.
     */
    private function recordRental(User $user, Garage $garage, string $start, string $end): void
    {
        DB::table('rentals')->insert([
            'user_id' => $user->id,
            'garage_id' => $garage->id,
            'vehicle_category' => 'bicycle',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'rental_type' => 'daily',
            'months' => 0,
            'days' => 2,
            'starts_on' => $start,
            'ends_on' => $end,
            'daily_rate' => 10000,
            'rental_amount' => 20000,
            'initial_total' => 20000,
            'proof_path' => 'bukti-uji.jpg',
        ]);
    }
}

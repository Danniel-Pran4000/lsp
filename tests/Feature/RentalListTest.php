<?php

namespace Tests\Feature;

use App\Models\Extension;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pastikan daftar, jumlah aktif, dan paginasi hanya mencakup sewa akun yang masuk.
     */
    public function testTheListAndActiveCountOnlyIncludeTheSignedInRentersRecordsAcrossPages(): void
    {
        $this->freezeTime();
        $renter = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $otherRenter = User::factory()->create();
        $firstGarage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 50000]);
        $secondGarage = Garage::create(['code' => 'S002', 'type' => 'S', 'daily_rate' => 50000]);
        $otherGarage = Garage::create(['code' => 'S003', 'type' => 'S', 'daily_rate' => 50000]);

        $past = today()->subDay()->toDateString();
        $this->rental($renter, $secondGarage, $past, $past);
        $this->rental($otherRenter, $otherGarage, today()->toDateString(), today()->toDateString());
        $this->rental($renter, $firstGarage, today()->toDateString(), today()->toDateString());
        for ($day = 1; $day <= 19; $day++) {
            $date = today()->addDays($day)->toDateString();
            $this->rental($renter, $firstGarage, $date, $date);
        }

        $this->actingAs($renter)->get(route('account.show'))->assertRedirect(route('rentals.index'));
        $this->get(route('rentals.index', ['user_id' => $otherRenter->id]))
            ->assertOk()->assertViewHas('activeCount', 1)->assertSee('Sewa baru')->assertSee('Rp 50.000')
            ->assertSee('S001')->assertDontSee('S002')->assertDontSee('S003');
        $this->get(route('rentals.index', ['page' => 2]))
            ->assertOk()->assertViewHas('activeCount', 1)
            ->assertSee('S002')->assertDontSee('S003')->assertSee(today()->subDay()->locale('id')->translatedFormat('d M Y'));
    }

    /**
     * Pastikan perubahan status membatasi pemesanan baru sambil mempertahankan akses riwayat.
     */
    public function testHistoryRequiresARenterAccountAndRemainsAvailableAfterRejection(): void
    {
        $this->get(route('rentals.index'))->assertRedirect(route('login'));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('rentals.index'))->assertForbidden();

        $renter = User::factory()->create(['role' => 'user', 'approval_status' => 'pending']);
        $this->actingAs($renter)->get(route('rentals.index'))
            ->assertOk()->assertViewHas('activeCount', 0)->assertSee('Belum ada sewa.')
            ->assertDontSee('Sewa baru')->assertSee('Pending');

        $garage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 50000]);
        $this->rental($renter, $garage, '2026-11-21', '2026-11-24');
        $renter->forceFill(['approval_status' => 'rejected'])->save();

        $this->get(route('rentals.index'))->assertOk()->assertSee('S001')->assertSee('24 Nov 2026')->assertSee('Rejected');
        $this->get(route('rentals.create'))->assertForbidden();
    }

    /**
     * Periksa total agar setiap pembayaran tambahan dihitung tepat sekali.
     */
    public function testTheDisplayedPriceIncludesEveryExtensionOnce(): void
    {
        $renter = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $admin = User::factory()->create(['role' => 'admin']);
        $garage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 50000]);
        $rental = $this->rental($renter, $garage, '2026-11-21', '2026-11-26');

        foreach ([
            ['2026-11-21', '2026-11-23', 100000],
            ['2026-11-23', '2026-11-26', 150000],
        ] as [$oldEnd, $newEnd, $amount]) {
            Extension::create([
                'rental_id' => $rental->id,
                'admin_id' => $admin->id,
                'extended_on' => '2026-11-21',
                'old_end' => $oldEnd,
                'new_end' => $newEnd,
                'amount' => $amount,
                'proof_path' => 'extension.png',
            ]);
        }

        $this->actingAs($renter)->get(route('rentals.index'))
            ->assertOk()->assertSee('Total pembayaran')->assertSee('Rp 300.000')
            ->assertDontSee('Rp 50.000')->assertSee('26 Nov 2026');
    }

    /**
     * Buat transaksi milik penyewa tertentu agar kepemilikan dan periode dapat diuji.
     */
    private function rental(User $user, Garage $garage, string $start, string $end): Rental
    {
        return Rental::forceCreate([
            'user_id' => $user->id,
            'garage_id' => $garage->id,
            'vehicle_category' => 'bicycle',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'rental_type' => 'daily',
            'months' => 0,
            'days' => 1,
            'starts_on' => $start,
            'ends_on' => $end,
            'daily_rate' => $garage->daily_rate,
            'rental_amount' => $garage->daily_rate,
            'initial_total' => $garage->daily_rate,
            'proof_path' => 'bukti-sewa.png',
        ]);
    }
}

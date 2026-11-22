<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RentalBookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pastikan sewa mendatang menyimpan persen baterai dan unit baru terpakai saat periodenya mulai.
     */
    public function testFutureBookingStoresRemainingPercentAndOnlyOccupiesTheUnitOnItsStartDate(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $garage = Garage::create(['code' => 'E001', 'type' => 'E', 'daily_rate' => 75000, 'kwh_rate' => 2000]);
        $details = [
            'vehicle_category' => 'electric_car', 'plate' => 'B 1234 ABC', 'battery_kwh' => '60',
            'remaining_percent' => '30', 'starts_on' => '2026-11-28', 'days' => 3,
        ];

        $plan = [...$details, 'brand' => 'Merek uji', 'vehicle_model' => 'Model uji', 'rental_type' => 'daily'];
        $this->actingAs($user)->post(route('rentals.preview'), [...$plan, 'garage_type' => 'E', 'garage_id' => $garage->id, 'remaining_percent' => null, 'remaining_kwh' => '18'])
            ->assertSessionHasErrors('remaining_percent');
        $this->post(route('rentals.preview'), [...$plan, 'garage_type' => 'E', 'garage_id' => $garage->id, 'remaining_percent' => '101'])
            ->assertSessionHasErrors('remaining_percent');

        $this->post(route('rentals.preview'), [...$plan, 'garage_type' => 'E', 'garage_id' => $garage->id, 'battery_kwh' => '60.001'])
            ->assertSessionHasErrors('battery_kwh');

        $quoteId = $this->requestQuote($garage, $details, 'Rp 309.000');
        $this->post(route('rentals.store'), ['quote_id' => $quoteId, 'proof' => $this->proofImage()])
            ->assertRedirect(route('rentals.index'));

        $this->assertDatabaseHas('rentals', [
            'garage_id' => $garage->id, 'starts_on' => '2026-11-28', 'ends_on' => '2026-11-30',
            'remaining_kwh' => null, 'remaining_percent' => 30, 'energy_kwh' => 42, 'initial_total' => 309000,
        ]);
        $this->assertSame('available', $garage->refresh()->status);

        $this->get(route('rentals.index'))->assertOk();

        $search = [...$plan, 'garage_type' => 'E'];
        $this->post(route('rentals.available'), $search)->assertOk()->assertDontSee('E001');
        $this->post(route('rentals.available'), [...$search, 'starts_on' => '2026-11-22'])
            ->assertOk()->assertSee('E001');

        $this->travelTo(now()->setDate(2026, 11, 28)->startOfDay());
        $this->post(route('rentals.available'), $search)->assertOk()->assertDontSee('E001');
        $this->assertSame('used', $garage->refresh()->status);
    }

    /**
     * Periksa pencatatan sewa listrik, biaya, serta penyimpanan gambar bukti bayar.
     */
    public function testRenterCanBookAnElectricGarageWithPaymentProof(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $garage = Garage::create(['code' => 'E001', 'type' => 'E', 'daily_rate' => 50000, 'kwh_rate' => 2000]);

        $quoteId = $this->actingAs($user)->requestQuote($garage, [
            'vehicle_category' => 'electric_car',
            'plate' => 'B 1234 ABC',
            'battery_kwh' => '60',
            'remaining_percent' => '30',
            'rental_type' => 'monthly',
            'months' => 1,
            'days' => 3,
        ], 'Rp 1.484.000');

        $this->post(route('rentals.store'), [
            'quote_id' => $quoteId,
            'proof' => $this->proofImage(),
            'initial_total' => 1,
        ])->assertRedirect(route('rentals.index'))->assertSessionHas('status');

        $this->assertDatabaseHas('rentals', [
            'user_id' => $user->id,
            'garage_id' => $garage->id,
            'starts_on' => '2026-11-21',
            'ends_on' => '2026-12-23',
            'daily_rate' => 50000,
            'kwh_rate' => 2000,
            'energy_kwh' => 42,
            'rental_amount' => 1400000,
            'electricity_amount' => 84000,
            'initial_total' => 1484000,
        ]);
        $rental = Rental::sole();
        Storage::disk('local')->assertExists($rental->proof_path);
        $this->assertSame('used', $garage->refresh()->status);
        $this->get(route('rentals.show', $rental))->assertOk()->assertSee('42,00 kWh')->assertSee('Rp 84.000');
    }

    /**
     * Pastikan bukti tidak sah ditolak dan perubahan tarif memerlukan persetujuan atas nominal baru.
     */
    public function testInvalidProofAndAChangedRateRequireConfirmationAgain(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $garage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 10000]);
        $quoteId = $this->actingAs($user)->requestQuote($garage, [], 'Rp 10.000');

        $this->post(route('rentals.store'), [
            'quote_id' => $quoteId,
            'proof' => UploadedFile::fake()->createWithContent('bukti.txt', 'bukan gambar'),
        ])->assertRedirect(route('rentals.preview'))->assertSessionHasErrors('proof');

        $garage->update(['daily_rate' => 12000]);
        $this->post(route('rentals.store'), [
            'quote_id' => $quoteId,
            'proof' => $this->proofImage(),
        ])->assertRedirect(route('rentals.preview'))->assertSessionHasErrors('quote');

        $this->assertDatabaseCount('rentals', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('rental-proofs'));
        $preview = $this->get(route('rentals.preview'))->assertOk()->assertSee('Rp 12.000');
        $newQuoteId = $preview->viewData('quoteId');
        $this->assertNotSame($quoteId, $newQuoteId);

        $this->post(route('rentals.store'), [
            'quote_id' => $newQuoteId,
            'proof' => $this->proofImage(),
        ])->assertRedirect(route('rentals.index'));
        $this->assertDatabaseHas('rentals', ['initial_total' => 12000]);
    }

    /**
     * Pastikan konfirmasi dari pratinjau lama tidak menimpa pemesanan yang lebih dulu tersimpan.
     */
    public function testRenterCannotStoreAQuoteIfAnotherBookingTookTheUnit(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $otherUser = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $garage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 10000]);
        $quoteId = $this->actingAs($user)->requestQuote($garage, [], 'Rp 10.000');
        $firstQuote = session('rental_quote');

        $otherQuoteId = $this->actingAs($otherUser)->requestQuote($garage, [], 'Rp 10.000');
        $this->post(route('rentals.store'), [
            'quote_id' => $otherQuoteId,
            'proof' => $this->proofImage(),
        ])->assertRedirect(route('rentals.index'));

        $this->actingAs($user)->withSession(['rental_quote' => $firstQuote])
            ->post(route('rentals.store'), ['quote_id' => $quoteId, 'proof' => $this->proofImage()])
            ->assertRedirect(route('rentals.preview'))->assertSessionHasErrors('garage_id');

        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseHas('rentals', ['user_id' => $otherUser->id]);
        $this->assertCount(1, Storage::disk('local')->allFiles('rental-proofs'));
    }

    /**
     * Uji perbedaan biaya listrik antara garasi biasa dan listrik serta pembulatan totalnya.
     */
    public function testElectricityIsChargedOnlyForEInWholeRupiah(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $regular = Garage::create(['code' => 'C001', 'type' => 'C', 'daily_rate' => 50]);
        $electric = Garage::create(['code' => 'E001', 'type' => 'E', 'daily_rate' => 50, 'kwh_rate' => 1]);
        $electricCar = [
            'vehicle_category' => 'electric_car',
            'plate' => 'B 1234 ABC',
            'battery_kwh' => '1',
            'remaining_percent' => '50',
        ];

        $this->actingAs($user)->requestQuote($regular, $electricCar, 'Rp 50');
        $this->requestQuote($electric, $electricCar, 'Rp 51');
    }

    /**
     * Peroleh ID konfirmasi melalui pratinjau HTTP dan periksa total sebelum pengujian penyimpanan.
     */
    private function requestQuote(Garage $garage, array $details, string $total): string
    {
        $this->post(route('rentals.preview'), array_replace([
            'vehicle_category' => 'bicycle',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'rental_type' => 'daily',
            'days' => 1,
            'garage_type' => $garage->type,
            'garage_id' => $garage->id,
        ], $details))->assertRedirect(route('rentals.preview'));

        return $this->get(route('rentals.preview'))->assertOk()->assertSee($total)->viewData('quoteId');
    }

    /**
     * Sediakan berkas PNG yang valid agar validasi bukti membaca isi gambar sungguhan.
     */
    private function proofImage(): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aK0sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent('bukti.png', $png);
    }
}

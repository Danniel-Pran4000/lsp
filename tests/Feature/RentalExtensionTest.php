<?php

namespace Tests\Feature;

use App\Models\Extension;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RentalExtensionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bedakan daftar aktif hari ini dari daftar admin yang mencakup seluruh periode.
     */
    public function testAdminSeesOnlyRentalsActiveToday(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $active = $this->rental(['ends_on' => '2026-11-21']);
        $expired = $this->rental(['ends_on' => '2026-11-20']);
        $future = $this->rental(['starts_on' => '2026-11-25', 'ends_on' => '2026-11-26']);

        $this->actingAs($admin)->get(route('extensions.create'))
            ->assertOk()
            ->assertSee(route('extensions.preview', $active))
            ->assertDontSee(route('extensions.preview', $expired))
            ->assertDontSee(route('extensions.preview', $future));
        $this->get(route('admin.rentals'))
            ->assertSee(route('extensions.preview', $active))
            ->assertSee(route('extensions.preview', $expired))
            ->assertSee(route('extensions.preview', $future));
    }

    /**
     * Pastikan transaksi lampau dan mendatang dapat diperpanjang melalui alur admin.
     */
    public function testAdminCanExtendFinishedAndUpcomingRentals(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $finished = $this->rental(['ends_on' => '2026-11-20']);
        $upcoming = $this->rental(['starts_on' => '2026-11-25', 'ends_on' => '2026-11-26']);

        $this->actingAs($admin);
        foreach ([[$finished, '2026-11-22'], [$upcoming, '2026-11-28']] as [$rental, $newEnd]) {
            $quote = $this->get(route('extensions.preview', ['rental' => $rental, 'ends_on' => $newEnd]))
                ->assertOk()->assertSee('Rp 80.000')->viewData('quoteId');
            $this->post(route('extensions.store', $rental), ['quote_id' => $quote, 'proof' => $this->proofImage()])
                ->assertRedirect(route('admin.rentals'));
            $this->assertSame($newEnd, $rental->refresh()->ends_on);
        }

        $this->assertSame('used', $finished->garage->status);
        $this->assertSame('available', $upcoming->garage->status);
    }

    /**
     * Periksa tarif awal sewa bulanan, riwayat tambahan, dan akses foto untuk admin serta pemilik.
     */
    public function testAdminExtendsAMonthlyElectricRentalUsingItsOriginalDailyRate(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $garage = Garage::create(['code' => 'E001', 'type' => 'E', 'daily_rate' => 50000, 'kwh_rate' => 2500]);
        $rental = $this->rental([
            'garage_id' => $garage->id,
            'vehicle_category' => 'electric_car',
            'rental_type' => 'monthly',
            'months' => 1,
            'daily_rate' => 40000,
            'kwh_rate' => 2000,
            'initial_total' => 1084000,
        ]);

        $url = route('extensions.preview', ['rental' => $rental, 'ends_on' => '2026-11-28']);
        $quote = $this->actingAs($admin)->get($url)
            ->assertOk()->assertSee('Bulanan')->assertSee('3 hari')->assertSee('Rp 120.000')
            ->viewData('quoteId');

        $this->post(route('extensions.store', $rental), ['quote_id' => $quote])
            ->assertRedirect($url)->assertSessionHasErrors('proof');
        $this->post(route('extensions.store', $rental), [
            'quote_id' => $quote, 'proof' => $this->proofImage(), 'amount' => 1,
        ])->assertRedirect(route('admin.rentals'))
            ->assertSessionHas('status', 'Sewa Merek uji Model uji berhasil diperpanjang. Unit E001.');

        $this->assertDatabaseHas('extensions', [
            'rental_id' => $rental->id, 'admin_id' => $admin->id, 'extended_on' => '2026-11-21',
            'old_end' => '2026-11-25', 'new_end' => '2026-11-28', 'amount' => 120000,
        ]);
        $this->assertDatabaseHas('rentals', [
            'id' => $rental->id, 'ends_on' => '2026-11-28', 'rental_type' => 'monthly',
            'initial_total' => 1084000, 'proof_path' => 'initial.png',
        ]);
        $extension = Extension::sole();
        Storage::disk('local')->assertExists($extension->proof_path);
        Storage::disk('local')->put('initial.png', $this->proofImage()->get());
        foreach ([$admin, $rental->user] as $viewer) {
            $this->actingAs($viewer)->get(route('rentals.show', $rental))
                ->assertOk()->assertViewIs('user.detail-rental')->assertSee('Rp 1.204.000');
            $this->get(route('rentals.proof', $rental))->assertOk()->assertHeader('Content-Type', 'image/png');
            $this->get(route('rentals.proof', [$rental, $extension]))->assertOk()->assertHeader('Content-Type', 'image/png');
        }

        $this->actingAs(User::factory()->create());
        $this->get(route('rentals.proof', $rental))->assertNotFound();
        $this->get(route('rentals.proof', [$rental, $extension]))->assertNotFound();
    }

    /**
     * Pastikan akhir baru melewati akhir lama dan tambahan periode bebas dari sewa lain.
     */
    public function testNewEndMustBeLaterAndCannotOverlapAnotherRental(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $rental = $this->rental();
        $baseUrl = route('extensions.preview', $rental);
        $this->actingAs($admin)->get($baseUrl.'?ends_on=2026-11-25')
            ->assertRedirect($baseUrl)->assertSessionHasErrors('ends_on');

        $quote = $this->get($baseUrl.'?ends_on=2026-11-28')->assertOk()->viewData('quoteId');
        $this->rental(['garage_id' => $rental->garage_id, 'starts_on' => '2026-11-28', 'ends_on' => '2026-11-29']);

        $this->get($baseUrl.'?ends_on=2026-11-28')
            ->assertRedirect($baseUrl)->assertSessionHasErrors('ends_on');
        $this->post(route('extensions.store', $rental), ['quote_id' => $quote, 'proof' => $this->proofImage()])
            ->assertRedirect($baseUrl)->assertSessionHasErrors('ends_on');

        $this->assertSame('2026-11-25', $rental->refresh()->ends_on);
        $this->assertDatabaseCount('extensions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('extension-proofs'));
    }

    /**
     * Pastikan admin meninjau ulang tagihan jika transaksi berubah sesudah pratinjau.
     */
    public function testStaleConfirmationRequiresAnotherPreview(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $rental = $this->rental();
        $url = route('extensions.preview', $rental);
        $quote = $this->actingAs($admin)->get($url.'?ends_on=2026-11-28')->assertOk()->viewData('quoteId');
        $rental->ends_on = '2026-11-26';
        $rental->save();

        $this->post(route('extensions.store', $rental), ['quote_id' => $quote, 'proof' => $this->proofImage()])
            ->assertRedirect($url)
            ->assertSessionHasErrors(['ends_on' => 'Tanggal selesai berubah. Hitung ulang biaya perpanjangan.']);
        $this->get($url.'?ends_on=2026-11-28')->assertOk()->assertSee('Rp 80.000');
        $this->assertDatabaseCount('extensions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('extension-proofs'));
    }

    /**
     * Periksa pembatasan peran pada halaman dan konfirmasi perpanjangan.
     */
    public function testOnlyAdminCanAccessExtensionRoutes(): void
    {
        $rental = $this->rental();
        $this->get(route('extensions.create'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'user', 'approval_status' => 'approved']));
        $this->get(route('extensions.create'))->assertForbidden();
        $this->get(route('extensions.preview', $rental))->assertForbidden();
        $this->post(route('extensions.store', $rental))->assertForbidden();
    }

    /**
     * Buat transaksi awal dengan periode dan tarif terkontrol untuk skenario perpanjangan.
     */
    private function rental(array $attributes = []): Rental
    {
        $renter = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $garageId = $attributes['garage_id'] ?? Garage::create([
            'code' => 'S'.str_pad((string) (Garage::count() + 1), 3, '0', STR_PAD_LEFT),
            'type' => 'S', 'daily_rate' => 40000,
        ])->id;

        $rental = new Rental;
        $rental->forceFill(array_replace([
            'user_id' => $renter->id, 'garage_id' => $garageId, 'vehicle_category' => 'bicycle',
            'brand' => 'Merek uji', 'vehicle_model' => 'Model uji', 'rental_type' => 'daily',
            'months' => 0, 'days' => 7, 'starts_on' => '2026-11-19', 'ends_on' => '2026-11-25',
            'daily_rate' => 40000, 'rental_amount' => 280000, 'initial_total' => 280000,
            'proof_path' => 'initial.png',
        ], $attributes))->save();

        return $rental;
    }

    /**
     * Sediakan gambar PNG valid sebagai bukti pembayaran tambahan.
     */
    private function proofImage(): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aK0sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent('bukti.png', $png);
    }
}

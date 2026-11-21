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

    public function test_future_booking_stores_remaining_percent_and_only_occupies_the_unit_on_its_start_date(): void
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
        $this->actingAs($user)->post(route('rentals.create'), [...$plan, 'remaining_percent' => null, 'remaining_kwh' => '18'])
            ->assertSessionHasErrors('remaining_percent');
        $this->post(route('rentals.create'), [...$plan, 'remaining_percent' => '101'])
            ->assertSessionHasErrors('remaining_percent');

        $quoteId = $this->requestQuote($garage, $details, 'Rp 309.000');
        $this->post(route('rentals.store'), ['quote_id' => $quoteId, 'proof' => $this->proofImage()])
            ->assertRedirect(route('account.show'));

        $this->assertDatabaseHas('rentals', [
            'garage_id' => $garage->id, 'starts_on' => '2026-11-28', 'ends_on' => '2026-11-30',
            'remaining_kwh' => null, 'remaining_percent' => 30, 'energy_kwh' => 42, 'initial_total' => 309000,
        ]);
        $this->assertSame('available', $garage->refresh()->status);

        $search = [...$plan, 'garage_type' => 'E'];
        $this->post(route('rentals.available'), $search)->assertOk()->assertDontSee('E001');
        $this->post(route('rentals.available'), [...$search, 'starts_on' => '2026-11-22'])
            ->assertOk()->assertSee('E001');

        $this->travelTo(now()->setDate(2026, 11, 28)->startOfDay());
        $this->post(route('rentals.available'), $search)->assertOk()->assertDontSee('E001');
        $this->assertSame('used', $garage->refresh()->status);
    }

    public function test_renter_can_book_an_electric_garage_with_payment_proof(): void
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
        ])->assertRedirect(route('account.show'))->assertSessionHas('status');

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
        Storage::disk('local')->assertExists(Rental::sole()->proof_path);
        $this->assertSame('used', $garage->refresh()->status);
    }

    public function test_invalid_proof_and_a_changed_rate_require_confirmation_again(): void
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
        ])->assertRedirect(route('account.show'));
        $this->assertDatabaseHas('rentals', ['initial_total' => 12000]);
    }

    public function test_renter_cannot_store_a_quote_if_another_booking_took_the_unit(): void
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
        ])->assertRedirect(route('account.show'));

        $this->actingAs($user)->withSession(['rental_quote' => $firstQuote])
            ->post(route('rentals.store'), ['quote_id' => $quoteId, 'proof' => $this->proofImage()])
            ->assertRedirect(route('rentals.preview'))->assertSessionHasErrors('garage_id');

        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseHas('rentals', ['user_id' => $otherUser->id]);
        $this->assertCount(1, Storage::disk('local')->allFiles('rental-proofs'));
    }

    public function test_electricity_is_charged_only_for_e_and_total_is_rounded_once(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $regular = Garage::create(['code' => 'C001', 'type' => 'C', 'daily_rate' => '50.49']);
        $electric = Garage::create(['code' => 'E001', 'type' => 'E', 'daily_rate' => '50.49', 'kwh_rate' => '0.25']);
        $electricCar = [
            'vehicle_category' => 'electric_car',
            'plate' => 'B 1234 ABC',
            'battery_kwh' => '1',
            'remaining_percent' => '0',
        ];

        $this->actingAs($user)->requestQuote($regular, $electricCar, 'Rp 50');
        $this->requestQuote($electric, $electricCar, 'Rp 51');
    }

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

    private function proofImage(): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aK0sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent('bukti.png', $png);
    }
}

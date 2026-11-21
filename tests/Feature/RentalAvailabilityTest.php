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

    public function test_search_filters_by_type_and_checks_both_ends_of_the_period(): void
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
    }

    public function test_search_offers_a_compatible_type_and_explains_empty_results(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);

        $this->actingAs($user)->post(route('rentals.create'), $this->searchInput())
            ->assertOk()->assertSee('Cari unit');
        $this->post(route('rentals.available'), $this->searchInput())
            ->assertOk()->assertSee('Tidak ada unit Sepeda (S) yang tersedia selama periode ini.');
        $this->post(route('rentals.available'), array_replace($this->searchInput(), ['garage_type' => 'C']))
            ->assertRedirect(route('rentals.create'))->assertSessionHasErrors('garage_type');
    }

    public function test_pending_renter_cannot_search_for_units(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'pending']);

        $this->actingAs($user)->post(route('rentals.available'), $this->searchInput())->assertForbidden();
    }

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

<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalListTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_and_counts_only_include_the_signed_in_renters_records_across_pages(): void
    {
        $this->freezeTime();
        $renter = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $otherRenter = User::factory()->create();
        $firstGarage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 50000]);
        $secondGarage = Garage::create(['code' => 'S002', 'type' => 'S', 'daily_rate' => 50000]);
        $otherGarage = Garage::create(['code' => 'S003', 'type' => 'S', 'daily_rate' => 50000]);

        $past = today()->subDay()->toDateString();
        $this->rental($renter, $secondGarage, $past, $past);
        $this->rental($otherRenter, $otherGarage, $past, $past);
        $this->rental($renter, $firstGarage, today()->toDateString(), today()->toDateString());
        for ($day = 1; $day <= 19; $day++) {
            $date = today()->addDays($day)->toDateString();
            $this->rental($renter, $firstGarage, $date, $date);
        }

        $this->actingAs($renter)->get(route('account.show'))->assertSee(route('rentals.index'));
        $this->get(route('rentals.index', ['user_id' => $otherRenter->id]))
            ->assertOk()->assertSee('21 rentals')->assertSee('2 distinct garage units')
            ->assertSee('S001')->assertDontSee('S002')->assertDontSee('S003');
        $this->get(route('rentals.index', ['page' => 2]))
            ->assertOk()->assertSee('21 rentals')->assertSee('2 distinct garage units')
            ->assertSee('S002')->assertDontSee('S003')->assertSee(today()->subDay()->format('d/m/Y'));
    }

    public function test_history_requires_a_renter_account_and_remains_available_after_rejection(): void
    {
        $this->get(route('rentals.index'))->assertRedirect(route('login'));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('rentals.index'))->assertForbidden();

        $renter = User::factory()->create(['role' => 'user', 'approval_status' => 'pending']);
        $this->actingAs($renter)->get(route('rentals.index'))
            ->assertOk()->assertSee('0 rentals')->assertSee('No rentals found');

        $garage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 50000]);
        $this->rental($renter, $garage, '2026-11-21', '2026-11-24');
        $renter->forceFill(['approval_status' => 'rejected'])->save();

        $this->get(route('rentals.index'))->assertOk()->assertSee('S001')->assertSee('24/11/2026');
        $this->get(route('rentals.create'))->assertForbidden();
    }

    private function rental(User $user, Garage $garage, string $start, string $end): void
    {
        Rental::forceCreate([
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

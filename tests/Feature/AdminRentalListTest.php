<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRentalListTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_view_all_rental_periods(): void
    {
        $this->get(route('admin.rentals'))->assertRedirect(route('login'));
        $renter = User::factory()->create(['role' => 'user']);
        $this->actingAs($renter)->get(route('admin.rentals'))->assertForbidden();

        $garage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 50000]);
        foreach (['2020-01-01', '2090-01-01'] as $date) {
            Rental::forceCreate([
                'user_id' => $renter->id, 'garage_id' => $garage->id, 'vehicle_category' => 'bicycle',
                'brand' => 'Bike', 'vehicle_model' => 'Model', 'rental_type' => 'daily',
                'months' => 0, 'days' => 1, 'starts_on' => $date, 'ends_on' => $date,
                'daily_rate' => 50000, 'rental_amount' => 50000, 'initial_total' => 50000,
                'proof_path' => 'proof.png',
            ]);
        }
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.rentals'))
            ->assertOk()->assertSee('2020-01-01')->assertSee('2090-01-01')->assertSee('Bike');
    }
}

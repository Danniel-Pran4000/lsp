<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_units_with_independent_sequences_and_unit_rates(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => null]);
        $this->actingAs($admin)->get(route('garages.create'))->assertOk();

        $this->post(route('garages.store-batch'), [
            'type' => 'S',
            'quantity' => 2,
            'daily_rate' => '10000',
            'kwh_rate' => '2000',
        ])->assertRedirect(route('admin.rentals'))
            ->assertSessionHas('status', 'Bicycle garage units created. Unit codes: S001, S002.');

        $this->get(route('admin.rentals'))->assertSee('S001, S002');
        $this->post(route('garages.store-batch'), [
            'type' => 'C',
            'quantity' => 1,
            'daily_rate' => '40000',
        ])->assertSessionHas('status', 'Petrol car garage units created. Unit codes: C001.');
        $this->post(route('garages.store-batch'), [
            'type' => 'E',
            'quantity' => 1,
            'daily_rate' => '50000',
            'kwh_rate' => '2000.25',
        ])->assertSessionHas('status', 'Electric car garage units created. Unit codes: E001.');
        $this->post(route('garages.store-batch'), [
            'type' => 'S',
            'quantity' => 1,
            'daily_rate' => '12000',
        ])->assertSessionHas('status', 'Bicycle garage units created. Unit codes: S003.');

        $this->assertDatabaseHas('garages', [
            'code' => 'S001', 'type' => 'S', 'daily_rate' => 10000,
            'kwh_rate' => null, 'status' => 'available',
        ]);
        $this->assertDatabaseHas('garages', ['code' => 'S003', 'daily_rate' => 12000]);
        $this->assertDatabaseHas('garages', [
            'code' => 'E001', 'type' => 'E', 'daily_rate' => 50000, 'kwh_rate' => 2000.25,
        ]);
        $this->assertDatabaseCount('garages', 5);
    }

    public function test_admin_cannot_create_a_partial_batch_when_codes_run_out(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => null]);
        Garage::create(['code' => 'C998', 'type' => 'C', 'daily_rate' => 40000]);

        $this->actingAs($admin)->from(route('garages.create'))->post(route('garages.store-batch'), [
            'type' => 'C',
            'quantity' => 2,
            'daily_rate' => 42000,
        ])->assertRedirect(route('garages.create'))
            ->assertSessionHasErrors(['quantity' => 'Remaining unit codes for garage type C: 1.']);
        $this->assertDatabaseCount('garages', 1);
        $this->assertDatabaseMissing('garages', ['code' => 'C999']);

        $this->post(route('garages.store-batch'), [
            'type' => 'C',
            'quantity' => 1,
            'daily_rate' => 42000,
        ])->assertSessionHas('status', 'Petrol car garage units created. Unit codes: C999.');
        $this->assertDatabaseCount('garages', 2);
    }

    public function test_invalid_quantity_and_rates_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => null]);
        $this->actingAs($admin)->post(route('garages.store-batch'), [
            'type' => 'E',
            'quantity' => 1000,
            'daily_rate' => '50000.001',
        ])->assertSessionHasErrors(['quantity', 'daily_rate', 'kwh_rate']);

        $this->assertDatabaseCount('garages', 0);
    }

    public function test_garage_creation_requires_an_admin(): void
    {
        $this->get(route('garages.create'))->assertRedirect(route('login'));
        $user = User::factory()->create(['approval_status' => 'approved']);
        $this->actingAs($user)->get(route('garages.create'))->assertForbidden();
        $this->post(route('garages.store-batch'), [
            'type' => 'S', 'quantity' => 1, 'daily_rate' => 10000,
        ])->assertForbidden();

        $this->assertDatabaseCount('garages', 0);
    }
}

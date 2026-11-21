<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_rental_type_updates_the_duration_fields_without_calculating(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);

        $this->actingAs($user)->followingRedirects()->post(route('rentals.create'), [
            'rental_type' => 'daily',
            'rental_type_selection' => 'monthly',
            'brand' => 'Sample brand',
            'days' => 3,
        ])->assertOk()->assertViewHas('isMonthly', true)->assertSee('Extra days')
            ->assertSee('value="Sample brand"', false)->assertDontSee('Find available units')
            ->assertDontSee('step="1" value="3"', false);

        $this->followingRedirects()->post(route('rentals.create'), [
            'rental_type' => 'monthly',
            'rental_type_selection' => 'daily',
        ])->assertOk()->assertViewHas('isMonthly', false)->assertDontSee('Extra days');
    }

    public function test_applying_a_vehicle_category_controls_the_fields_without_calculating(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $this->actingAs($user)->get(route('rentals.create'))
            ->assertViewHas('needsPlate', false)->assertViewHas('needsBattery', false);

        foreach ([
            'motorcycle' => false,
            'gas_car' => false,
            'electric_car' => true,
            'bicycle' => false,
        ] as $category => $needsBattery) {
            $this->followingRedirects()->post(route('rentals.create'), [
                'vehicle_selection' => $category,
                'brand' => 'Sample brand',
            ])->assertOk()->assertViewHas('needsPlate', $category !== 'bicycle')->assertViewHas('needsBattery', $needsBattery)
                ->assertSee('value="Sample brand"', false)->assertDontSee('Find available units');
        }
    }

    public function test_future_start_uses_its_own_month_for_the_daily_limit(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $payload = [
            'vehicle_category' => 'bicycle', 'brand' => 'Merek uji', 'vehicle_model' => 'Model uji',
            'rental_type' => 'daily', 'starts_on' => '2026-12-01', 'days' => 30,
        ];

        $this->actingAs($user)->post(route('rentals.create'), $payload)
            ->assertOk()->assertSee('30/12/2026');
        $this->post(route('rentals.create'), array_replace($payload, ['starts_on' => '2026-11-28']))
            ->assertSessionHasErrors('days');
        $this->post(route('rentals.create'), array_replace($payload, ['starts_on' => '2026-11-20', 'days' => 1]))
            ->assertSessionHasErrors('starts_on');
    }

    public function test_daily_limit_uses_the_start_month_and_end_date_is_inclusive(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 21)->startOfDay());
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $payload = [
            'vehicle_category' => 'bicycle',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'rental_type' => 'daily',
            'days' => 29,
        ];

        $this->actingAs($user)->get(route('rentals.create'))
            ->assertOk()->assertSee('value="2026-11-21"', false)
            ->assertDontSee('Find available units');
        $this->post(route('rentals.create'), $payload)
            ->assertOk()->assertSee('19/12/2026')->assertViewHas('garageTypes', ['S']);
        $this->post(route('rentals.create'), array_replace($payload, ['days' => 30]))
            ->assertRedirect(route('rentals.create'))->assertSessionHasErrors('days');

        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_monthly_rentals_cross_short_months_and_accept_extra_days(): void
    {
        $this->travelTo(now()->setDate(2028, 1, 31)->startOfDay());
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $payload = [
            'vehicle_category' => 'electric_car',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'plate' => 'B 1234 ABC',
            'battery_kwh' => '60',
            'remaining_percent' => '0',
            'rental_type' => 'monthly',
            'months' => 1,
        ];

        $this->actingAs($user)->post(route('rentals.create'), $payload)
            ->assertOk()->assertSee('29/02/2028')->assertViewHas('garageTypes', ['C', 'E'])
            ->assertViewHas('details.days', 0);
        $this->post(route('rentals.create'), [...$payload, 'days' => ''])
            ->assertOk()->assertSee('29/02/2028')->assertViewHas('details.days', 0);
        $this->post(route('rentals.create'), array_replace($payload, ['months' => 2, 'days' => 3]))
            ->assertOk()->assertSee('03/04/2028');
    }

    public function test_plate_and_battery_values_are_required_for_an_electric_car(): void
    {
        $user = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);

        $this->actingAs($user)->post(route('rentals.create'), [
            'vehicle_category' => 'electric_car',
            'brand' => 'Merek uji',
            'vehicle_model' => 'Model uji',
            'rental_type' => 'daily',
            'days' => 1,
        ])->assertSessionHasErrors(['plate', 'battery_kwh', 'remaining_percent']);
    }

    public function test_only_approved_renters_can_open_or_submit_the_form(): void
    {
        $this->get(route('rentals.create'))->assertRedirect(route('login'));

        foreach ([
            ['role' => 'user', 'approval_status' => 'pending'],
            ['role' => 'user', 'approval_status' => 'rejected'],
            ['role' => 'admin', 'approval_status' => 'approved'],
        ] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->get(route('rentals.create'))->assertForbidden();
            $this->post(route('rentals.create'))->assertForbidden();
        }
    }
}

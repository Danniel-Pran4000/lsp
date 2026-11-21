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

    public function test_admin_sees_only_rentals_active_today(): void
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
        $this->get(route('extensions.preview', $expired))
            ->assertRedirect(route('extensions.create'))->assertSessionHasErrors('rental');
    }

    public function test_admin_extends_a_monthly_electric_rental_using_its_original_daily_rate(): void
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
            ->assertOk()->assertSee('Monthly')->assertSee('3 days')->assertSee('IDR 120.000')
            ->viewData('quoteId');

        $this->post(route('extensions.store', $rental), ['quote_id' => $quote])
            ->assertRedirect($url)->assertSessionHasErrors('proof');
        $this->post(route('extensions.store', $rental), [
            'quote_id' => $quote, 'proof' => $this->proofImage(), 'amount' => 1,
        ])->assertRedirect(route('extensions.create'))->assertSessionHas('status');

        $this->assertDatabaseHas('extensions', [
            'rental_id' => $rental->id, 'admin_id' => $admin->id, 'extended_on' => '2026-11-21',
            'old_end' => '2026-11-25', 'new_end' => '2026-11-28', 'amount' => 120000,
        ]);
        $this->assertDatabaseHas('rentals', [
            'id' => $rental->id, 'ends_on' => '2026-11-28', 'rental_type' => 'monthly',
            'initial_total' => 1084000, 'proof_path' => 'initial.png',
        ]);
        Storage::disk('local')->assertExists(Extension::sole()->proof_path);
    }

    public function test_new_end_must_be_later_and_cannot_overlap_another_rental(): void
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

    public function test_stale_confirmation_requires_another_preview(): void
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
            ->assertSessionHasErrors(['ends_on' => 'The end date has changed. Recalculate the extension charge.']);
        $this->get($url.'?ends_on=2026-11-28')->assertOk()->assertSee('IDR 80.000');
        $this->assertDatabaseCount('extensions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('extension-proofs'));
    }

    public function test_only_admin_can_access_extension_routes(): void
    {
        $rental = $this->rental();
        $this->get(route('extensions.create'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'user', 'approval_status' => 'approved']));
        $this->get(route('extensions.create'))->assertForbidden();
        $this->get(route('extensions.preview', $rental))->assertForbidden();
        $this->post(route('extensions.store', $rental))->assertForbidden();
    }

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

    private function proofImage(): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aK0sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent('bukti.png', $png);
    }
}

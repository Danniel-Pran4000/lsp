<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageBatchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Periksa urutan kode independen dan tarif tersimpan untuk setiap tipe garasi.
     */
    public function testAdminCreatesUnitsWithIndependentSequencesAndUnitRates(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => null]);
        $this->actingAs($admin)->get(route('garages.create'))->assertOk();

        $this->post(route('garages.store-batch'), [
            'type' => 'S',
            'quantity' => 2,
            'daily_rate' => '10000',
            'kwh_rate' => '2000',
        ])->assertRedirect(route('garages.index'))
            ->assertSessionHas('status', '2 unit berhasil ditambahkan. Kode unit S001, S002.');

        $this->get(route('garages.index'))->assertSee('S001, S002');
        $this->post(route('garages.store-batch'), [
            'type' => 'C',
            'quantity' => 1,
            'daily_rate' => '40000',
        ])->assertSessionHas('status', '1 unit berhasil ditambahkan. Kode unit C001.');
        $this->post(route('garages.store-batch'), [
            'type' => 'E',
            'quantity' => 1,
            'daily_rate' => '50000',
            'kwh_rate' => '2000.25',
        ])->assertSessionHas('status', '1 unit berhasil ditambahkan. Kode unit E001.');
        $this->post(route('garages.store-batch'), [
            'type' => 'S',
            'quantity' => 1,
            'daily_rate' => '12000',
        ])->assertSessionHas('status', '1 unit berhasil ditambahkan. Kode unit S003.');

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

    /**
     * Pastikan kekurangan kapasitas membatalkan seluruh permintaan penambahan unit.
     */
    public function testAdminCannotCreateAPartialBatchWhenCodesRunOut(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => null]);
        Garage::create(['code' => 'C998', 'type' => 'C', 'daily_rate' => 40000]);

        $this->actingAs($admin)->from(route('garages.create'))->post(route('garages.store-batch'), [
            'type' => 'C',
            'quantity' => 2,
            'daily_rate' => 42000,
        ])->assertRedirect(route('garages.create'))
            ->assertSessionHasErrors(['quantity' => 'Sisa kapasitas tipe garasi C adalah 1 unit.']);
        $this->assertDatabaseCount('garages', 1);
        $this->assertDatabaseMissing('garages', ['code' => 'C999']);

        $this->post(route('garages.store-batch'), [
            'type' => 'C',
            'quantity' => 1,
            'daily_rate' => 42000,
        ])->assertSessionHas('status', '1 unit berhasil ditambahkan. Kode unit C999.');
        $this->assertDatabaseCount('garages', 2);
    }

    /**
     * Pastikan jumlah atau tarif yang salah tidak menghasilkan unit.
     */
    public function testInvalidQuantityAndRatesAreRejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => null]);
        $this->actingAs($admin)->post(route('garages.store-batch'), [
            'type' => 'E',
            'quantity' => 1000,
            'daily_rate' => '50000.001',
        ])->assertSessionHasErrors(['quantity', 'daily_rate', 'kwh_rate']);

        $this->assertDatabaseCount('garages', 0);
    }

    /**
     * Periksa pembatasan akses saat melihat formulir maupun menyimpan unit.
     */
    public function testGarageCreationRequiresAnAdmin(): void
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

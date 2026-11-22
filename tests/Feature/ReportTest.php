<?php

namespace Tests\Feature;

use App\Models\Extension;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pastikan sewa awal dihitung sekali dan pembayaran tambahan masuk bulan saat perpanjangan.
     */
    public function testMonthlyReportCountsEachRentalOnceAndGroupsPaymentsByTheirOwnDate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $renter = User::factory()->create(['role' => 'user']);
        $garage = Garage::create(['code' => 'E001', 'type' => 'E', 'daily_rate' => 75000, 'kwh_rate' => 2000]);
        $rental = $this->rental($renter, $garage, '2026-11-30', '2026-12-05', 309000);
        $this->payment($rental, $admin, '2026-12-01', 150000);
        $this->payment($rental, $admin, '2026-12-02', 75000);
        $this->rental($renter, $garage, '2025-11-01', '2025-11-03', 999000);

        $report = $this->actingAs($admin)->get(route('reports.index', ['mode' => 'monthly', 'year' => 2026]))
            ->assertOk()->assertSee('Januari')->assertSee('Desember')->assertSee('No.')->assertDontSee('Rental ID')
            ->assertSee('Rp 309.000')->assertSee('Rp 225.000')->assertDontSee('Rp 999.000');

        $this->assertCount(12, $report->viewData('groups'));
        $rentals = $report->viewData('rentalTotals');
        $extensions = $report->viewData('extensionTotals');
        $this->assertSame(1, (int) $rentals[11]->transaction_count);
        $this->assertSame('309000', (string) $rentals[11]->total);
        $this->assertArrayNotHasKey(12, $rentals->all());
        $this->assertSame(2, (int) $extensions[12]->transaction_count);
        $this->assertSame('225000', (string) $extensions[12]->total);
        $this->assertCount(1, $report->viewData('rentalDetails'));
        $this->assertCount(2, $report->viewData('extensionDetails'));
    }

    /**
     * Periksa laporan Februari kabisat hingga hari terakhir pada halaman kedua.
     */
    public function testDailyReportUsesTheSelectedMonthAndIncludesItsLastDay(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $renter = User::factory()->create(['role' => 'user']);
        $garage = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 10000]);
        $rental = $this->rental($renter, $garage, '2028-02-29', '2028-03-02', 30000);
        $this->payment($rental, $admin, '2028-02-29', 10000);
        $this->payment($rental, $admin, '2028-03-01', 80000);

        $report = $this->actingAs($admin)->get(route('reports.index', ['mode' => 'daily', 'year' => 2028, 'month' => 2]))
            ->assertOk()->assertSee('01 Februari')->assertDontSee('29 Februari')
            ->assertSee('Rp 30.000')->assertSee('Rp 10.000')->assertDontSee('Rp 80.000');

        $this->assertCount(15, $report->viewData('groups'));
        $this->assertSame(29, $report->viewData('groups')->total());
        $this->get(route('reports.index', ['mode' => 'daily', 'year' => 2028, 'month' => 2, 'summary_page' => 2]))
            ->assertOk()->assertSee('29 Februari')->assertDontSee('01 Februari');
        $this->assertSame('30000', (string) $report->viewData('rentalTotals')[29]->total);
        $this->assertSame('10000', (string) $report->viewData('extensionTotals')[29]->total);
    }

    /**
     * Pastikan filter tipe dan penyewa berlaku pada ringkasan serta kedua tabel rincian.
     */
    public function testTypeAndRenterFiltersApplyToSummaryAndBothDetailTables(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Uji']);
        $first = User::factory()->create(['role' => 'user', 'name' => 'Penyewa Satu']);
        $second = User::factory()->create(['role' => 'user', 'name' => 'Penyewa Dua']);
        $bike = Garage::create(['code' => 'S001', 'type' => 'S', 'daily_rate' => 10000]);
        $car = Garage::create(['code' => 'C001', 'type' => 'C', 'daily_rate' => 75000]);
        $firstRental = $this->rental($first, $bike, '2026-11-20', '2026-11-21', 20000);
        $secondRental = $this->rental($second, $car, '2026-11-22', '2026-11-23', 150000);
        $this->payment($firstRental, $admin, '2026-11-21', 10000);
        $this->payment($secondRental, $admin, '2026-11-23', 75000);

        $allTypes = $this->actingAs($admin)->get(route('reports.index', ['mode' => 'type']))->assertOk();
        $this->assertSame(['S', 'C', 'E'], array_keys($allTypes->viewData('groups')->items()));
        $this->assertSame('150000', (string) $allTypes->viewData('rentalTotals')['C']->total);

        $byType = $this->get(route('reports.index', ['mode' => 'type', 'garage_type' => 'S']))
            ->assertOk()->assertSee('S001')->assertDontSee('C001')->assertDontSee('Rp 75.000');
        $this->assertSame(['S'], array_keys($byType->viewData('groups')->items()));
        $this->assertCount(1, $byType->viewData('rentalDetails'));
        $this->assertCount(1, $byType->viewData('extensionDetails'));

        $allRenters = $this->get(route('reports.index', ['mode' => 'renter']))
            ->assertOk()->assertSee('Penyewa Satu')->assertSee('Penyewa Dua');
        $this->assertSame('75000', (string) $allRenters->viewData('extensionTotals')[$second->id]->total);

        $byRenter = $this->get(route('reports.index', ['mode' => 'renter', 'user_id' => $second->id]))
            ->assertOk()->assertSee('C001')->assertDontSee('S001');
        $this->assertSame([$second->id], array_keys($byRenter->viewData('groups')->items()));
        $this->assertCount(1, $byRenter->viewData('rentalDetails'));
        $this->assertCount(1, $byRenter->viewData('extensionDetails'));
    }

    /**
     * Periksa akses admin dan penolakan filter periode atau penyewa yang tidak sah.
     */
    public function testReportRequiresAdminAndValidFilters(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $renter = User::factory()->create(['role' => 'user', 'approval_status' => 'approved']);
        $this->actingAs($renter)->get(route('reports.index'))->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('reports.index', ['mode' => 'daily', 'month' => 13]))
            ->assertRedirect(route('reports.index'))->assertSessionHasErrors('month');
        $this->get(route('reports.index', ['mode' => 'renter', 'user_id' => $admin->id]))
            ->assertRedirect(route('reports.index'))->assertSessionHasErrors('user_id');
    }

    /**
     * Siapkan sewa bernominal tetap agar agregat laporan dapat dibandingkan dengan hasil yang diketahui.
     */
    private function rental(User $renter, Garage $garage, string $start, string $end, int $total): Rental
    {
        return Rental::forceCreate([
            'user_id' => $renter->id, 'garage_id' => $garage->id, 'vehicle_category' => 'bicycle',
            'brand' => 'Merek uji', 'vehicle_model' => 'Model uji', 'rental_type' => 'daily',
            'months' => 0, 'days' => 1, 'starts_on' => $start, 'ends_on' => $end,
            'daily_rate' => $garage->daily_rate, 'rental_amount' => $total, 'initial_total' => $total,
            'proof_path' => 'bukti-sewa.png',
        ]);
    }

    /**
     * Catat pembayaran tambahan pada tanggal pilihan untuk menguji pemisahan periode laporan.
     */
    private function payment(Rental $rental, User $admin, string $date, int $amount): void
    {
        Extension::create([
            'rental_id' => $rental->id, 'admin_id' => $admin->id, 'extended_on' => $date,
            'old_end' => $rental->ends_on, 'new_end' => '2028-03-05', 'amount' => $amount,
            'proof_path' => 'bukti-perpanjangan.png',
        ]);
    }
}

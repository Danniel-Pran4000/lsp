<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pastikan nol awal NIK tersimpan dan pendaftar baru menunggu persetujuan.
     */
    public function testRegistrationPreservesNikAndAssignsPendingUserRole(): void
    {
        $this->get(route('register'))->assertOk();
        $this->post(route('registration.store'), [
            'nik' => '0123456789012345',
            'name' => 'Calon Penyewa',
            'phone' => '081234567890',
            'email' => 'penyewa@example.test',
            'password' => 'sample-password',
            'role' => 'admin',
            'approval_status' => 'approved',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $user = User::sole();
        $this->assertSame('0123456789012345', $user->nik);
        $this->assertSame('user', $user->role);
        $this->assertSame('pending', $user->approval_status);
        $this->assertTrue(Hash::check('sample-password', $user->password));
        $this->assertFalse($user->canRent());
    }

    /**
     * Periksa agar NIK tidak sah dan email yang sudah dipakai ditolak saat pendaftaran.
     */
    public function testRegistrationRejectsInvalidNikAndADuplicateEmail(): void
    {
        $existingUser = User::factory()->create();
        $this->from(route('register'))->post(route('registration.store'), [
            'nik' => '012345678901234',
            'name' => 'Calon Penyewa',
            'phone' => '081234567890',
            'email' => $existingUser->email,
            'password' => 'sample-password',
        ])->assertRedirect(route('register'))->assertSessionHasErrors(['nik', 'email']);

        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Pastikan admin dapat melihat semua status dan membalik keputusan persetujuan.
     */
    public function testAdminCanListAllRentersAndChangeTheirStatusInEitherDirection(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => null]);
        [$first, $second, $third] = User::factory()->count(3)->create()->all();

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('registration.index'));
        $this->patch(route('registration.verify', $first), ['decision' => 'approved'])
            ->assertRedirect(route('registration.index'))->assertSessionHas('status', "Pendaftaran {$first->name} disetujui.");
        $this->patch(route('registration.verify', $second), ['decision' => 'rejected'])->assertRedirect();
        $this->assertTrue($first->refresh()->canRent());
        $this->assertFalse($second->refresh()->canRent());
        $this->get(route('registration.index'))->assertOk()
            ->assertSee($first->email)->assertSee($second->email)->assertSee($third->email)
            ->assertSee('Approved')->assertSee('Rejected')->assertSee('Pending')
            ->assertDontSee($admin->email);

        $this->patch(route('registration.verify', $first), ['decision' => 'rejected'])->assertRedirect();
        $this->patch(route('registration.verify', $second), ['decision' => 'approved'])->assertRedirect();
        $this->patch(route('registration.verify', $admin), ['decision' => 'rejected'])->assertNotFound();
        $this->assertDatabaseCount('users', 4);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($first->refresh())->get(route('rentals.create'))->assertForbidden();
        $this->actingAs($second->refresh())->get(route('rentals.create'))->assertOk();
    }

    /**
     * Periksa agar halaman admin hanya menerima akun berperan admin.
     */
    public function testAdminRoutesRequireAnAdminAccount(): void
    {
        $user = User::factory()->create();
        $this->get(route('registration.index'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('registration.index'))->assertForbidden();
        $this->patch(route('registration.verify', $user), ['decision' => 'approved'])->assertForbidden();
        $this->assertSame('pending', $user->refresh()->approval_status);
    }
}

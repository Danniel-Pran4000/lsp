<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Pastikan pengunjung tanpa sesi diarahkan untuk masuk.
     */
    public function testTheHomePageRedirectsGuestsToLogin(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    /**
     * Periksa bahwa halaman awal membuka tujuan yang sesuai untuk tiap peran.
     */
    public function testTheHomePageRedirectsSignedInUsersByRole(): void
    {
        $this->actingAs(User::factory()->make(['role' => 'admin']))
            ->get('/')->assertRedirect(route('registration.index'));

        $this->actingAs(User::factory()->make(['role' => 'user']))
            ->get('/')->assertRedirect(route('rentals.index'));
    }
}

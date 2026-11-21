<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_home_page_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_the_home_page_redirects_signed_in_users_by_role(): void
    {
        $this->actingAs(User::factory()->make(['role' => 'admin']))
            ->get('/')->assertRedirect(route('registration.index'));

        $this->actingAs(User::factory()->make(['role' => 'user']))
            ->get('/')->assertRedirect(route('rentals.index'));
    }
}

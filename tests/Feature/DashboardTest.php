<?php

namespace Tests\Feature;

use App\Enums\UserGender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_greeting_is_feminine_for_female_users(): void
    {
        $user = User::factory()->create(['gender' => UserGender::Female]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.greeting', 'Bienvenida'));
    }

    public function test_greeting_is_masculine_for_male_users(): void
    {
        $user = User::factory()->create(['gender' => UserGender::Male]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.greeting', 'Bienvenido'));
    }

    public function test_greeting_falls_back_when_gender_is_unset(): void
    {
        $user = User::factory()->create(['gender' => null]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.greeting', 'Bienvenido(a)'));
    }
}

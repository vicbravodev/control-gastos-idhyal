<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUserHasRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private const Uri = '/__feature-tests/role-middleware';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:contabilidad'])
            ->get(self::Uri, fn () => response()->noContent());
    }

    public function test_it_forbids_guests(): void
    {
        $this->get(self::Uri)->assertRedirect(route('login'));
    }

    public function test_it_forbids_users_without_matching_role(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->actingAs($user)->get(self::Uri)->assertForbidden();
    }

    public function test_it_allows_users_with_matching_role(): void
    {
        $user = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($user)->get(self::Uri)->assertNoContent();
    }

    public function test_it_allows_when_user_matches_any_slug_in_pipe_list(): void
    {
        $uri = self::Uri.'-multi';
        Route::middleware(['web', 'auth', 'role:asesor|contabilidad'])
            ->get($uri, fn () => response()->noContent());

        $asesor = User::factory()->forRole('asesor')->create();

        $this->actingAs($asesor)->get($uri)->assertNoContent();
    }
}

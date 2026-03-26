<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VacationRule;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VacationRuleHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('vacation-rules.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_index(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('vacation-rules.index'))
            ->assertForbidden();
    }

    public function test_contabilidad_can_view_index(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('contabilidad')->create();

        VacationRule::factory()->create(['code' => 'R1', 'sort_order' => 1]);

        $this->actingAs($user)
            ->get(route('vacation-rules.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('vacation-rules/index')
                ->has('rules', 1));
    }

    public function test_contabilidad_can_create_rule(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('contabilidad')->create();

        $this->actingAs($user)
            ->post(route('vacation-rules.store'), [
                'code' => 'NEW_RULE',
                'name' => 'Nueva regla',
                'min_years_service' => 1,
                'max_years_service' => null,
                'days_granted_per_year' => 12,
                'max_days_per_request' => null,
                'max_days_per_month' => null,
                'max_days_per_quarter' => null,
                'max_days_per_year' => null,
                'blackout_dates' => '',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('vacation-rules.index'));

        $this->assertDatabaseHas('vacation_rules', [
            'code' => 'NEW_RULE',
            'days_granted_per_year' => 12,
        ]);
    }

    public function test_contabilidad_can_update_rule(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('contabilidad')->create();
        $rule = VacationRule::factory()->create(['code' => 'OLD', 'name' => 'Old']);

        $this->actingAs($user)
            ->put(route('vacation-rules.update', $rule), [
                'code' => 'OLD',
                'name' => 'Renamed',
                'min_years_service' => 1,
                'max_years_service' => '',
                'days_granted_per_year' => 15,
                'max_days_per_request' => '',
                'max_days_per_month' => '',
                'max_days_per_quarter' => '',
                'max_days_per_year' => '',
                'blackout_dates' => '',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('vacation-rules.index'));

        $this->assertSame('Renamed', $rule->fresh()->name);
        $this->assertSame(15, $rule->fresh()->days_granted_per_year);
    }

    public function test_contabilidad_can_delete_rule(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('contabilidad')->create();
        $rule = VacationRule::factory()->create();

        $this->actingAs($user)
            ->delete(route('vacation-rules.destroy', $rule))
            ->assertRedirect(route('vacation-rules.index'));

        $this->assertModelMissing($rule);
    }
}

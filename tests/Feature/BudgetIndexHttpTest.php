<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BudgetIndexHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('budgets.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_budget_permission_cannot_view_index(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('budgets.index'))
            ->assertForbidden();
    }

    public function test_accounting_user_can_view_budgets_index(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('contabilidad')->create();

        Budget::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('budgets/index')
                ->has('budgets.data', 2)
                ->where('can.create', true));
    }

    public function test_budgets_index_search_filters_by_budgetable_name(): void
    {
        $this->seed(RoleSeeder::class);
        $viewer = User::factory()->forRole('contabilidad')->create();
        $matchUser = User::factory()->forRole('asesor')->create(['name' => 'ScopeLabel UniqueXYZ']);
        Budget::factory()->forBudgetable('user', $matchUser->id)->create();
        Budget::factory()->forBudgetable('user', User::factory()->forRole('asesor')->create(['name' => 'Someone Else'])->id)->create();

        $this->actingAs($viewer)
            ->get(route('budgets.index', ['search' => 'UniqueXYZ']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('budgets/index')
                ->has('budgets.data', 1)
                ->where('budgets.data.0.scope_label', 'ScopeLabel UniqueXYZ')
                ->where('filters.search', 'UniqueXYZ'));
    }
}

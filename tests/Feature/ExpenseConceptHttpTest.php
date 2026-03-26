<?php

namespace Tests\Feature;

use App\Models\ExpenseConcept;
use App\Models\ExpenseRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseConceptHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_expense_concepts_index(): void
    {
        $this->get(route('expense-concepts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_budget_permission_cannot_manage_concepts(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('asesor')->create();

        $this->actingAs($user)
            ->get(route('expense-concepts.index'))
            ->assertForbidden();
    }

    public function test_accounting_can_list_and_create_concept(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('contabilidad')->create();
        ExpenseConcept::factory()->create(['name' => 'Catálogo demo', 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('expense-concepts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-concepts/index')
                ->has('concepts', 2)
                ->has('filters')
                ->where('filters.search', '')
                ->where('filters.active', ''));

        $this->actingAs($user)
            ->post(route('expense-concepts.store'), [
                'name' => 'Nuevo desde prueba',
                'is_active' => true,
                'sort_order' => 5,
            ])
            ->assertRedirect(route('expense-concepts.index'));

        $this->assertDatabaseHas('expense_concepts', [
            'name' => 'Nuevo desde prueba',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $this->assertSame(3, ExpenseConcept::query()->count());
    }

    public function test_expense_concepts_index_filters_by_active_query(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('contabilidad')->create();
        ExpenseConcept::factory()->inactive()->create(['name' => 'Inactive Only']);

        $this->actingAs($user)
            ->get(route('expense-concepts.index', ['active' => '0']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('expense-concepts/index')
                ->has('concepts', 1)
                ->where('concepts.0.name', 'Inactive Only')
                ->where('filters.active', '0'));
    }

    public function test_concept_with_requests_cannot_be_deleted(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('contabilidad')->create();
        $concept = ExpenseConcept::factory()->create();
        ExpenseRequest::factory()->create(['expense_concept_id' => $concept->id]);

        $this->actingAs($user)
            ->delete(route('expense-concepts.destroy', $concept))
            ->assertForbidden();
    }

    public function test_unused_concept_can_be_deleted(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->forRole('contabilidad')->create();
        $concept = ExpenseConcept::factory()->create();

        $this->actingAs($user)
            ->delete(route('expense-concepts.destroy', $concept))
            ->assertRedirect(route('expense-concepts.index'));

        $this->assertDatabaseMissing('expense_concepts', ['id' => $concept->id]);
    }
}

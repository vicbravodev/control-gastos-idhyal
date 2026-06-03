<?php

namespace Tests\Feature;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\BudgetAudit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetManagementHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_can_store_budget_and_audit_is_recorded(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $target = User::factory()->create();

        $this->actingAs($accounting)
            ->post(route('budgets.store'), [
                'budgetable_type' => 'user',
                'budgetable_id' => $target->id,
                'period_starts_on' => '2026-01-01',
                'period_ends_on' => '2026-12-31',
                'amount_limit_cents' => 500_000,
                'priority' => 3,
            ])
            ->assertRedirect(route('budgets.index'));

        $budget = Budget::query()
            ->where('budgetable_type', 'user')
            ->where('budgetable_id', $target->id)
            ->firstOrFail();

        $this->assertSame(BudgetStatus::Active, $budget->status);
        $this->assertDatabaseHas('budget_audits', [
            'budget_id' => $budget->id,
            'event' => 'created',
            'actor_id' => $accounting->id,
        ]);
    }

    public function test_updating_amount_records_amount_changed_audit(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $target = User::factory()->create();
        $budget = Budget::factory()
            ->forBudgetable('user', $target->id)
            ->create(['amount_limit_cents' => 100_000]);

        $this->actingAs($accounting)
            ->put(route('budgets.update', $budget), [
                'budgetable_type' => 'user',
                'budgetable_id' => $target->id,
                'period_starts_on' => $budget->period_starts_on->toDateString(),
                'period_ends_on' => $budget->period_ends_on->toDateString(),
                'amount_limit_cents' => 250_000,
                'priority' => $budget->priority,
            ])
            ->assertRedirect(route('budgets.index'));

        $audit = BudgetAudit::query()
            ->where('budget_id', $budget->id)
            ->where('event', 'amount_changed')
            ->firstOrFail();

        $this->assertSame(100_000, $audit->changes['from']['amount_limit_cents']);
        $this->assertSame(250_000, $audit->changes['to']['amount_limit_cents']);
        $this->assertSame($accounting->id, $audit->actor_id);
    }

    public function test_updating_scope_records_scope_changed_audit(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $budget = Budget::factory()
            ->forBudgetable('user', $userA->id)
            ->create(['amount_limit_cents' => 100_000]);

        $this->actingAs($accounting)
            ->put(route('budgets.update', $budget), [
                'budgetable_type' => 'user',
                'budgetable_id' => $userB->id,
                'period_starts_on' => $budget->period_starts_on->toDateString(),
                'period_ends_on' => $budget->period_ends_on->toDateString(),
                'amount_limit_cents' => 100_000,
                'priority' => $budget->priority,
            ])
            ->assertRedirect(route('budgets.index'));

        $audit = BudgetAudit::query()
            ->where('budget_id', $budget->id)
            ->where('event', 'scope_changed')
            ->firstOrFail();

        $this->assertSame($userA->id, $audit->changes['from']['budgetable_id']);
        $this->assertSame($userB->id, $audit->changes['to']['budgetable_id']);
    }

    public function test_budget_destroy_route_does_not_exist(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $budget = Budget::factory()->create();

        $this->actingAs($accounting)
            ->delete('/budgets/'.$budget->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
    }

    public function test_can_cancel_active_budget_with_reason_and_records_audit(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $budget = Budget::factory()->create();

        $this->actingAs($accounting)
            ->post(route('budgets.cancel', $budget), [
                'reason' => 'Cierre del ejercicio.',
            ])
            ->assertRedirect(route('budgets.index'));

        $budget->refresh();
        $this->assertSame(BudgetStatus::Cancelled, $budget->status);
        $this->assertSame('Cierre del ejercicio.', $budget->cancellation_reason);
        $this->assertSame($accounting->id, $budget->cancelled_by);
        $this->assertNotNull($budget->cancelled_at);

        $this->assertDatabaseHas('budget_audits', [
            'budget_id' => $budget->id,
            'event' => 'status_changed',
            'actor_id' => $accounting->id,
        ]);
    }

    public function test_cancel_requires_reason(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $budget = Budget::factory()->create();

        $this->actingAs($accounting)
            ->post(route('budgets.cancel', $budget), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame(BudgetStatus::Active, $budget->fresh()->status);
    }

    public function test_cannot_cancel_already_cancelled_budget(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $budget = Budget::factory()->cancelled()->create();

        $this->actingAs($accounting)
            ->post(route('budgets.cancel', $budget), [
                'reason' => 'Intento duplicado.',
            ])
            ->assertForbidden();
    }

    public function test_cannot_update_cancelled_budget(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $budget = Budget::factory()->cancelled()->create([
            'amount_limit_cents' => 100_000,
        ]);

        $this->actingAs($accounting)
            ->put(route('budgets.update', $budget), [
                'budgetable_type' => 'user',
                'budgetable_id' => $budget->budgetable_id,
                'period_starts_on' => $budget->period_starts_on->toDateString(),
                'period_ends_on' => $budget->period_ends_on->toDateString(),
                'amount_limit_cents' => 999_999,
                'priority' => $budget->priority,
            ])
            ->assertForbidden();

        $this->assertSame(100_000, (int) $budget->fresh()->amount_limit_cents);
    }
}

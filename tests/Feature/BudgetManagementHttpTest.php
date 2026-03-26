<?php

namespace Tests\Feature;

use App\Enums\BudgetLedgerEntryType;
use App\Models\Budget;
use App\Models\BudgetLedgerEntry;
use App\Models\ExpenseRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetManagementHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_can_store_budget(): void
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

        $this->assertDatabaseHas('budgets', [
            'budgetable_type' => 'user',
            'budgetable_id' => $target->id,
            'amount_limit_cents' => 500_000,
            'priority' => 3,
        ]);
    }

    public function test_cannot_destroy_budget_with_ledger_entries(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $target = User::factory()->create();
        $budget = Budget::factory()->forBudgetable('user', $target->id)->create();

        $expense = ExpenseRequest::factory()->create(['user_id' => $target->id]);
        BudgetLedgerEntry::query()->create([
            'budget_id' => $budget->id,
            'entry_type' => BudgetLedgerEntryType::Commit,
            'amount_cents' => 100,
            'source_type' => $expense->getMorphClass(),
            'source_id' => $expense->id,
            'reverses_ledger_entry_id' => null,
        ]);

        $this->actingAs($accounting)
            ->delete(route('budgets.destroy', $budget))
            ->assertRedirect(route('budgets.index'))
            ->assertSessionHasErrors('budget');

        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
    }

    public function test_can_destroy_budget_without_ledger_entries(): void
    {
        $this->seed(RoleSeeder::class);
        $accounting = User::factory()->forRole('contabilidad')->create();
        $target = User::factory()->create();
        $budget = Budget::factory()->forBudgetable('user', $target->id)->create();

        $this->actingAs($accounting)
            ->delete(route('budgets.destroy', $budget))
            ->assertRedirect(route('budgets.index'));

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }
}
